<?php

/**
 *
 * @category   Webextend
 * @package    Emarsys_Webextend
 * @copyright  Copyright (c) 2017 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Webextend_Adminhtml_Webextend_AttributemappingController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @return $this
     */
    protected function _initAction()
    {
        try {
            $this->loadLayout()
                ->_setActiveMenu('suite2email/attributemapping')
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
            return Mage::getSingleton('admin/session')->isAllowed('suite2email/attributemapping');
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
            $storeId = $this->getRequest()->getParam('store');
            if (!$storeId) {
                $storeId = Mage::getSingleton('suite2email/emarsysevents')->getFirstStoreId();
                Mage::app()->getResponse()->setRedirect($this->getUrl("adminhtml/webextend_attributemapping/index/", array('store' => $storeId)));
            }
            $this->_initAction()->renderLayout();
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Save grid data to db
     */
    public function saveGridAction()
    {
        try {
            $model = Mage::getModel("webextend/emarsysproductattributesmapping");
            $session = Mage::getSingleton("core/session")->getData();
            $storeId = $session['storeId'];
            $gridSessionData = $session['attributegridData'];
            foreach ($gridSessionData as $key => $value) {
                $model->setId($key);
                $model->setStoreId($storeId);
                $model->setMagentoAttributeCode($gridSessionData[$key]['magentoAttributeCode']);
                $model->setEmarsysAttributeCodeId($gridSessionData[$key]['emarsysAttributeCodeId']);
                $model->setEmarsysAttributeCodeLabel($gridSessionData[$key]['magentoAttributeCodeLabel']);
                $model->save();
            }
            Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Attributes mapped successfully!"));
            Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl("adminhtml/webextend_attributemapping/index/", array("store" => $storeId)));
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
            Mage::getSingleton("adminhtml/session")->addError(Mage::helper("adminhtml")->__("Error occurred while mapping Events"));
            $this->_redirect('*/*/index');
        }
    }

    /**
     * change grid coulmn value using ajax
     */
    public function changeValueAction()
    {
        try {
            $magentoAttributeCode = $this->getRequest()->getParam('magentoAttributeCode');
            $emarsysAttributeCodeId = $this->getRequest()->getParam('emarsysAttributeCodeId');
            $magentoAttributeCodeLabel = $this->getRequest()->getParam('magentoAttributeCodeLabel');
            $id = $this->getRequest()->getParam('Id');
            $session = Mage::getSingleton("core/session")->getData();
            $gridSession = $session['attributegridData'];
            $gridSession[$id]['magentoAttributeCode'] = $magentoAttributeCode;
            $gridSession[$id]['magentoAttributeCodeLabel'] = $magentoAttributeCodeLabel;
            $gridSession[$id]['emarsysAttributeCodeId'] = $emarsysAttributeCodeId;
            Mage::getSingleton('core/session')->setData('attributegridData', $gridSession);
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Recommended Mapping function
     */

    public function recommendedMappingAction()
    {
        try {
            // Pull events from Emarsys
            $storeId = $this->getRequest()->getParam('store');
            $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
            Mage::getSingleton('emarsys_suite2/config')->setWebsite($websiteId);
            $session = Mage::getSingleton("core/session")->getData();

            // Static Emarsys attributes array
            $array = Mage::helper('webextend')->getStaticFieldArray();

            //Collection of product attribute mapping and check records with static values if default mapping is missing then do mapping
            $model = Mage::getModel('webextend/emarsysproductattributesmapping');
            $collection = $model->getCollection();
            $collection = $collection->addFieldToFilter("store_id", $storeId);

            foreach ($collection as $col_record) {
                if ($col_record->getData('magento_attribute_code') == "sku") {
                    $emarsysAttributeId = $this->getEmarsysAttributeIds($storeId, $array[0]);
                    $model->setEmarsysAttributeCodeId($emarsysAttributeId);
                    $model->setStoreId($storeId);
                    $model->setId($col_record->getData("id"));
                    $model->save();
                }
                if ($col_record->getData('magento_attribute_code') == "name") {
                    $emarsysAttributeId = $this->getEmarsysAttributeIds($storeId, $array[1]);
                    $model->setEmarsysAttributeCodeId($emarsysAttributeId);
                    $model->setStoreId($storeId);
                    $model->setId($col_record->getData("id"));
                    $model->save();
                }
                if ($col_record->getData('magento_attribute_code') == "url_key") {
                    $emarsysAttributeId = $this->getEmarsysAttributeIds($storeId, $array[2]);
                    $model->setEmarsysAttributeCodeId($emarsysAttributeId);
                    $model->setStoreId($storeId);
                    $model->setId($col_record->getData("id"));
                    $model->save();
                }
                if ($col_record->getData('magento_attribute_code') == "image") {
                    $emarsysAttributeId = $this->getEmarsysAttributeIds($storeId, $array[3]);
                    $model->setEmarsysAttributeCodeId($emarsysAttributeId);
                    $model->setStoreId($storeId);
                    $model->setId($col_record->getData("id"));
                    $model->save();
                }
                if ($col_record->getData('magento_attribute_code') == "category_ids") {
                    $emarsysAttributeId = $this->getEmarsysAttributeIds($storeId, $array[4]);
                    $model->setEmarsysAttributeCodeId($emarsysAttributeId);
                    $model->setStoreId($storeId);
                    $model->setId($col_record->getData("id"));
                    $model->save();
                }
                if ($col_record->getData('magento_attribute_code') == "price") {
                    $emarsysAttributeId = $this->getEmarsysAttributeIds($storeId, $array[5]);
                    $model->setEmarsysAttributeCodeId($emarsysAttributeId);
                    $model->setStoreId($storeId);
                    $model->setId($col_record->getData("id"));
                    $model->save();
                }
                if ($col_record->getData('magento_attribute_code') == "msrp") {
                    $emarsysAttributeId = $this->getEmarsysAttributeIds($storeId, $array[6]);
                    $model->setEmarsysAttributeCodeId($emarsysAttributeId);
                    $model->setStoreId($storeId);
                    $model->setId($col_record->getData("id"));
                    $model->save();
                }
                if ($col_record->getData('magento_attribute_code') == "is_saleable") {
                    $emarsysAttributeId = $this->getEmarsysAttributeIds($storeId, $array[7]);
                    $model->setEmarsysAttributeCodeId($emarsysAttributeId);
                    $model->setStoreId($storeId);
                    $model->setId($col_record->getData("id"));
                    $model->save();
                }
                if ($col_record->getData('magento_attribute_code') == "manufacturer") {
                    $emarsysAttributeId = $this->getEmarsysAttributeIds($storeId, $array[8]);
                    $model->setEmarsysAttributeCodeId($emarsysAttributeId);
                    $model->setStoreId($storeId);
                    $model->setId($col_record->getData("id"));
                    $model->save();
                }
                if ($col_record->getData('magento_attribute_code') == "description") {
                    $emarsysAttributeId = $this->getEmarsysAttributeIds($storeId, $array[9]);
                    $model->setEmarsysAttributeCodeId($emarsysAttributeId);
                    $model->setStoreId($storeId);
                    $model->setId($col_record->getData("id"));
                    $model->save();
                }
                if ($col_record->getData('magento_attribute_code') == "image") {
                    $emarsysAttributeId = $this->getEmarsysAttributeIds($storeId, $array[10]);
                    $model->setEmarsysAttributeCodeId($emarsysAttributeId);
                    $model->setStoreId($storeId);
                    $model->setId($col_record->getData("id"));
                    $model->save();
                }
            }
            $storeId = $session['storeId'];
            Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Recommended Emarsys Attributes Mapping Created Successfully!"));
            Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl("adminhtml/webextend_attributemapping/index/", array("store" => $storeId, "limit" => 200)));
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Get Emarsys Attribute Id from Table
     * @param $storeId
     * @param $attributeCode
     * @return mixed
     *
     */
    public function getEmarsysAttributeIds($storeId, $attributeCode)
    {
        $model = Mage::getModel('webextend/emarsysproductattributes');
        $collection = $model->getCollection();
        $collection = $collection->addFieldToFilter("store_id", $storeId);
        $collection = $collection->addFieldToFilter("attribute_code", $attributeCode)->getFirstItem();

        return $collection->getData('id');
    }

    /**
     * UpdateSchema Action
     */
    public function updateSchemaAction()
    {
        try {
            //Function will delete events which are not exist in Emarsys and if new record found then will insert it
            $storeId = $this->getRequest()->getParam('store');
            $website = Mage::app()->getStore($storeId)->getWebsite();
            Mage::getSingleton('emarsys_suite2/config')->setWebsite($website);
            Mage::getSingleton('webextend/emarsysproductattributesmapping')->importNewAttributes($storeId);
            Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Schema Updated Successfully!"));
            Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl("adminhtml/webextend_attributemapping/index/", array('store' => $storeId)));
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}