<?php
/**
 * @category   Webextend
 * @package    Emarsys_Webextend
 */

class Emarsys_Webextend_Model_Emarsysproductexport extends Mage_Core_Model_Abstract
{
    CONST EMARSYS_DELIMITER = '{EMARSYS}';

    protected $_preparedData = array();

    protected $_mapHeader = array('item');

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

            /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
            $collection = Mage::getModel('catalog/product')->getCollection();
            $collection->setPageSize($pageSize)
                ->setCurPage($currentPageNumber)
                ->addStoreFilter($storeId)
                ->addAttributeToSelect($attributes)
                ->addAttributeToSelect('visibility');

            $collection->joinField(
                'inventory_in_stock',
                'cataloginventory/stock_item',
                'is_in_stock',
                'product_id=entity_id',
                null,
                'left'
            );

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
        $this->_mapHeader = array('item');
        $this->_preparedData = array();
        $this->_prepareData();

        $io = new Varien_Io_File();

        $path = Mage::getBaseDir('var');
        $name = "products_" . $websiteId . ".csv";
        $file = $path . DS . $name;

        $io->setAllowCreateFolders(true);
        $io->open(array('path' => $path));
        $io->streamOpen($file, 'w+');
        $io->streamLock(true);
        $io->streamWriteCsv($this->_mapHeader);

        $columnCount = count($this->_mapHeader);
        $emptyArray = array_fill(0, $columnCount, "");

        foreach ($this->_preparedData as &$item) {
            if (count($item) < $columnCount) {
                $item = $item + $emptyArray;
            }
            $io->streamWriteCsv(Mage::helper("core")->getEscapedCSVData($item));
        }

        $io->streamUnlock();
        $io->streamClose();

        return array($file, $name);
    }

    /**
     * Prepare Data for CSV
     *
     * @throws Exception
     * @throws Mage_Core_Model_Store_Exception
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
                    $map = $this->prepareHeader(
                        $item['store'],
                        $item['header'],
                        $item['default_store'],
                        $item['currency_code']
                    );

                    if (!isset($this->_preparedData[$productId])) {
                        $this->_preparedData[$productId] = array_fill(0, count($this->_mapHeader), "");
                    } else {
                        $processed = $this->_preparedData[$productId];
                        $this->_preparedData[$productId] = array_fill(0, count($this->_mapHeader), "");
                        $this->_preparedData[$productId] = $processed + $this->_preparedData[$productId];
                    }

                    foreach ($item['data'] as $key => $value) {
                        if (isset($this->_mapHeader[$map[$key]]) &&
                            ($this->_mapHeader[$map[$key]] == 'price_' . $item['currency_code']
                                || $this->_mapHeader[$map[$key]] == 'msrp_' . $item['currency_code']
                            )) {
                            $value = Mage::app()->getStore($item['store_id'])->getBaseCurrency()->convert($value, $item['currency_code']);
                        }
                        $this->_preparedData[$productId][$map[$key]] = $value;
                    }
                }
                ksort($this->_preparedData[$productId]);
            }

            $currentPageNumber++;
        }

        return $this->_preparedData;
    }

    /**
     * Prepare Global Header and Mapping
     *
     * @param string $storeCode
     * @param array $header
     * @param bool $isDefault
     * @param string $currencyCode
     * @return mixed
     */
    public function prepareHeader($storeCode, $header, $isDefault = false, $currencyCode)
    {
        if (!array_key_exists($storeCode, $this->_processedStores)) {
            // $this->_processedStores[$storeCode] = array(oldKey => newKey);
            $this->_processedStores[$storeCode] = array();
            foreach ($header as $key => &$value) {
                if (strtolower($value) == 'item') {
                    unset($header[$key]);
                    $this->_processedStores[$storeCode][$key] = 0;
                    continue;
                }

                if (!$isDefault) {
                    if (strtolower($value) == 'price' || strtolower($value) == 'msrp') {
                        $newValue = $value . '_' . $currencyCode;
                        $existingKey = array_search($newValue, $this->_mapHeader);
                        if ($existingKey) {
                            unset($header[$key]);
                            $this->_processedStores[$storeCode][$key] = $existingKey;
                            continue;
                        } else {
                            $value = $newValue;
                        }
                    } else {
                        $value = $value . '_' . $storeCode;
                    }
                }
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