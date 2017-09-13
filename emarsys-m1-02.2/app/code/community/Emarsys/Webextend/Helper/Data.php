<?php

/**
 *
 * @category   Webextend
 * @package    Emarsys_Webextend
 * @copyright  Copyright (c) 2017 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Webextend_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_AJAX_UPDATE_ENABLED   = 'webextendsection/webextendoptions/ajaxupdate';
    const XML_PATH_USE_JQUERY_ENABLED   = 'webextendsection/webextendoptions/usejquery';

    /**
     * Static Array of Required Emarsys attribute Fields
     * @return array
     */
    public function getStaticFieldArray()
    {
        $emarsysRequiredAttributes = array('Item', 'Title', 'Link', 'Image', 'Category', 'Price', 'MSRP', 'Available', 'Brand', 'Description', 'Zoom image');
        return $emarsysRequiredAttributes;
    }

    /**
     * get Static Export Array for Emarsys
     * @return array
     */
    public function getstaticExportArray()
    {
        $staticExportArray = array("Item", "Available", "Title", "Link", "Image", "Category", "Price");
        return $staticExportArray;
    }

    /**
     * get Static Export Array for Magento
     * @return array
     */
    public function getstaticMagentoAttributeArray()
    {
        $staticMagentoAttributeArray = array("sku", "is_saleable", "name", "url_key", "image", "category_ids", "price");
        return $staticMagentoAttributeArray;
    }

    /**
     * Moving File to FTP
     * @param $websiteId
     * @param $outputFile
     * @param $ftpConnection
     * @param $filePath
     */
    public function moveToFTP($websiteId, $outputFile, $ftpConnection, $filePath)
    {
        $bulkDir = Mage::getStoreConfig('emarsys_suite2_smartinsight/ftp/dir', $websiteId);
        $passiveMode = Mage::getStoreConfig('emarsys_suite2_smartinsight/ftp/passive', $websiteId);

        $remoteDirPath = $bulkDir;
        if ($remoteDirPath == '/') {
            $remoteFileName = $outputFile;
        } else {
            $remoteDirPath = rtrim($remoteDirPath, '/');
            $remoteFileName = $remoteDirPath . "/" . $outputFile;
        }
        if ($passiveMode == 1) {
            @ftp_pasv($ftpConnection, true);
        }
        if (!@ftp_chdir($ftpConnection, $remoteDirPath)) {
            @ftp_mkdir($ftpConnection, $remoteDirPath);
        }
        @ftp_chdir($ftpConnection, '/');
        try {
            @ftp_put($ftpConnection, $remoteFileName, $filePath, FTP_ASCII);
            unlink($filePath);
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    public function attributeData($magentoAttributeNames, $productData, $categoryNames)
    {
        try {
            //Get Product attributes
            $attributeData = array();
            foreach ($magentoAttributeNames as $attributeName) {
                if ($attributeName != "category_ids") {
                    $attributeOption = trim($productData->getData($attributeName));
                    if (!is_array($attributeOption)) {
                        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $attributeName);
                        if ($attribute->getFrontendInput() == 'boolean' || $attribute->getFrontendInput() == 'select' || $attribute->getFrontendInput() == 'multiselect') {
                            $attributeOption = $productData->getAttributeText($attributeName);
                        }
                    }
                }
                if (isset($attributeOption) && $attributeOption != '') {
                    if (is_array($attributeOption)) {
                        $attributeData[] = implode(',', $attributeOption);
                    } else {
                        if ($attributeName == 'category_ids') {
                            $attributeData[] = implode('|', $categoryNames);
                        } else if ($attributeName == 'image' || $attributeName == 'small_image' || $attributeName == 'thumbnail' || $attributeName == 'base_image') {
                            $attributeData[] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . "catalog/product" . $attributeOption;
                        } else if ($attributeName == 'url_key') {
                            $attributeData[] = $productData->getProductUrl();
                        } else if ($attributeName == 'price') {
                            $attributeData[] = number_format($productData->getPrice(), 2, '.', '');
                        } else if ($attributeName == 'msrp') {
                            $attributeData[] = number_format($productData->getMsrp(), 2, '.', '');
                        } else {
                            $attributeData[] = $attributeOption;
                        }
                    }
                } else {
                    if ($attributeName == 'url_key') {
                        $attributeData[] = $productData->getProductUrl();
                    } else if ($attributeName == 'is_saleable') {
                        if ($productData->isSaleable())
                            $attributeData[] = 'TRUE';
                        else
                            $attributeData[] = 'FALSE';
                    } else {
                        $attributeData[] = '';
                    }
                }
            }
            return $attributeData;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Check Ajax Update Enbled
     * @param $storeId
     * @return bool
     */
    public function isAjaxUpdateEnabled()
    {
        return (bool)Mage::getStoreConfig(self::XML_PATH_AJAX_UPDATE_ENABLED);
    }

    /**
     * @return bool
     */
    public function useJQuery()
    {
        return (bool)Mage::getStoreConfig(self::XML_PATH_USE_JQUERY_ENABLED);
    }
}