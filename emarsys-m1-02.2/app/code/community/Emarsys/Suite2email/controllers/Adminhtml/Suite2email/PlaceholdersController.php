<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Suite2email_Adminhtml_Suite2email_PlaceholdersController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @return $this
     */
    protected function _initAction()
    {
        try {
            $this->loadLayout()
                ->_setActiveMenu('suite2email/magentoevents')
                ->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));

            return $this;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Verify the permissions
     */
    protected function _isAllowed()
    {
        try {
            return Mage::getSingleton('admin/session')->isAllowed('suite2email/mappingitems');
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Index function
     */
    public function indexAction()
    {
        try {
            $this->_initAction()
                ->renderLayout();
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Save Mapping in Database
     * Added special character validation for placeholder names
     */
    public function saveMappingAction()
    {
        try {
            $model = Mage::getModel("suite2email/emarsysplaceholdermapping");
            $session = Mage::getSingleton("core/session")->getData();
            $mapping_id = $session['mappingId'];
            $store_id = $session['storeId'];
            $gridSessionData = $session['gridPlaceholders'];
            foreach ($gridSessionData as $key => $value) {
                $model->setId($key);
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $gridSessionData[$key]['emarsys_placeholder_name'])) {
                    Mage::getSingleton("adminhtml/session")->addError(Mage::helper("adminhtml")->__("Placeholders can only have Alphanumerics and Underscores"));
                    return $this->_redirect('*/*/index', array('mapping_id' => $mapping_id, 'store_id' => $store_id));
                }
                $model->setEventMappingId($gridSessionData[$key]['event_mapping_id']);
                $model->setMagentoPlaceholderName($gridSessionData[$key]['magento_placeholder_name']);
                $model->setEmarsysPlaceholderName($gridSessionData[$key]['emarsys_placeholder_name']);
                $model->save();
            }
            Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Mapping Updated successfully!"));
            Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl("adminhtml/suite2email_placeholders/index/mapping_id/" . $mapping_id . "/store_id/" . $store_id));
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
            Mage::getSingleton("adminhtml/session")->addError(Mage::helper("adminhtml")->__("Error occurred while updating mapping"));
            $this->_redirect('*/*/index', array('mapping_id' => $mapping_id, 'store_id' => $store_id));
        }
    }

    /**
     * Change grid coulmn value using ajax
     */
    public function changeValueAction()
    {
        try {
            $id = $this->getRequest()->getParam('id');
            $event_mapping_id = $this->getRequest()->getParam('event_mapping_id');
            $emarsys_placeholder_name = $this->getRequest()->getParam('emarsys_placeholder_name');
            $model = Mage::getModel("suite2email/emarsysplaceholdermapping");
            $collection = $model->getCollection();
            $collection->addFieldToFilter("id", $id);
            $item = $collection->getFirstItem();
            $magento_placeholder_name = $item->getData('magento_placeholder_name');

            $session = Mage::getSingleton("core/session")->getData();
            $gridSession = $session['gridPlaceholders'];
            $gridSession[$id]['event_mapping_id'] = $event_mapping_id;
            $gridSession[$id]['magento_placeholder_name'] = $magento_placeholder_name;
            $gridSession[$id]['emarsys_placeholder_name'] = $emarsys_placeholder_name;
            Mage::getSingleton('core/session')->setData('gridPlaceholders', $gridSession);
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * JSON Request function
     */
    public function jsonrequestAction()
    {
        try {
            $mapping_id = $this->getRequest()->getParam('mapping_id');
            $store_id = $this->getRequest()->getParam('store_id');
            $placeholders = array("external_id" => "RECIPIENT_EMAIL", "key_id" => "KEY_ID");

            $hasPlaceholders = false;

            $eventMappingModel = Mage::getModel("suite2email/emarsyseventsmapping")->load($mapping_id);
            $magentoEventId = $eventMappingModel->getData('magento_event_id');

            $magentoEventModel = Mage::getModel("suite2email/emarsysmagentoevents")->load($magentoEventId);
            $magentoEventName = $magentoEventModel->getData('magento_event');

            $headerPlaceholders = $this->getHeaderPlaceholders($store_id);
            if (count($headerPlaceholders) > 0) {
                $hasPlaceholders = true;
                foreach ($headerPlaceholders as $key => $value) {
                    $placeholders['data']['global'][$key] = $value;
                }
            }

            $_placeholders = $this->getPlacholdersByMappingId($mapping_id);
            if (count($_placeholders) > 0) {
                $hasPlaceholders = true;
                foreach ($_placeholders as $key => $value) {
                    $placeholders['data']['global'][$key] = $value;
                }
            }

            $footerPlaceholders = $this->getFooterPlaceholders($store_id);
            if (count($footerPlaceholders) > 0) {
                $hasPlaceholders = true;
                foreach ($footerPlaceholders as $key => $value) {
                    $placeholders['data']['global'][$key] = $value;
                }
            }

            if ($hasPlaceholders) {
                if (strstr($magentoEventName, "Order") || strstr($magentoEventName, "Shipment") || strstr($magentoEventName, "Invoice") || strstr($magentoEventName, "Credit Memo") || strstr($magentoEventName, "RMA")) {
                    $result = $eventMappingModel->emarsysDefaultPlaceholders();
                    foreach ($result as $key => $value) {
                        $placeholders['data'][$key] = $value;
                    }
                }
            }
            if ($hasPlaceholders) {
                printf ("<pre>" . json_encode($placeholders, JSON_PRETTY_PRINT) . "</pre>");
            } else {
                printf ("No Placeholders Available");
            }
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Header Template Placholders
     * @param $storeId
     * @return array
     */
    public function getHeaderPlaceholders($storeId)
    {
        try {
            $returnArray = array();
            $mEvent = Mage::getModel('suite2email/emarsysmagentoevents')->getCollection()
                ->addFieldToFilter("config_path", 'design/email/header')
                ->getFirstItem();
            if ($meventId = $mEvent->getId()) {
                $eEvent = Mage::getModel('suite2email/emarsyseventsmapping')->getCollection()
                    ->addFieldToFilter("store_id", $storeId)
                    ->addFieldToFilter("magento_event_id", $meventId)
                    ->getFirstItem();
                if ($mappingId = $eEvent->getId()) {
                    $returnArray = $this->getPlacholdersByMappingId($mappingId);
                }
            }
            return $returnArray;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Footer Template Placholders
     * @param $storeId
     * @return array
     */
    public function getFooterPlaceholders($storeId)
    {
        try {
            $returnArray = array();
            $mEvent = Mage::getModel('suite2email/emarsysmagentoevents')->getCollection()
                ->addFieldToFilter("config_path", 'design/email/footer')
                ->getFirstItem();
            if ($meventId = $mEvent->getId()) {
                $eEvent = Mage::getModel('suite2email/emarsyseventsmapping')->getCollection()
                    ->addFieldToFilter("store_id", $storeId)
                    ->addFieldToFilter("magento_event_id", $meventId)
                    ->getFirstItem();
                if ($mappingId = $eEvent->getId()) {
                    $returnArray = $this->getPlacholdersByMappingId($mappingId);
                }
            }
            return $returnArray;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    public function getPlacholdersByMappingId($mapping_id)
    {
        try {
            $returnArray = array();
            $collection = Mage::getModel("suite2email/emarsysplaceholdermapping")->getCollection()
                ->addFieldToFilter("event_mapping_id", $mapping_id);

            if ($collection->getSize() == 0) {
                $objEventMapping = Mage::getModel('suite2email/emarsyseventsmapping')->getCollection()
                    ->addFieldToFilter("id", $mapping_id)
                    ->getFirstItem();
                if ($objEventMapping->getId()) {
                    $storeId = $objEventMapping->getData('store_id');
                    Mage::getModel('suite2email/emarsysplaceholdermapping')->insertFirstime($mapping_id, $storeId);
                    /* Reload the collection */
                    $collection = Mage::getModel("suite2email/emarsysplaceholdermapping")->getCollection()->addFieldToFilter("event_mapping_id", $mapping_id);
                }
            }
            if ($collection->getSize()) {
                foreach ($collection as $coll) {
                    $returnArray[$coll->getEmarsysPlaceholderName()] = $coll->getMagentoPlaceholderName(); //strtoupper($coll->getEmarsysPlaceholderName());
                }
            }
            return $returnArray;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Get new placeholders added in template
     * @throws Exception
     */

    public function refreshPlaceholdersAction()
    {
        try {
            $mapping_id = $this->getRequest()->getParam('mapping_id');
            $storeId = $this->getRequest()->getParam('store');
            $dbEmarsysPlaceHolders = array();
            $templateEmarsysPlaceHolders = array();
            $collectionEventMapping = Mage::getModel('suite2email/emarsyseventsmapping')->getCollection();
            $collectionEventMapping->addFieldToFilter("id", $mapping_id);
            $objEventMapping = $collectionEventMapping->getFirstItem();
            $magento_event_id = $objEventMapping->getData('magento_event_id');

            $collectionMagentoEvents = Mage::getModel('suite2email/emarsysmagentoevents')->getCollection();
            $collectionMagentoEvents->addFieldToFilter("id", $magento_event_id);
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
            $placeholderModel = Mage::getModel('suite2email/emarsysplaceholdermapping');
            $databaseCollection = $placeholderModel->getCollection()
                ->addFieldToFilter("event_mapping_id", $mapping_id)
                ->addFieldToFilter("store_id", $storeId);
            foreach ($databaseCollection as $dbCollection) {
                $placeholder = $dbCollection->getMagentoPlaceholderName();
                $dbEmarsysPlaceHolders[] = $placeholder;
            }
            $array = array();
            $emailText = $template->getTemplateText();
            $i = 0;
            while ($variable = Mage::helper('suite2email')->substringBetween($emailText)) {
                $emailText = str_replace($variable, '', $emailText);
                $templateEmarsysPlaceHolders[] = $variable;
            }
            $resultantArray = array_diff($dbEmarsysPlaceHolders, $templateEmarsysPlaceHolders);
            if (count($resultantArray) > 0) {
                foreach ($resultantArray as $_placeholder) {
                    $placeholderModel = Mage::getModel('suite2email/emarsysplaceholdermapping')->getCollection()
                        ->addFieldToFilter("event_mapping_id", $mapping_id)
                        ->addFieldToFilter("store_id", $storeId)
                        ->addFieldToFilter("magento_placeholder_name", $_placeholder)
                        ->getFirstItem();
                    if ($placeholderModel->getId()) {
                        $placeholderModel->delete();
                    }
                }
            }
            $emailText = $template->getTemplateText();
            while ($variable = Mage::helper('suite2email')->substringBetween($emailText)) {
                $emailText = str_replace($variable, '', $emailText);
                $emarsysVariable = Mage::helper('suite2email')->getPlacheloderName($variable);
                if (!empty($emarsysVariable)) {
                    $placeholderModel = Mage::getModel('suite2email/emarsysplaceholdermapping');
                    $collection = $placeholderModel->getCollection()
                        ->addFieldToFilter("event_mapping_id", $mapping_id)
                        ->addFieldToFilter("store_id", $storeId)
                        ->addFieldToFilter("magento_placeholder_name", array('like' => '%' . $variable . '%'));
                    if (!$collection->getSize()) {
                        $array[$i]["event_mapping_id"] = $mapping_id;
                        $array[$i]["magento_placeholder_name"] = $variable;
                        $array[$i]["emarsys_placeholder_name"] = $emarsysVariable;
                        $array[$i]["store_id"] = $storeId;
                    }
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
            Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Placeholders refreshed successfully!"));
            Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl("adminhtml/suite2email_placeholders/index/mapping_id/" . $mapping_id . "/store_id/" . $storeId));
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}
