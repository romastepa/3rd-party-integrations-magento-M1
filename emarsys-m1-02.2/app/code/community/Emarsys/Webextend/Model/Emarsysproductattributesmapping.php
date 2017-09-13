<?php

/**
 *
 * @category   Webextend
 * @package    Emarsys_Webextend
 * @copyright  Copyright (c) 2017 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Webextend_Model_Emarsysproductattributesmapping extends Mage_Core_Model_Abstract
{
    /**
     * Construct
     */
    public function _construct()
    {
        $this->_init('webextend/emarsysproductattributesmapping');
    }

    /**
     * Insert Attribute mapping first time
     * @param $storeId
     * @throws Exception
     */
    public function insertFirstime($storeId)
    {
        try {
            $collection = Mage::getModel('webextend/emarsysproductattributes')->getCollection()->addFieldToFilter("store_id", $storeId);
            if (!$collection->getSize()) {
                $this->importEmarsysAttributes($storeId);
            }
            $staticExportArray = Mage::helper('webextend')->getstaticExportArray();
            $productAttrs = Mage::getResourceModel('catalog/product_attribute_collection');
            foreach ($productAttrs as $productAttr) {
                /** @var Mage_Catalog_Model_Resource_Eav_Attribute $productAttr */
                if ($productAttr->getData("frontend_label") != "") {
                    $emarsysCodeId = '';
                    if ($productAttr->getData("attribute_code") == "sku")
                        $emarsysCodeId = $this->getEmarsysFieldIdByName($storeId, $staticExportArray[0]);

                    if ($productAttr->getData("attribute_code") == "name")
                        $emarsysCodeId = $this->getEmarsysFieldIdByName($storeId, $staticExportArray[2]);

                    if ($productAttr->getData("attribute_code") == "url_key")
                        $emarsysCodeId = $this->getEmarsysFieldIdByName($storeId, $staticExportArray[3]);

                    if ($productAttr->getData("attribute_code") == "image")
                        $emarsysCodeId = $this->getEmarsysFieldIdByName($storeId, $staticExportArray[4]);

                    if ($productAttr->getData("attribute_code") == "price")
                        $emarsysCodeId = $this->getEmarsysFieldIdByName($storeId, $staticExportArray[6]);

                    $model = Mage::getModel('webextend/emarsysproductattributesmapping');
                    if ($emarsysCodeId != "") {
                        $model->setMagentoAttributeCode($productAttr->getData("attribute_code"));
                        $model->setMagentoAttributeCodeLabel($productAttr->getData("frontend_label"));
                        $model->setEmarsysAttributeCodeId($emarsysCodeId);
                        $model->setStoreId($storeId);
                        $model->save();
                    } else {
                        $model->setMagentoAttributeCode($productAttr->getData("attribute_code"));
                        $model->setMagentoAttributeCodeLabel($productAttr->getData("frontend_label"));
                        $model->setEmarsysAttributeCodeId('');
                        $model->setStoreId($storeId);
                        $model->save();
                    }
                }
            }
            $emarsysCodeId = $this->getEmarsysFieldIdByName($storeId, $staticExportArray[5]);
            $model = Mage::getModel('webextend/emarsysproductattributesmapping');
            $model->setMagentoAttributeCode("category_ids");
            $model->setMagentoAttributeCodeLabel("Category");
            $model->setEmarsysAttributeCodeId($emarsysCodeId);
            $model->setStoreId($storeId);
            $model->save();

            $emarsysCodeId = $this->getEmarsysFieldIdByName($storeId, $staticExportArray[1]);
            $model = Mage::getModel('webextend/emarsysproductattributesmapping');
            $model->setMagentoAttributeCode("is_saleable");
            $model->setMagentoAttributeCodeLabel("Available");
            $model->setEmarsysAttributeCodeId($emarsysCodeId);
            $model->setStoreId($storeId);
            $model->save();

        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Import Emarsys default attribute first time for the respective store if not exists
     * @param $storeId
     * @throws Exception
     *
     */
    public function importEmarsysAttributes($storeId)
    {
        $array = Mage::helper('webextend')->getStaticFieldArray();
        for ($i = 0; $i < count($array); $i++) {
            $model = Mage::getModel('webextend/emarsysproductattributes');
            $attributeCode = $array[$i];
            $model->setAttributeCode($attributeCode);
            $model->setAttributeLabel($attributeCode);
            $model->setStoreId($storeId);
            $model->save();
        }
    }

    /**
     * Import New Magento Attributes into Mapping Table
     * @param $storeId
     */
    public function importNewAttributes($storeId)
    {
        $model = Mage::getModel('webextend/emarsysproductattributesmapping');
        $collection = $model->getCollection();
        foreach ($collection as $col_record) {
            $magentoAttributeCode[] = $col_record->getData('magento_attribute_code');
        }
        $productAttrs = Mage::getResourceModel('catalog/product_attribute_collection');
        foreach ($productAttrs as $productAttr) {
            if ($productAttr->getData("frontend_label") != "") {
                /** @var Mage_Catalog_Model_Resource_Eav_Attribute $productAttr */
                if (!in_array($productAttr->getData("attribute_code"), $magentoAttributeCode)) {
                    $model = Mage::getModel('webextend/emarsysproductattributesmapping');
                    $model->setMagentoAttributeCode($productAttr->getData("attribute_code"));
                    $model->setMagentoAttributeCodeLabel($productAttr->getData("frontend_label"));
                    $model->setEmarsysAttributeCodeId('');
                    $model->setStoreId($storeId);
                    $model->save();
                }
            }
        }
    }

    /**
     * Get Emarsys Field names by Emarsys Id
     * @param $storeId
     * @param $emarsysFieldId
     * @return mixed
     */
    public function getEmarsysFieldName($storeId, $emarsysFieldId)
    {
        $model = Mage::getModel('webextend/emarsysproductattributes');
        $collection = $model->getCollection();
        $collection->addFieldToFilter("id", $emarsysFieldId);
        $collection->addFieldToFilter("store_id", $storeId);
        $item = $collection->getFirstItem();
        return $item->getAttributeLabel();
    }

    /**
     * Get Emarsys Field Id by Emarsys Attribute Name
     * @param $storeId
     * @param $emarsysFieldName
     * @return mixed
     */
    public function getEmarsysFieldIdByName($storeId, $emarsysFieldName)
    {
        $model = Mage::getModel('webextend/emarsysproductattributes');
        $collection = $model->getCollection();
        $collection->addFieldToFilter("attribute_code", $emarsysFieldName);
        $collection->addFieldToFilter("store_id", $storeId);
        $item = $collection->getFirstItem();
        return $item->getId();
    }

    /**
     * Get Catalog Product Export Collection
     * @param $store
     * @param $exportProductTypes
     * @param $exportProductStatus
     * @return mixed
     */
    public function getCatalogExportProductCollection($store, $exportProductTypes, $exportProductStatus, $pageSize, $currentPageNumber)
    {
        try {
            $storeId = $store->getData('store_id');
            Mage::app()->getStore($storeId);
            $productCollection = Mage::getModel('catalog/product')->getCollection();
            $productCollection->setPageSize($pageSize)->setCurPage($currentPageNumber);
            $productCollection->addAttributeToSelect("*");
            $productCollection->addStoreFilter($store);
            $productCollection->joinAttribute(
                'visibility',
                'catalog_product/visibility',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
            $productCollection = $productCollection->addAttributeToFilter('visibility', array("neq" => 1));

            //Added collection filter of type ID
            if ($exportProductTypes != "") {
                $explode = explode(",", $exportProductTypes);
                $productCollection->addAttributeToFilter('type_id', array('in' => $explode));
            }
            //Added status filter
            if ($exportProductStatus == 1) {
                $productCollection->addAttributeToFilter('status', array('in' => array(1, 2)));
            } else {
                $productCollection->addAttributeToFilter('status', array('eq' => 1));
            }
            return $productCollection;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}