<?php
/**
 *
 * @category   Webextend
 * @package    Emarsys_Webextend
 * @copyright  Copyright (c) 2017 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

class Emarsys_Webextend_Model_Observer
{
    protected $_credentials = [];
    protected $_websites = [];
    protected $_categoryNames = [];

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

    public function newOrderEmailAddress(Varien_Event_Observer $observer)
    {
        try {
            $orderIds = $observer->getEvent()->getOrderIds();
            if (empty($orderIds) || !is_array($orderIds)) {
                return;
            }
            foreach ($orderIds as $_orderId) {
                $order = Mage::getModel('sales/order')->load($_orderId);
                Mage::getSingleton('core/session')->setWebExtendCustomerEmail($order->getCustomerEmail());
                if ($order->getCustomerId()) {
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
            if ($observer->getEvent()->getControllerAction()->getRequest()->getParams() && $observer->getEvent()->getControllerAction()->getRequest()->getParam('email')) {
                Mage::getSingleton('core/session')->setWebExtendCustomerEmail($observer->getEvent()->getControllerAction()->getRequest()->getParam('email'));
            }

            if ($observer->getEvent()->getControllerAction()->getRequest()->getPost() && $observer->getEvent()->getControllerAction()->getRequest()->getPost('email')) {
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
                $attributes = [];

                foreach ($website as $store) {
                    $attributes = array_merge($attributes, $store['mapped_attributes_names']);
                }

                $attributes = array_unique($attributes);

                /** @var Emarsys_Webextend_Model_Emarsysproductexport $productExportModel */
                $productExportModel = Mage::getModel("webextend/emarsysproductexport");
                $productExportModel->truncateTable();

                $defaultStoreID = false;

                foreach ($website as $storeId => $store) {
                    $currencyStoreCode = $store['store']->getDefaultCurrencyCode();
                    if (!$defaultStoreID) {
                        $defaultStoreID = $store['store']->getWebsite()->getDefaultStore()->getId();
                    }
                    $currentPageNumber = 1;
                    $collection = $productExportModel->getCatalogExportProductCollection(
                        $storeId,
                        $currentPageNumber,
                        $attributes
                    );

                    $lastPageNumber = $collection->getLastPageNumber();
                    $header = $store['emarsys_field_names'];

                    /** @var Mage_Core_Model_App_Emulation $appEmulation */
                    $appEmulation = Mage::getSingleton('core/app_emulation');
                    $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
                    while ($currentPageNumber <= $lastPageNumber) {
                        if ($currentPageNumber != 1) {
                            $collection = $productExportModel->getCatalogExportProductCollection(
                                $storeId,
                                $currentPageNumber,
                                $attributes
                            );
                        }
                        $products = [];
                        /** @var Mage_Catalog_Model_Product $product */
                        foreach ($collection as $product) {
                            $product->setStoreId($storeId);
                            $catIds = $product->getCategoryIds();
                            $categoryNames = $this->getCategoryNames($catIds, $storeId);
                            $products[$product->getId()] = [
                                'entity_id' => $product->getId(),
                                'params' => serialize([
                                    'default_store' => ($storeId == $defaultStoreID) ? $storeId : 0,
                                    'store' => $store['store']->getCode(),
                                    'store_id' => $store['store']->getId(),
                                    'data' => Mage::helper('webextend')->attributeData($store['mapped_attributes_names'], $product, $categoryNames),
                                    'header' => $header,
                                    'currency_code' => $currencyStoreCode,
                                ]),
                            ];
                        }
                        if (!empty($products)) {
                            $productExportModel->saveBulkProducts($products);
                        }
                        $currentPageNumber++;
                    }
                    $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
                }

                if (!empty($store)) {
                    list($csvFilePath, $outputFile) = $productExportModel->saveToCsv($websiteId);
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
                            Mage::helper('webextend')->moveToFTP($storeId, $outputFile, $ftpConnection, $csvFilePath);
                        }
                    }
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
                        $this->_credentials[$websiteId][$storeId]['store'] = $store;
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
        $staticExportArray = Mage::helper('webextend')->getStaticExportArray();
        $staticMagentoAttributeArray = Mage::helper('webextend')->getStaticMagentoAttributeArray();
        $emarsysFieldNames = [];
        $magentoAttributeNames = [];

        //Getting mapped emarsys attribute collection
        $model = Mage::getModel('webextend/emarsysproductattributesmapping');
        $collection = $model->getCollection();
        $collection->addFieldToFilter("store_id", $storeId);
        $collection->addFieldToFilter("emarsys_attribute_code_id", ["neq" => 0]);
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
        $key = $storeId . '-' . serialize($catIds);
        if (!isset($this->_categoryNames[$key])) {
            $this->_categoryNames[$key] = [];
            foreach ($catIds as $catId) {
                $cateData = Mage::getModel("catalog/category")->setStoreId($storeId)->load($catId);
                $categoryPath = $cateData->getPath();
                $categoryPathIds = explode('/', $categoryPath);
                $childCats = [];
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
                    $this->_categoryNames[$key][] = implode(" > ", $childCats);
                }
            }
        }
        return $this->_categoryNames[$key];
    }
}