<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Suite2email_Model_Emarsysplaceholdermapping extends Mage_Core_Model_Abstract
{
    /**
     * Construct
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('suite2email/emarsysplaceholdermapping');
    }

    /**
     * Create Event mapping first time
     * @param $storeId
     * @throws Exception
     */
    public function insertFirstime($mapping_id, $storeId)
    {
        try {
            $collectionEventMapping = Mage::getModel('suite2email/emarsyseventsmapping')->getCollection()
                ->addFieldToFilter("id", $mapping_id)
                ->addFieldToFilter("store_id", $storeId);
            $objEventMapping = $collectionEventMapping->getFirstItem();
            $magento_event_id = $objEventMapping->getData('magento_event_id');

            $collectionMagentoEvents = Mage::getModel('suite2email/emarsysmagentoevents')->getCollection()
                ->addFieldToFilter("id", $magento_event_id);

            $objMagentoEvents = $collectionMagentoEvents->getFirstItem();
            $configPath = $objMagentoEvents->getData('config_path');
            $template = Mage::getModel('core/email_template');

            $templateIdorCode = Mage::getStoreConfig($configPath, $storeId);
            if (is_numeric($templateIdorCode)) {
                $getTemplateId = Mage::getModel('core/email_template')->load($templateIdorCode);
                $template->load($getTemplateId->getTemplateId());
            } else {
                $template->loadDefault($templateIdorCode);
            }

            $array = array();
            $emailText = $template->getTemplateText();
            $i = 0;
            while ($variable = Mage::helper('suite2email')->substringBetween($emailText)) {
                $emailText = str_replace($variable, '', $emailText);
                $emarsysVariable = Mage::helper('suite2email')->getPlacheloderName($variable);
                if (!empty($emarsysVariable)) {
                    $array[$i]["event_mapping_id"] = $mapping_id;
                    $array[$i]["magento_placeholder_name"] = $variable;
                    $array[$i]["emarsys_placeholder_name"] = $emarsysVariable;
                    $array[$i]["store_id"] = $storeId;
                    $i++;
                }
            }
            foreach ($array as $key => $value) {
                $placeholderModel = Mage::getModel('suite2email/emarsysplaceholdermapping');
                $placeholderModel->setEventMappingId($value['event_mapping_id']);
                $placeholderModel->setMagentoPlaceholderName($value['magento_placeholder_name']);
                $placeholderModel->setEmarsysPlaceholderName($value['emarsys_placeholder_name']);
                $placeholderModel->setStoreId($value['store_id']);
                $placeholderModel->save();
            }
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}
