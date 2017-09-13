<?php

/**
 *
 * @category   Webextend
 * @package    Emarsys_Webextend
 * @copyright  Copyright (c) 2017 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Webextend_Adminhtml_Webextend_NewattributeController extends Mage_Adminhtml_Controller_Action
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
            $this->_initAction()
                ->renderLayout();
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }


    protected function _isAllowed()
    {
        try {
            return Mage::getSingleton('admin/session')->isAllowed('suite2email/attributemapping');
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     *
     * Saving newly added attributes in DB
     */
    public function saveAction()
    {
        $array = Mage::helper('webextend')->getStaticFieldArray();
        try {
            $data = $this->getRequest()->getParams();
            $storeId = $data['storeId'];
            if ($data['field_name'][0] != "") {
                for ($i = 0; $i < count($data['field_name']); $i++) {
                    $field_name = $data['field_name'][$i];
                    $field_label = $data['field_label'][$i];

                    //Checking for attribute code duplicates
                    $model = Mage::getModel('webextend/emarsysproductattributes');
                    $collection = $model->getCollection();
                    $collection = $collection->addFieldToFilter("store_id", $storeId);
                    $collection = $collection->addFieldToFilter(
                        array(
                            'attribute_code',
                            'attribute_label'
                        ),
                        array(
                            array('eq' => $field_label),
                            array('eq' => $field_name),
                        )
                    );
                    if (!$collection->count()) {
                        if (!in_array($field_name, $array) || !in_array($field_label, $array)) {
                            $model = Mage::getModel('webextend/emarsysproductattributes');
                            if (strstr($field_name, 'c_')) {
                                $model->setAttributeCode($field_name);
                            } else {
                                $field_name = "c_" . $field_name;
                                $model->setAttributeCode($field_name);
                            }
                            if (strstr($field_label, 'c_')) {
                                $model->setAttributeLabel($field_label);
                            } else {
                                $field_label = "c_" . $field_label;
                                $model->setAttributeLabel($field_label);
                            }
                            $model->setStoreId($storeId);
                            $model->save();
                        }
                    } else {
                        Mage::getSingleton("adminhtml/session")->addNotice(Mage::helper("adminhtml")->__($field_name . " Attribute can not be added as its already Exists!"));
                    }
                }
            }
            Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl("adminhtml/webextend_newattribute/index/", array('store' => $storeId)));
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Update attribute values
     */
    public function updateAction()
    {
        try {
            $array = Mage::helper('webextend')->getStaticFieldArray();
            $id = $this->getRequest()->getParam('recordid');
            $storeId = $this->getRequest()->getParam('storeId');
            $attribute_code = $this->getRequest()->getParam('attribute_code');
            $attribute_label = $this->getRequest()->getParam('attribute_label');

            //Checking for attribute code duplicates
            $model = Mage::getModel('webextend/emarsysproductattributes');
            $collection = $model->getCollection();
            $collection = $collection->addFieldToFilter("store_id", $storeId);
            $collection = $collection->addFieldToFilter(
                array(
                    'attribute_code',
                    'attribute_label'
                ),
                array(
                    array('eq' => $attribute_code),
                    array('eq' => $attribute_label),
                )
            );
            $collection = $collection->addFieldToFilter("id", array('neq' => $id));

            if (!$collection->count()) {
                if (!in_array($attribute_code, $array) || !in_array($attribute_label, $array)) {
                    $model = Mage::getModel('webextend/emarsysproductattributes');
                    if (strstr($attribute_code, 'c_')) {
                        $model->setAttributeCode($attribute_code);
                    } else {
                        $attribute_code = "c_" . $attribute_code;
                        $model->setAttributeCode($attribute_code);
                    }
                    if (strstr($attribute_label, 'c_')) {
                        $model->setAttributeLabel($attribute_label);
                    } else {
                        $attribute_label = "c_" . $attribute_label;
                        $model->setAttributeLabel($attribute_label);
                    }
                    $model->setId($id);
                    $model->setStoreId($storeId);
                    $model->save();
                    Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Attributes Updated Successfully!"));
                } else {
                    Mage::getSingleton("adminhtml/session")->addNotice(Mage::helper("adminhtml")->__("Attribute should not be same as Default Emarsys Attributes!"));
                }
            } else {
                Mage::getSingleton("adminhtml/session")->addNotice(Mage::helper("adminhtml")->__($attribute_label . " Attribute can not be added as its already Exists!"));
            }

            Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl("adminhtml/webextend_newattribute/index/", array('store' => $storeId)));
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Delete attribute values
     */

    public function deleteAction()
    {
        try {
            $id = $this->getRequest()->getParam('recordid');
            $storeId = $this->getRequest()->getParam('storeId');
            $model = Mage::getModel('webextend/emarsysproductattributes');
            $model->load($id)->delete();

            Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Attribute Deleted Successfully!"));
            Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl("adminhtml/webextend_newattribute/index/", array('store' => $storeId)));
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}
