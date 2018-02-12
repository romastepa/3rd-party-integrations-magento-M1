<?php

class Emarsys_Suite2_Model_Api_Payload_Order extends Emarsys_Suite2_Model_Api_Payload_Abstract
{
    /**
     * Order
     * 
     * @var Mage_Sales_Model_Order
     */
    protected $_order;
    protected $_refundMode = false;
    /**
     * @inheritdoc
     */
    public function __construct(Mage_Sales_Model_Order $order)
    {
        $this->_order = $order;
        parent::__construct(array('id' => $order->getId()));
    }
    
    protected function _getCustomerId()
    {
        if ($this->_getConfig()->getSmartinsightSettingsEmailAsId()) {
            return $this->_order->getCustomerEmail();
        } else {
            return $this->_order->getCustomerId();
        }
    }
    
    protected function _generateItems()
    {
        $_bundleIncluded = $this->_getConfig()->getSmartinsightSettingsBundleInclude();
        $_bundlePrice = $this->_getConfig()->getSmartinsightSettingsBundlePriceCalculated();
        if (!$_bundleIncluded) {
            $_bundlePrice = 0; // Must be always off
        }
        $storeId = $this->_order->getStoreId();
        $useBaseCurrency = $this->useBaseCurrency($storeId);
        $customerId = $this->_getCustomerId();
        $orderId = $this->_order->getRealOrderId();
        $data = array();
        foreach ($this->_order->getAllVisibleItems() as $item) {
            /* @var $item Mage_Sales_Model_Order_Item */
            $dataItem = array(
                'website_id'    => $this->_getConfig()->getWebsiteId(),
                'order'         => $orderId,
                'date'          => $this->_order->getCreatedAt(),
                'customer'      => $customerId,
                'item'          => $item->getSku(),
                'quantity'      => $item->getQtyInvoiced(),
                'unit_price'    => $this->_formatPrice($useBaseCurrency? $item->getBasePriceInclTax() : $item->getPriceInclTax()),
                'c_sales_amount'=> $this->_formatPrice($useBaseCurrency? $item->getBaseRowTotalInclTax() - $item->getBaseDiscountAmount() : $item->getRowTotalInclTax() - $item->getDiscountAmount()),
            );
            if ($item->getProductType() == 'bundle') {
                if (!$_bundlePrice) {
                    // calculated bundle price, items must have bundle prices
                    $dataItem['unit_price'] = $this->_formatPrice(0);
                    $dataItem['c_sales_amount'] = $this->_formatPrice(0);
                }

                if ($_bundleIncluded) {
                    $data[] = $dataItem;
                }

                foreach ($this->_order->getItemsCollection()->getItemsByColumnValue('parent_item_id', $item->getId()) as $_item) {
                    $_dataItem = $dataItem;
                    $_dataItem['item'] = $_item->getSku();
                    if ($useBaseCurrency) {
                        $priceIncludingTax = $_item->getBasePriceInclTax();
                    } else {
                        $priceIncludingTax = $_item->getPriceInclTax();
                    }
                    if ($useBaseCurrency) {
                        $rowTotalIncludingTax = $_item->getBaseRowTotalInclTax();
                    } else {
                        $rowTotalIncludingTax = $_item->getRowTotalInclTax();
                    }
                    $_dataItem['unit_price'] = $this->_formatPrice(($_bundlePrice ? 0 :$priceIncludingTax));
                    $_dataItem['c_sales_amount'] = $this->_formatPrice(($_bundlePrice ? 0 :$rowTotalIncludingTax));
                    $data[] = $_dataItem;
                }
            } else {
                $data[] = $dataItem;
            }
        }

        return $data;
    }
    
    /**
     * @inheritdoc
     */
    public function toArray(array $arrAttributes = array())
    {
        return $this->_generateItems();
    }
}