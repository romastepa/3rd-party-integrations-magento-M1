<?php
/**
 * @category   Webextend
 * @package    Emarsys_Webextend
 */

class Emarsys_Webextend_Model_Emarsysproductexport extends Mage_Core_Model_Abstract
{
    CONST EMARSYS_DELIMITER = '{EMARSYS}';

    protected $_prepearedData = array();

    protected $_mapHeader = array('Item');

    protected $_processedStores = array();
    /**
     * Init model
     */
    public function _construct()
    {
        $this->_init('webextend/emarsysproductexport');
    }

    /**
     * Get Catalog Product Export Collection
     * @param int|object $storeId
     * @param int $currentPageNumber
     * @param array $attributes
     * @return object
     */
    public function getCatalogExportProductCollection($storeId, $currentPageNumber, $attributes)
    {
        try {
            $storeId = Mage::app()->getStore($storeId)->getId();
            $pageSize = Mage::helper('emarsys_suite2/adminhtml')->getBatchSize();
            $exportProductStatus = Mage::getStoreConfig("catalogexport/configurable_cron/webextenproductstatus", $storeId);
            $exportProductTypes = Mage::getStoreConfig("catalogexport/configurable_cron/webextenproductoptions", $storeId);

            $collection = Mage::getModel('catalog/product')->getCollection();
            $collection->setPageSize($pageSize)
                ->setCurPage($currentPageNumber)
                ->addStoreFilter($storeId)
                ->addAttributeToSelect($attributes);

            Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
            Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);

            //Added collection filter of type ID
            if ($exportProductTypes != "") {
                $explode = explode(",", $exportProductTypes);
                $collection->addAttributeToFilter('type_id', array('in' => $explode));
            }
            //Added status filter
            if ($exportProductStatus == 1) {
                $collection->addAttributeToFilter('status', array('in' => array(
                    Mage_Catalog_Model_Product_Status::STATUS_ENABLED, Mage_Catalog_Model_Product_Status::STATUS_DISABLED
                )));
            } else {
                $collection->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED));
            }
            return $collection;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Save Products to temp table
     *
     * @param array $products
     * @return $this
     */
    public function saveBulkProducts($products)
    {
        $this->getResource()->saveBulkProducts($products);
        return $this;
    }

    /**
     * Truncate a table
     *
     * @return $this
     */
    public function truncateTable()
    {
        $this->getResource()->truncateTable();
        return $this;
    }

    /**
     * Save CSV for Website
     *
     * @param $websiteId
     * @return array
     * @throws Exception
     */
    public function saveToCsv($websiteId)
    {
        $this->_prepareData();

        $io = new Varien_Io_File();

        $path = Mage::getBaseDir('var') . DS . 'export';
        $name = "products_" . date('YmdHis', time()) . "_" . $websiteId . ".csv";
        $file = $path . DS . $name;

        $io->setAllowCreateFolders(true);
        $io->open(array('path' => $path));
        $io->streamOpen($file, 'w+');
        $io->streamLock(true);
        $io->streamWriteCsv($this->_mapHeader);

        foreach ($this->_prepearedData as $item) {
            $io->streamWriteCsv(Mage::helper("core")->getEscapedCSVData($item));
        }

        $io->streamUnlock();
        $io->streamClose();

        return array($file, $name);

    }

    /**
     * Prepare Data for CSV
     *
     * @return array
     */
    protected function _prepareData()
    {
        $pageSize = Mage::helper('emarsys_suite2/adminhtml')->getBatchSize();
        $currentPageNumber = 1;

        $collection = $this->getCollection();
        $collection->setPageSize($pageSize)
            ->setCurPage($currentPageNumber);

        $lastPageNumber = $collection->getLastPageNumber();

        while ($currentPageNumber <= $lastPageNumber) {
            if ($currentPageNumber != 1) {
                $collection->setCurPage($currentPageNumber);
            }
            foreach ($collection as $product) {
                $productId = $product->getId();
                $productData = explode($this::EMARSYS_DELIMITER, $product->getParams());
                foreach ($productData as $param) {
                    $item = unserialize($param);
                    $map = $this->prepareHeader($item['store'], $item['header']);

                    if (!isset($this->_prepearedData[$productId])) {
                        $this->_prepearedData[$productId] = array_fill(0, count($this->_mapHeader), "");
                    }

                    foreach ($item['data'] as $key => $value) {
                        $this->_prepearedData[$productId][$map[$key]] = $value;
                    }
                }
                ksort($this->_prepearedData[$productId]);
            }

            $currentPageNumber++;
        }

        return $this->_prepearedData;
    }

    /**
     * Prepare Global Header and Mapping
     *
     * @param $storeCode
     * @param $header
     * @return mixed
     */
    public function prepareHeader($storeCode, $header)
    {
        if (!array_key_exists($storeCode, $this->_processedStores)) {
            // $this->_processedStores[$storeCode] = array(oldKey, newKey);
            foreach ($header as $key => &$value) {
                if ($value == 'Item') {
                    unset($header[$key]);
                    $this->_processedStores[$storeCode] = array($key => 0);
                    continue;
                }
                $value = $value . '_' . $storeCode;
            }
            $headers = array_flip($header);

            foreach ($headers as $head => $key) {
                $this->_mapHeader[] = $head;
                $renewedHead = array_keys($this->_mapHeader);
                $lastElementKey = array_pop($renewedHead);
                $this->_processedStores[$storeCode][$key] = $lastElementKey;
            }
        }

        return $this->_processedStores[$storeCode];
    }
}