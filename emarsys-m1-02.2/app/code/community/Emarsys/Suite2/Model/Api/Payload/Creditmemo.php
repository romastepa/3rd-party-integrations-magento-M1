<?php

class Emarsys_Suite2_Model_Api_Payload_Creditmemo extends Emarsys_Suite2_Model_Api_Payload_Order
{
    /**
     * Order
     * 
     * @var Mage_Sales_Model_Order
     */
    protected $_order;
    
    /**
     * Order creditmemo
     * 
     * @var Mage_Sales_Model_Order_Creditmemo
     */
    
    protected $_creditmemo;
    /**
     * @inheritdoc
     */
    public function __construct(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $this->_creditmemo = $creditmemo;
        $this->_order = $creditmemo->getOrder();
        $this->_order->setRefundDate($creditmemo->getCreatedAt());
        $this->_refundMode = true;
        parent::__construct($this->_order);
    }
    
    protected function _generateItems()
    {
        $data = array();
        $refundTotal = 0;
        $storeId = $this->_creditmemo->getStoreId();
        $useBaseCurrency = $this->useBaseCurrency($storeId);
        foreach ($this->_creditmemo->getAllItems() as $item) {
            /* @var $item Mage_Sales_Model_Order_Creditmemo_Item */
            $useBaseCurrency = $this->useBaseCurrency($storeId);
            if ($rowTotal = $useBaseCurrency ? $item->getBaseRowTotalInclTax() : $item->getRowTotalInclTax()) {
                /* @var $item Mage_Sales_Model_Order_Creditmemo_Item */

                if($useBaseCurrency){
                    $discountAmount = $item->getBaseDiscountAmount();
                }else{
                    $discountAmount = $item->getDiscountAmount();
                }
                $data[] = array(
                    'website_id'    => $this->_getConfig()->getWebsiteId(),
                    'order'         => $this->_order->getIncrementId(),
                    'date'          => $this->_creditmemo->getCreatedAt(),
                    'customer'      => $this->_getCustomerId(),
                    'item'          => $item->getSku(),
                    'quantity'      => $item->getQty(),
                    'unit_price'    => $this->_formatPrice($useBaseCurrency ? $item->getBasePriceInclTax() : $item->getPriceInclTax()),
                    'c_sales_amount'=> $this->_formatPrice(-($rowTotal - $discountAmount)),
                );
                $refundTotal += $rowTotal;
            }
        }

        if ($useBaseCurrency ? (float)$this->_creditmemo->getBaseAdjustment() : (float)$this->_creditmemo->getAdjustment()) {
            $data[] = array(
                    'website_id'    => $this->_getConfig()->getWebsiteId(),
                    'order'         => $this->_order->getIncrementId(),
                    'date'          => $this->_creditmemo->getCreatedAt(),
                    'customer'      => $this->_getCustomerId(),
                    'item'          => 0,
                    'quantity'      => 1,
                    'unit_price'    => $this->_formatPrice($useBaseCurrency ? $this->_creditmemo->getBaseAdjustment() : $this->_creditmemo->getAdjustment()),
                    'c_sales_amount'=> $this->_formatPrice($useBaseCurrency ? -$this->_creditmemo->getBaseAdjustment() : -$this->_creditmemo->getAdjustment()),
                );
        }

        return $data;
    }
}