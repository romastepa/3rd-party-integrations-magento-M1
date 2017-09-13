<?php

/**
 *
 * @category   Webextend
 * @package    Emarsys_Webextend
 * @copyright  Copyright (c) 2017 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Webextend_Model_Observer
{
    /**
     * Catalog Export Function which will call from Cron
     */
    public function catalogExport()
    {
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
}
