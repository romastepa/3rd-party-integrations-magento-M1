<?php

/**
 *
 * @category   Webextend
 * @package    Emarsys_Webextend
 * @copyright  Copyright (c) 2017 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Webextend_Block_Webextend extends Mage_Core_Block_Template
{
    /**
     * Get Merchant Id from DB
     */
    public function getMerchantId()
    {
        return Mage::getStoreConfig('webextendsection/webextendoptions/merchantid');
    }

    /**
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getWebExtendEnabled()
    {
        return Mage::getStoreConfig('webextendsection/webextendoptions/webextendenabled');
    }

    /**
     * @return string
     * Get logged in customer email
     */
    public function getLoggedInCustomerEmail()
    {
        $loggedInCustomerEmail = '';
        try {
            $customerBy = Mage::getStoreConfig('webextendsection/webextendoptions/identityregistered');
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $customer = Mage::getSingleton('customer/session')->getCustomer();
                if ($customerBy == "customer_id") {
                    $loggedInCustomerEmail = $customer->getEntityId();
                } else {
                    $loggedInCustomerEmail = addslashes($customer->getEmail());
                }
            }
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log('WebExtend_getLoggedInCustomerEmail_Exception: ' . $e->getMessage());
        }
        return $loggedInCustomerEmail;
    }

    /**
     * @return mixed
     * Get all cart items
     */
    public function getAllCartItems()
    {
        return Mage::getModel('checkout/cart')->getQuote()->getAllItems();
    }

    /**
     * @param $id
     * @return Mage_Core_Model_Abstract
     */
    public function getLoadProduct($id)
    {
        return Mage::getModel("catalog/product")->load($id);
    }

    /**
     * @return string
     * get Page Handle
     */
    public function getPageHandle()
    {
        return Mage::app()->getFrontController()->getAction()->getFullActionName();
    }

    /**
     * @return array
     */
    public function getOrderData()
    {
        try {
            $orderIds = Mage::getSingleton('core/session')->getWebExtendNewOrderIds();
            if (empty($orderIds) || !is_array($orderIds)) {
                return false;
            }
            $taxIncluded = Mage::getStoreConfig('webextendsection/webextendoptions/tax_included');
            $useBaseCurrency = Mage::getStoreConfig('webextendsection/webextendoptions/use_base_currency');
            $result = array();
            foreach($orderIds as $_orderId){
                $order = Mage::getModel('sales/order')->load($_orderId);
                $orderData = array();
                foreach ($order->getAllVisibleItems() as $item) {
                    $qty = $item->getQtyOrdered();
                    if($taxIncluded) {
                        $price = $useBaseCurrency? ($item->getBaseRowTotal()  + $item->getBaseTaxAmount()) - $item->getBaseDiscountAmount() : ($item->getRowTotal() + $item->getTaxAmount()) - $item->getDiscountAmount();
                    } else {
                        $price = $useBaseCurrency? $item->getBaseRowTotal() - $item->getBaseDiscountAmount() : $item->getRowTotal() - $item->getDiscountAmount();
                    }
                    $sku = $item->getSku();
                    $orderData[] = "{item: '" . addslashes($sku) . "', price: $price, quantity: $qty}";
                }
                $result[$order->getIncrementId()] = $orderData;
            }

            if(count($result) > 0) {
                Mage::getSingleton('core/session')->setWebExtendNewOrderIds(array());
                return $result;
            }
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log('WebExtend_getOrderData_Exception: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * get Current Category
     * @return string
     */
    public function getCurrentCategory()
    {
        try {
            $category = Mage::registry('current_category');
            if (isset($category) && $category != '') {
                $categoryName = '';
                $categoryPath = $category->getPath();
                $categoryPathIds = explode('/', $categoryPath);
                $childCats = array();
                if (count($categoryPathIds) > 2) {
                    $pathIndex = 0;
                    foreach ($categoryPathIds as $categoryPathId) {
                        if ($pathIndex <= 1) {
                            $pathIndex++;
                            continue;
                        }
                        $childCat = Mage::getModel('catalog/category')->load($categoryPathId);
                        $childCats[] = addslashes($childCat->getName());
                    }
                    $categoryName = implode(" > ", $childCats);
                }
                return $categoryName;
            } else {
                return '';
            }
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * @return string
     */
    public function getCurrentProductSku()
    {
        try {
            $uniqueIdentifier = Mage::getStoreConfig('webextendsection/webextendoptions/uniqueidentifier');
            $product = Mage::registry('current_product');
            if (isset($product) && $product != '') {
                if ($uniqueIdentifier == "product_id")
                    $productIdentifier = $product->getId();
                else
                    $productIdentifier = addslashes($product->getSku());

                return $productIdentifier;
            } else {
                return '';
            }
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Get Search Result
     * @return mixed|string
     */
    public function getSearchResult()
    {
        $q = Mage::app()->getRequest()->getParam('q');
        if ($q != '') {
            return addslashes($q);
        } else {
            return '';
        }
    }

    /**
     * Get Ajax Update Url
     * @return string
     */
    public function getAjaxUpdateUrl()
    {
        return $this->getUrl('emarsys_webextend/index/ajaxupdate', array(
            '_secure'=> true));
    }

    public function getCustomerId()
    {
        try {
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                return $this->getLoggedInCustomerEmail();
            } else {
                $customerId = Mage::getSingleton('core/session')->getWebExtendCustomerId();
                if(!empty($customerId)){
                    Mage::getSingleton('core/session')->setWebExtendCustomerId('');
                    Mage::getSingleton('core/session')->setWebExtendCustomerEmail('');
                    return $customerId;
                }
            }
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log('WebExtend_getCustomerId_Exception: ' . $e->getMessage());
        }
        return false;
    }

    public function getCustomerEmailAddress() {
        $returnEmail = false;
        try {
            $sessionEmail = Mage::getSingleton('core/session')->getWebExtendCustomerEmail();
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $loggedInCustomerEmail = $this->getLoggedInCustomerEmail();
                if (Zend_Validate::is($loggedInCustomerEmail, 'EmailAddress')) {
                    $returnEmail = $loggedInCustomerEmail;
                }
            } elseif (isset($sessionEmail)) {
                if (!empty($sessionEmail) && Zend_Validate::is($sessionEmail, 'EmailAddress')) {
                    $returnEmail = $sessionEmail;
                }
            }
            Mage::getSingleton('core/session')->setWebExtendCustomerEmail('');
            Mage::getSingleton('core/session')->setWebExtendCustomerId('');
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log('WebExtend_getCustomerEmailAddress_Exception: ' . $e->getMessage());
        }
        return $returnEmail;
    }

    public function getCartItemsJsonData()
    {
        $returnData = false;
        try {
            $allItems = $this->getAllCartItems();
            $useBaseCurrency = Mage::getStoreConfig('webextendsection/webextendoptions/use_base_currency');
            if($allItems != "") {
                $jsData = array();
                foreach ($allItems as $item) {
                    if ($item->getParentItemId()) {
                        continue;
                    }
                    $productSku = $this->getLoadProduct($item->getProductId())->getSku();
                    $price = $useBaseCurrency? $item->getBaseRowTotal() : $item->getRowTotal();
                    $qty = $item->getQty();
                    $jsData[] = "{item: '" . addslashes($productSku) . "', price: $price, quantity: $qty}";
                }
                $returnData = implode($jsData, ',');
            }
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log('WebExtend_getCartItemsJsonData_Exception: ' . $e->getMessage());
        }
        return $returnData;
    }

    public function getAdditionalWebExtendCommands()
    {
        $returnVal = false;
        try {
            $data = Mage::getSingleton('core/session')->getAdditionalWebExtendCommands();
            if (!empty($data)) {
                $returnVal = $data;
                Mage::getSingleton('core/session')->setAdditionalWebExtendCommands('');
            }
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log('WebExtend_getAdditionalWebExtendCommands_Exception: ' . $e->getMessage());
        }
        return $returnVal;
    }

}
