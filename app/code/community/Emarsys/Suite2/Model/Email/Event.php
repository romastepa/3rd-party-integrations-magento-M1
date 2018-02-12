<?php
/**
 * @method Emarsys_Suite2_Model_Resource_Email_Event getResource()
 **/
class Emarsys_Suite2_Model_Email_Event extends Mage_Core_Model_Abstract
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('emarsys_suite2/email_event');
    }
    
    /**
     * Loads by event id
     * 
     * @param string $eventId
     * 
     * @return \Emarsys_Suite2_Model_Email_Event
     */
    public function loadByEventId($eventId)
    {
        $this->getResource()->load($this, $eventId, 'event_id');
        return $this;
    }
    
    /**
     * @inheritdoc
     */
    protected function _afterDelete()
    {
        if ($this->getTemplateId())
        {
            Mage::getModel('core/email_template')->load($this->getTemplateId())->delete();
        }
    }
    
    /**
     * @inheritdoc
     */
    protected function _beforeSave()
    {
        if (!$this->getTemplateId()) {
            $this->setTemplateId(
                Mage::getModel('core/email_template')
                    ->setTemplateCode('Emarsys event: ' . $this->getName())
                    ->setTemplateText('This is an emarsys event. Please edit the relevant email in Suite.')
                    ->save()
                    ->getId()
            );
        }

        return $this;
    }
}
