<?php
/**
 *
 * @category   Webextend
 * @package    Emarsys_Webextend
 * @copyright  Copyright (c) 2017 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

class Emarsys_Webextend_Model_Observer
{
    protected $_credentials = array();
    protected $_websites = array();

    /**
     * Catalog Export Function which will call from Cron
     */
    public function catalogExport()
    {
        return $this->consolidatedCatalogExport();
        try {
            set_time_limit(0);
            $staticExportArray = Mage::helper('webextend')->getstaticExportArray();
            $staticMagentoAttributeArray = Mage::helper('webextend')->getstaticMagentoAttributeArray();
            $allStores = Mage::app()->getStores();
            foreach ($allStores as $store) {
                $counter = 0;
                $websiteId = $store->getData("website_id");
                $storeId = $store->getData('store_id');
                //Getting Configuration of SmartInsight and Webextend Export
                $hostname = Mage::getStoreConfig('emarsys_suite2_smartinsight/ftp/host', $storeId);
                $username = Mage::getStoreConfig('emarsys_suite2_smartinsight/ftp/user', $storeId);
                $password = Mage::getStoreConfig('emarsys_suite2_smartinsight/ftp/password', $storeId);
                $ftpSsl = Mage::getStoreConfig('emarsys_suite2_smartinsight/ftp/ssl', $storeId);
                $exportProductStatus = Mage::getStoreConfig("catalogexport/configurable_cron/webextenproductstatus", $storeId);
                $exportProductTypes = Mage::getStoreConfig("catalogexport/configurable_cron/webextenproductoptions", $storeId);
                $fullCatalogExportEnabled = Mage::getStoreConfig("catalogexport/configurable_cron/fullcatalogexportenabled", $storeId);

                if ($fullCatalogExportEnabled == 1) {
                    if ($hostname != '' && $username != '' && $password != '') {
                        if ($ftpSsl == 1) {
                            $ftpConnection = @ftp_ssl_connect($hostname);
                        } else {
                            $ftpConnection = @ftp_connect($hostname);
                        }
                        //Login to FTP
                        $ftpLogin = @ftp_login($ftpConnection, $username, $password);
                        if ($ftpLogin) {
                            $storeCode = $store->getData("code");

                            $emarsysFieldNames = array();
                            $magentoAttributeNames = array();

                            //Getting mapped emarsys attribute collection
                            $model = Mage::getModel('webextend/emarsysproductattributesmapping');
                            $collection = $model->getCollection();
                            $collection->addFieldToFilter("store_id", $storeId);
                            $collection->addFieldToFilter("emarsys_attribute_code_id", array("neq" => 0));
                            if ($collection->getSize()) {
                                //need to make sure required mapping fields should be there else we have to manually map.
                                foreach ($collection as $col_record) {
                                    $emarsysFieldName = Mage::getModel('webextend/emarsysproductattributesmapping')->getEmarsysFieldName($storeId, $col_record->getData('emarsys_attribute_code_id'));
                                    $emarsysFieldNames[] = $emarsysFieldName;
                                    $magentoAttributeNames[] = $col_record->getData('magento_attribute_code');
                                }
                                for ($ik = 0; $ik < count($staticExportArray); $ik++) {
                                    if (!in_array($staticExportArray[$ik], $emarsysFieldNames)) {
                                        $emarsysFieldNames[] = $staticExportArray[$ik];
                                        $magentoAttributeNames[] = $staticMagentoAttributeArray[$ik];
                                    }
                                }
                            } else {
                                // As we does not have any Magento Emarsys Attibutes mapping so we will go with default Emarsys export attributes
                                for ($ik = 0; $ik < count($staticExportArray); $ik++) {
                                    if (!in_array($staticExportArray[$ik], $emarsysFieldNames)) {
                                        $emarsysFieldNames[] = $staticExportArray[$ik];
                                        $magentoAttributeNames[] = $staticMagentoAttributeArray[$ik];
                                    }
                                }
                            }

                            $currentPageNumber = 1;
                            $pageSize = Mage::helper('emarsys_suite2/adminhtml')->getBatchSize();
                            //Product collection with 1000 batch size
                            $productCollection = Mage::getModel("webextend/emarsysproductattributesmapping")
                                ->getCatalogExportProductCollection($store, $exportProductTypes, $exportProductStatus, $pageSize, $currentPageNumber);
                            $lastPageNumber = $productCollection->getLastPageNumber();

                            //create CSV file with emarsys field name header
                            if ($counter == 0 && $productCollection->getSize()) {
                                $heading = $emarsysFieldNames;
                                $localFilePath = BP . "/var";
                                $outputFile = "products_" . date('YmdHis', time()) . "_" . $storeCode . ".csv";
                                $filePath = $localFilePath . "/" . $outputFile;
                                $handle = fopen($filePath, 'w');
                                fputcsv($handle, $heading);
                            }
                            while ($currentPageNumber <= $lastPageNumber) {
                                if ($currentPageNumber != 1) {
                                    $productCollection = Mage::getModel("webextend/emarsysproductattributesmapping")
                                        ->getCatalogExportProductCollection($store, $exportProductTypes, $exportProductStatus, $pageSize, $currentPageNumber);
                                }
                                //iterate the product collection
                                if (count($productCollection)) {
                                    foreach ($productCollection as $product) {
                                        try {
                                            $productData = Mage::getModel("catalog/product")->setStoreId($storeId)->load($product->getId());
                                        } catch (Exception $e) {
                                            print_r($e->getMessage());
                                        }
                                        $catIds = $productData->getCategoryIds();
                                        $categoryNames = array();

                                        //Get Category Names
                                        foreach ($catIds as $catId) {
                                            $cateData = Mage::getModel("catalog/category")->setStoreId($storeId)->load($catId);
                                            $categoryPath = $cateData->getPath();
                                            $categoryPathIds = explode('/', $categoryPath);
                                            $childCats = array();
                                            if (count($categoryPathIds) > 2) {
                                                $pathIndex = 0;
                                                foreach ($categoryPathIds as $categoryPathId) {
                                                    if ($pathIndex <= 1) {
                                                        $pathIndex++;
                                                        continue;
                                                    }
                                                    $childCateData = Mage::getModel("catalog/category")->load($categoryPathId);
                                                    $childCats[] = $childCateData->getName();
                                                }
                                                $categoryNames[] = implode(" > ", $childCats);
                                            }
                                        }

                                        //getting Product Attribute Data
                                        $attributeData = Mage::helper('webextend')->attributeData($magentoAttributeNames, $productData, $categoryNames);
                                        fputcsv($handle, $attributeData);
                                    }
                                    $currentPageNumber = $currentPageNumber + 1;
                                }
                            }
                            Mage::helper('webextend')->moveToFTP($websiteId, $outputFile, $ftpConnection, $filePath);
                        } else {
                            Mage::helper('emarsys_suite2')->log("Unable to connect FTP for Store ID: " . $storeId, $this);
                        }
                    }
                }
                $counter++;
            }
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    public function newSubscriberEmailAddress(Varien_Event_Observer $observer)
    {
        try {
            $event = $observer->getEvent();
            $subscriber = $event->getSubscriber();
            Mage::getSingleton('core/session')->setWebExtendCustomerEmail($subscriber->getSubscriberEmail());
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log('WebExtend_newSubscriberEmailAddress_Exception: ' . $e->getMessage());
        }
    }

    public function newCustomerEmailAddress(Varien_Event_Observer $observer)
    {
        try {
            $event = $observer->getEvent();
            $customer = $event->getCustomer();
            Mage::getSingleton('core/session')->setWebExtendCustomerEmail($customer->getEmail());
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log('WebExtend_newCustomerEmailAddress_Exception: ' . $e->getMessage());
        }
    }

    public function newOrderEmailAddress(Varien_Event_Observer $observer){
        try {
            $orderIds = $observer->getEvent()->getOrderIds();
            if (empty($orderIds) || !is_array($orderIds)) {
                return;
            }
            foreach($orderIds as $_orderId){
                $order = Mage::getModel('sales/order')->load($_orderId);
                Mage::getSingleton('core/session')->setWebExtendCustomerEmail($order->getCustomerEmail());
                if($order->getCustomerId()) {
                    Mage::getSingleton('core/session')->setWebExtendCustomerId($order->getCustomerId());
                }
            }
            Mage::getSingleton('core/session')->setWebExtendNewOrderIds($orderIds);
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log('WebExtend_newOrderEmailAddress_Exception: ' . $e->getMessage());
        }
    }

    public function hookToControllerActionPreDispatch(Varien_Event_Observer $observer)
    {
        try {
            if($observer->getEvent()->getControllerAction()->getRequest()->getParams() && $observer->getEvent()->getControllerAction()->getRequest()->getParam('email')) {
                Mage::getSingleton('core/session')->setWebExtendCustomerEmail($observer->getEvent()->getControllerAction()->getRequest()->getParam('email'));
            }

            if($observer->getEvent()->getControllerAction()->getRequest()->getPost() && $observer->getEvent()->getControllerAction()->getRequest()->getPost('email')) {
                Mage::getSingleton('core/session')->setWebExtendCustomerEmail($observer->getEvent()->getControllerAction()->getRequest()->getPost('email'));
            }
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log('WebExtend_hookToControllerActionPreDispagtch_Exception: ' . $e->getMessage());
        }
    }

    public function consolidatedCatalogExport()
    {
        try {
            set_time_limit(0);
            $allStores = Mage::app()->getStores();

            foreach ($allStores as $store) {
                $this->setCredentials($store);
            }

            foreach ($this->getCredentials() as $websiteId => $website) {
                $attributes = array();

                foreach ($website as $store) {
                    $attributes = array_merge($attributes, $store['mapped_attributes_names']);
                }

                $attributes = array_unique($attributes);

                /** @var Emarsys_Webextend_Model_Emarsysproductexport $productExportModel */
                $productExportModel = Mage::getModel("webextend/emarsysproductexport");

                $productExportModel->truncateTable();

                foreach ($website as $storeId => $store) {
                    $currentPageNumber = 1;
                    $collection = $productExportModel->getCatalogExportProductCollection(
                        $storeId,
                        $currentPageNumber,
                        $attributes
                    );

                    $lastPageNumber = $collection->getLastPageNumber();
                    $header = $store['emarsys_field_names'];

                    while ($currentPageNumber <= $lastPageNumber) {
                        if ($currentPageNumber != 1) {
                            $collection = $productExportModel->getCatalogExportProductCollection(
                                $storeId,
                                $currentPageNumber,
                                $attributes
                            );
                        }
                        $products = array();
                        foreach ($collection as $product) {
                            $catIds = $product->getCategoryIds();
                            $categoryNames = $this->getCategoryNames($catIds, $storeId);
                            $products[$product->getId()] = array(
                                'entity_id' => $product->getId(),
                                'params' => serialize(array(
                                    'store' => $store['store']->getCode(),
                                    'data' => Mage::helper('webextend')->attributeData($store['mapped_attributes_names'], $product, $categoryNames),
                                    'header' => $header
                                ))
                            );
                        }
                        if (!empty($products)) {
                            $productExportModel->saveBulkProducts($products);
                        }
                        $currentPageNumber++;
                    }
                }

                if (!empty($store)) {
                    list($csvFilePath, $outputFile) = $productExportModel->saveToCsv($websiteId);
                    Mage::helper('webextend')->moveToFTP($storeId, $outputFile, $store['ftp_connection'], $csvFilePath);
                }
            }
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Get Store Credentials
     *
     * @param null|int $websiteId
     * @param null|int $storeId
     * @return array|mixed
     */
    public function getCredentials($websiteId = null, $storeId = null)
    {
        $return = $this->_credentials;
        if (!is_null($storeId) && !is_null($websiteId)) {
            $return = null;
            if (isset($this->_credentials[$storeId])) {
                $return = $this->_credentials[$storeId];
            }
        }
        return $return;
    }

    /**
     * Set Store Credential
     *
     * @param Mage_Core_Model_Store $store
     */
    public function setCredentials($store)
    {
        $storeId = $store->getId();
        if (!isset($this->_credentials[$store->getWebsiteId()][$storeId])) {
            $fullCatalogExportEnabled = Mage::getStoreConfig("catalogexport/configurable_cron/fullcatalogexportenabled", $storeId);
            if ($fullCatalogExportEnabled) {
                $hostname = Mage::getStoreConfig('emarsys_suite2_smartinsight/ftp/host', $storeId);
                $username = Mage::getStoreConfig('emarsys_suite2_smartinsight/ftp/user', $storeId);
                $password = Mage::getStoreConfig('emarsys_suite2_smartinsight/ftp/password', $storeId);
                $ftpSsl = Mage::getStoreConfig('emarsys_suite2_smartinsight/ftp/ssl', $storeId);
                if ($hostname != '' && $username != '' && $password != '') {
                    if ($ftpSsl == 1) {
                        $ftpConnection = @ftp_ssl_connect($hostname);
                    } else {
                        $ftpConnection = @ftp_connect($hostname);
                    }
                    //Login to FTP
                    $ftpLogin = @ftp_login($ftpConnection, $username, $password);
                    if ($ftpLogin) {
                        $websiteId = $this->getWebsiteId($store);
                        $this->_credentials[$this->_getWebsiteId($store)][$storeId]['store'] = $store;
                        $this->getMappedAttributes($websiteId, $storeId);
                        $this->_credentials[$websiteId][$storeId]['ftp_connection'] = $ftpConnection;
                    } else {
                        Mage::helper('emarsys_suite2')->log("Unable to connect FTP for Store ID: " . $storeId, $this);
                    }
                }
            }
        }
    }

    /**
     * Get Grouped WebsiteId
     *
     * @param Mage_Core_Model_Store $store
     * @return int
     */
    public function getWebsiteId($store)
    {
        $apiUserName = Mage::getStoreConfig('emarsys_suite2/settings/api_username', $store);
        if (!isset($this->_websites[$apiUserName])) {
            $this->_websites[$apiUserName] = $store->getWebsiteId();
        }

        return $this->_websites[$apiUserName];
    }

    /**
     * Get Mapped Attributes
     *
     * @param int $websiteId
     * @param int $storeId
     */
    public function getMappedAttributes($websiteId, $storeId)
    {
        $staticExportArray = Mage::helper('webextend')->getstaticExportArray();
        $staticMagentoAttributeArray = Mage::helper('webextend')->getstaticMagentoAttributeArray();
        $emarsysFieldNames = array();
        $magentoAttributeNames = array();

        //Getting mapped emarsys attribute collection
        $model = Mage::getModel('webextend/emarsysproductattributesmapping');
        $collection = $model->getCollection();
        $collection->addFieldToFilter("store_id", $storeId);
        $collection->addFieldToFilter("emarsys_attribute_code_id", array("neq" => 0));
        if ($collection->getSize()) {
            //need to make sure required mapping fields should be there else we have to manually map.
            foreach ($collection as $col_record) {
                $emarsysFieldName = Mage::getModel('webextend/emarsysproductattributesmapping')->getEmarsysFieldName($storeId, $col_record->getData('emarsys_attribute_code_id'));
                $emarsysFieldNames[] = $emarsysFieldName;
                $magentoAttributeNames[] = $col_record->getData('magento_attribute_code');
            }
            for ($ik = 0; $ik < count($staticExportArray); $ik++) {
                if (!in_array($staticExportArray[$ik], $emarsysFieldNames)) {
                    $emarsysFieldNames[] = $staticExportArray[$ik];
                    $magentoAttributeNames[] = $staticMagentoAttributeArray[$ik];
                }
            }
        } else {
            // As we does not have any Magento Emarsys Attibutes mapping so we will go with default Emarsys export attributes
            for ($ik = 0; $ik < count($staticExportArray); $ik++) {
                if (!in_array($staticExportArray[$ik], $emarsysFieldNames)) {
                    $emarsysFieldNames[] = $staticExportArray[$ik];
                    $magentoAttributeNames[] = $staticMagentoAttributeArray[$ik];
                }
            }
        }

        $this->_credentials[$websiteId][$storeId]['emarsys_field_names'] = $emarsysFieldNames;
        $this->_credentials[$websiteId][$storeId]['mapped_attributes_names'] = $magentoAttributeNames;
    }

    /**
     * Get Category Names
     *
     * @param $catIds
     * @param $storeId
     * @return array
     */
    public function getCategoryNames($catIds, $storeId)
    {
        $categoryNames = array();
        foreach ($catIds as $catId) {
            $cateData = Mage::getModel("catalog/category")->setStoreId($storeId)->load($catId);
            $categoryPath = $cateData->getPath();
            $categoryPathIds = explode('/', $categoryPath);
            $childCats = array();
            if (count($categoryPathIds) > 2) {
                $pathIndex = 0;
                foreach ($categoryPathIds as $categoryPathId) {
                    if ($pathIndex <= 1) {
                        $pathIndex++;
                        continue;
                    }
                    $childCateData = Mage::getModel("catalog/category")->load($categoryPathId);
                    $childCats[] = $childCateData->getName();
                }
                $categoryNames[] = implode(" > ", $childCats);
            }
        }

        return $categoryNames;
    }
}
