<?php

class Emarsys_Suite2_Model_System_Config_Backend_Email_Event extends Mage_Core_Model_Config_Data
{
    /**
     * @inheritdoc
     */
    protected function _beforeSave()
    {
        // Unsets events which cannot be removed
        $oldEvents = ($this->getOldValue() ? Mage::helper('core')->jsonDecode($this->getOldValue()) : array());
        $newEvents = ($this->getValue() ? Mage::helper('core')->jsonDecode($this->getValue()) : array());
        $deletedEvents = array_diff($oldEvents, $newEvents);

        foreach ($deletedEvents as $deletedEvent) {
            if ($events = Mage::helper('emarsys_suite2/adminhtml')->isEventRegistered($deletedEvent)) {
                $newEvents[] = $deletedEvent;
                foreach ($events as $eventPath) {
                    $error = sprintf('Event %s is still used in %s', $deletedEvent, $eventPath);
                    Mage::getSingleton('adminhtml/session')->addError($error);
                }
            }
        }

        $this->setValue(Mage::helper('core')->jsonEncode($newEvents));
    }
    
    /**
     * @inheritdoc
     */
    protected function _afterSave()
    {
        // Removes safely removable elements and creates new
        if ($this->isValueChanged() && $this->getValue()) {
            $eventIds = Mage::helper('core')->jsonDecode($this->getValue());
            if (!empty($eventIds)) {
                Mage::getSingleton('emarsys_suite2/email_event')->getResource()->deleteExcept($eventIds);
            } else {
                Mage::getSingleton('emarsys_suite2/email_event')->getResource()->deleteAll();
            }

            if ($eventIds) {
                $apiEvents = Mage::getSingleton('emarsys_suite2/api_event')->getEvents();
            }

            foreach ($eventIds as $eventId) {
                $event = Mage::getModel('emarsys_suite2/email_event')->loadByEventId($eventId);
                if (!$event->getId()) {
                    $event->setEventId($eventId);
                }

                $event->setName($apiEvents[$eventId])->save();
            }
        }

        return $this;
    }
} 