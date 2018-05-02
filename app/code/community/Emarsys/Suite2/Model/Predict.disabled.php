<?php

class Emarsys_Suite2_Model_Predict extends Varien_Object
{
    const BUNCH_SIZE = 10;
    const SEPARATOR_PATH = ' > ';
    const SEPARATOR_MULTIPATH = ' | ';
    
    protected $_rewrites;
    
    protected $_pageNumber;
    /**
     * @var Mage_Catalog_Model_Resource_Category_Collection
     */
    protected $_categories;
    /**
     * @var Mage_Core_Model_Website
     */
    protected $_website;
    
    protected function _getDefaultCustomerGroupId()
    {
        return Mage::getStoreConfig(Mage_Customer_Model_Group::XML_PATH_DEFAULT_ID, $this->_website->getDefaultStore()->getId());
    }
    /**
     * Returns product availability
     * 
     * @param Mage_Catalog_Model_Product $product
     */
    protected function _getAvailability($product)
    {
        return (int)(
            $product->isInStock() && 
            $product->getStockItem()->getIsInStock()
        );
    }
    /**
     * Returns bunch of products
     * 
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function _getBunch()
    {
        /* @var $bunch Mage_Catalog_Model_Resource_Product_Collection */
        $bunch = Mage::getResourceModel('catalog/product_collection')
            ->setPageSize(static::BUNCH_SIZE)
            ->setCurPage(++$this->_pageNumber)
            ->addWebsiteFilter($this->_website)
            ->addAttributeToSelect(array('name', 'thumbnail'))
            ->setStoreId($this->_website->getDefaultStore()->getId())
            ->addCategoryIds()
            ->addPriceData($this->_getDefaultCustomerGroupId(), $this->_website->getId())
            ->setVisibility(Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds());
        Mage::getResourceSingleton('cataloginventory/stock_status')->addIsInStockFilterToCollection($bunch);
        return $bunch;
    }
    
    /**
     * Gets category data
     * 
     * @param Mage_Catalog_Model_Product $item
     */
    protected function _getCategoryData($item)
    {
        $_categories = array();
        $_deepestCategoryLevel = $_deepestCategoryId = 0;
        
        foreach ($item->getCategoryIds() as $_catId) {
            if ($category = $this->_categories->getItemById($_catId)) {
                $_categories[] = $category->getPredictCategoryPath();
                if ($category->getLevel() > $_deepestCategoryLevel) {
                    $_deepestCategoryId = $category->getId();
                }
            }
        }

        $_categories = implode(static::SEPARATOR_MULTIPATH, $_categories);
        
        $productUrl = 'product/' . $item->getId();
        if ($_deepestCategoryId) {
            $productUrl .= '/' . $_deepestCategoryId;
        }

        $rewrite = $this->_rewrites->getItemByColumnValue('id_path', $productUrl);
        if ($rewrite) {
            $productUrl = $rewrite->getRequestPath();
        } else {
            $productUrl = 'catalog/product/view/id/' . $item->getId();
            if ($_deepestCategoryId) {
                $productUrl .= '/category/' . $_deepestCategoryId;
            }
        }

        $productUrl = $this->_website->getDefaultStore()->getBaseUrl() . $productUrl;
        $item->setPredictProductUrl($productUrl);
        $item->setPredictCategory($_categories);
    }
    
    /**
     * Returns data row
     * 
     * @param Mage_Catalog_Model_Product $item
     * 
     * @return array
     */
    protected function _getItemData($item)
    {
        $thumbnailUrl = Mage::helper('catalog/image')->init($item, 'thumbnail')->resize(75, 75)->__toString();
        $this->_getCategoryData($item);
        $data = array(
            'item'          => $item->getId(), // or sku ?
            'available'     => $this->_getAvailability($item),
            'title'         => $item->getName(),
            'link'          => $item->getPredictProductUrl(),
            'image'         => $thumbnailUrl,
            'category'      => $item->getPredictCategory(),
            'price'         => $item->getFinalPrice(),
            'msrp'          => $item->getPrice(),
        );
        return $data;
    }
    
    /**
     * Exports website data
     * 
     * @param Mage_Core_Model_Website $website
     */
    protected function _exportWebsite($website)
    {
        $io = new Varien_Io_File();
        $this->_website = $website;
        $this->_pageNumber = 0;
        $headerAdded = false;
        
        $dir = 'media/emarsys/predict/' . $website->getCode() . '/';
        $filename = 'catalog.csv';
        $io->mkdir($dir, 0777, true);
        $io->streamOpen($dir . $filename, 'w');
        while (($bunch = $this->_getBunch())) {
            $this->_rewrites = Mage::getResourceModel('core/url_rewrite_collection')
                    ->addFieldToFilter('product_id', $bunch->getAllIds())
                    ->addFieldToFilter('store_id', $website->getDefaultStore()->getId());
            foreach ($bunch as $item) {
                $data = $this->_getItemData($item);
                if (!$headerAdded) {
                    $io->streamWriteCsv(array_keys($data));
                    $headerAdded = true;
                }

                $io->streamWriteCsv($data);
            }

            if ($bunch->count() < static::BUNCH_SIZE) {
                break;
            }
        }

        $io->streamClose();
    }
    
    /**
     * Exports whole data for all websites
     */
    public function export()
    {
        /* @var $tree Mage_Catalog_Model_Resource_Category_Tree */
        $this->_prepareCategoryTree();
        foreach (Mage::app()->getWebsites() as $website) {
            $this->_exportWebsite($website);
        }
    }
    
    public function _prepareCategoryTree()
    {
        $this->_categories = Mage::getResourceModel('catalog/category_collection')->addAttributeToSelect('name')->addAttributeToFilter('is_active', 1);
        $this->_categories->load();
        foreach ($this->_categories as $category) {
            $path = explode('/', $category->getPath());
            array_shift($path);
            array_shift($path);
            if (!empty($path)) {
                $result = array();
                foreach ($path as $catId) {
                    $result[] = $this->_categories->getItemById($catId)->getName();
                }

                $this->_categories->getItemById($catId)->setPredictCategoryPath(implode(static::SEPARATOR_PATH, $result));
            }
        }
    }
}
