<?php

/**
 *
 * @category   Webextend
 * @package    Emarsys_Webextend
 * @copyright  Copyright (c) 2017 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Webextend_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_AJAX_UPDATE_ENABLED = 'webextendsection/webextendoptions/ajaxupdate';
    const XML_PATH_USE_JQUERY_ENABLED = 'webextendsection/webextendoptions/usejquery';

    /**
     * Static Array of Required Emarsys attribute Fields
     *
     * @return array
     */
    public function getStaticFieldArray()
    {
        return array('Item', 'Title', 'Link', 'Image', 'Category', 'Price', 'MSRP', 'Available', 'Brand', 'Description', 'Zoom_image');
    }

    /**
     * get Static Export Array for Emarsys
     *
     * @return array
     */
    public function getStaticExportArray()
    {
        return ["item", "available", "title", "link", "image", "category", "price"];
    }

    /**
     * get Static Export Array for Magento
     *
     * @return array
     */
    public function getStaticMagentoAttributeArray()
    {
        return ["sku", "is_saleable", "name", "url_key", "image", "category_ids", "price"];
    }

    /**
     * Moving File to FTP
     *
     * @param $storeId
     * @param $outputFile
     * @param $ftpConnection
     * @param $filePath
     */
    public function moveToFTP($storeId, $outputFile, $ftpConnection, $filePath)
    {
        $bulkDir = Mage::getStoreConfig('emarsys_suite2_smartinsight/ftp/dir', $storeId);
        $passiveMode = Mage::getStoreConfig('emarsys_suite2_smartinsight/ftp/passive', $storeId);

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
            @unlink($filePath);
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * @param $magentoAttributeNames
     * @param Mage_Catalog_Model_Product $productData
     * @param $categoryNames
     * @return array
     */
    public function attributeData($magentoAttributeNames, $productData, $categoryNames)
    {
        //Get Product attributes
        $attributeData = [];
        foreach ($magentoAttributeNames as $attributeName) {
            try {
                $attributeOption = '';
                if ($attributeName != "category_ids") {
                    $attributeOption = trim($productData->getData($attributeName));
                    if (!is_array($attributeOption)) {
                        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $attributeName);
                        if ($attribute->getFrontendInput() == 'boolean' || $attribute->getFrontendInput() == 'select' || $attribute->getFrontendInput() == 'multiselect') {
                            $attributeOption = $productData->getAttributeText($attributeName);
                        }
                    }
                }
                if (is_array($attributeOption)) {
                    $attributeData[] = implode(',', $attributeOption);
                } else {
                    if ($attributeName == 'category_ids') {
                        $attributeData[] = implode('|', $categoryNames);
                    } elseif ($attributeName == 'image' || $attributeName == 'small_image' || $attributeName == 'thumbnail' || $attributeName == 'base_image') {
                        //$attributeData[] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . "catalog/product" . $attributeOption;
                        $attributeData[] = (string)Mage::helper('catalog/image')->init($productData, $attributeName);
                    } elseif ($attributeName == 'url_key') {
                        $attributeData[] = $productData->getUrlInStore();
                    } elseif ($attributeName == 'price') {
                        $attributeData[] = number_format($productData->getFinalPrice(), 2, '.', '');
                    } elseif ($attributeName == 'msrp') {
                        $attributeData[] = number_format($productData->getMsrp(), 2, '.', '');
                    } elseif ($attributeName == 'is_saleable') {
                        if ($productData->isSaleable()) {
                            $attributeData[] = 'TRUE';
                        } else {
                            $attributeData[] = 'FALSE';
                        }
                    } else {
                        $attributeData[] = $attributeOption;
                    }
                }
            } catch (Exception $e) {
                $attributeData[] = '';
                Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
            }
        }
        return $attributeData;

    }

    /**
     * Check Ajax Update Enbled
     *
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