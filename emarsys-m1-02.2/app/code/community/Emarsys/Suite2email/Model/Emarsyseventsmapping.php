<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Suite2email_Model_Emarsyseventsmapping extends Mage_Core_Model_Abstract
{
    /**
     * Construct
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('suite2email/emarsyseventsmapping');
    }

    /**
     * Create Event mapping first time
     * @param $storeId
     * @throws Exception
     */
    public function insertFirstime($storeId)
    {
        try {
            $collection = Mage::getModel('suite2email/emarsysmagentoevents')->getCollection();

            foreach ($collection as $col) {
                $model = Mage::getModel('suite2email/emarsyseventsmapping');
                $model->setMagentoEventId($col->getId());
                $model->setEmarsysEventId('');
                $model->setStoreId($storeId);
                $model->save();
            }
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Default place holders of emarsys
     * @return mixed
     */
    public function emarsysDefaultPlaceholders()
    {
        try {
            $order['product_purchases']['unitary_price_exc_tax'] = strtoupper("unitary_price_exc_tax");
            $order['product_purchases']['unitary_price_inc_tax'] = strtoupper("unitary_price_inc_tax");
            $order['product_purchases']['unitary_tax_amount'] = strtoupper("unitary_tax_amount");
            $order['product_purchases']['line_total_price_exc_tax'] = strtoupper("line_total_price_exc_tax");
            $order['product_purchases']['line_total_tax_amount'] = strtoupper("line_total_tax_amount");
            $order['product_purchases']['unitary_price_inc_tax'] = strtoupper("unitary_price_inc_tax");
            $order['product_purchases']['product_id'] = strtoupper("product_id");
            $order['product_purchases']['product_type'] = strtoupper("product_type");
            $order['product_purchases']['base_original_price'] = strtoupper("base_original_price");
            $order['product_purchases']['sku'] = strtoupper("sku");
            $order['product_purchases']['product_name'] = strtoupper("product_name");
            $order['product_purchases']['product_weight'] = strtoupper("product_weight");
            $order['product_purchases']['qty_ordered'] = strtoupper("qty_ordered");
            $order['product_purchases']['original_price'] = strtoupper("original_price");
            $order['product_purchases']['price'] = strtoupper("price");
            $order['product_purchases']['base_price'] = strtoupper("base_price");
            $order['product_purchases']['tax_percent'] = strtoupper("tax_percent");
            $order['product_purchases']['tax_amount'] = strtoupper("tax_amount");
            $order['product_purchases']['discount_amount'] = strtoupper("discount_amount");
            $order['product_purchases']['price_line_total'] = strtoupper("price_line_total");
            $order['product_purchases']['_external_image_url'] = strtoupper("_external_image_url");
            $order['product_purchases']['_url'] = strtoupper("_url");
            $order['product_purchases']['_url_name'] = strtoupper("_url_name");
            $order['product_purchases']['product_description'] = strtoupper("product_description");
            $order['product_purchases']['short_description'] = strtoupper("short_description");
            $order['product_purchases']['additional_data'] = strtoupper("additional_data");
            $order['product_purchases']['full_options'] = strtoupper("full_options");

            $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->getItems();

            foreach ($attributes as $attribute) {
                if ($attribute->getAttributecode() == "gallery") continue;
                $order['product_purchases']['attribute_' . $attribute->getAttributecode()] = strtoupper('attribute_' . $attribute->getAttributecode());
            }

            return $order;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}
