<?php

class Emarsys_Suite2_Model_Email_Template extends Mage_Core_Model_Email_Template
{
    /**
     * @inheritdoc
     */
    public function sendTransactional($templateId, $sender, $email, $name, $vars = array(), $storeId = null)
    {
        $websiteId = 0;
        $this->setSentSuccess(false);
        if (($storeId === null) && $this->getDesignConfig()->getStore()) {
            $storeId = $this->getDesignConfig()->getStore();
        }

        if (is_numeric($templateId)) {
            $this->load($templateId);
        } else {
            $localeCode = Mage::getStoreConfig('general/locale/code', $storeId);
            $this->loadDefault($templateId, $localeCode);
            return parent::sendTransactional($templateId, $sender, $email, $name, $vars, $storeId);
        }

        $event = Mage::getModel('emarsys_suite2/email_event')->getCollection()->addFieldToFilter('template_id', $templateId)->getFirstItem();
        if (!$event->getEventId()) {
            return parent::sendTransactional($templateId, $sender, $email, $name, $vars, $storeId);
        }

        $email_data = array();

        if (!isset($vars['store'])) {
            $vars['store'] = Mage::app()->getStore($storeId);
            $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
        }

        $emails = array_values((array) $email);
        $names = is_array($name) ? $name : (array) $name;
        $names = array_values($names);
        foreach ($emails as $key => $email) {
            if (!isset($names[$key])) {
                $names[$key] = substr($email, 0, strpos($email, '@'));
            }
        }

        $vars['email'] = reset($emails);
        $vars['name'] = reset($names);

        $this->setUseAbsoluteLinks(true);

        $processor = Mage::getModel('emarsys_suite2/email_template_filter');
        $processor->setUseSessionInUrl(false)
                ->setPlainTemplateMode(false);

        if (!$this->_preprocessFlag) {
            $vars['this'] = $this;
        }

        if (isset($vars['subscriber']) && ($vars['subscriber'] instanceof Mage_Newsletter_Model_Subscriber)) {
            $processor->setStoreId($vars['subscriber']->getStoreId());
        }

        if (!isset($vars['logo_url'])) {
            $vars['logo_url'] = $this->_getLogoUrl($processor->getStoreId());
        }

        if (!isset($vars['logo_alt'])) {
            $vars['logo_alt'] = $this->_getLogoAlt($processor->getStoreId());
        }

        $processor->setIncludeProcessor(array($this, 'getInclude'))
                ->setVariables($vars);

        $this->_applyDesignConfig();

        $email_data['global'] = $processor->getEmailData();

        $keyId = Mage::getSingleton('emarsys_suite2/config')->getEmarsysCustomerKeyId();
        if (isset($vars['order'])) {
            $order = array();
            $externalId = $vars['order']->getCustomerId();
            $optionsBySKU = $this->getOptionsBySKU();
            if (!$websiteId) {
                $websiteId = $vars['order']->getWebsiteId();
            }

            //Mage::log("---> " . var_export($optionsBySKU,true),null,'emarsys.log');
            foreach ($vars['order']->getAllVisibleItems() as $item) {
                //Mage::log("itemOptionsString: " . $optionsBySKU[$item->getData('sku')], null,'emarsys.log');
                $order[] = $this->getOrderData($item, $optionsBySKU[$item->getData('sku')]);
            }

            $email_data['product_purchases'] = $order;
        } elseif (isset($vars['customer'])) {
            $externalId = $vars['customer']->getId();
            if (!$websiteId) {
                $websiteId = $vars['customer']->getWebsiteId();
            }
        } elseif (isset($vars['subscriber'])) {
            $keyId = Mage::getSingleton('emarsys_suite2/config')->getEmarsysSubscriberKeyId();
            $externalId = $vars['subscriber']->getId();
            if (!$websiteId) {
                $websiteId = Mage::app()->getStore($vars['subscriber']->getStoreId())->getWebsiteId();
            }
        } else {
            $this->setSentSuccess(parent::sendTransactional($templateId, $sender, $email, $name, $vars, $storeId));
            return $this;
        }
        
        if ($websiteId && !isset($vars['customer'])) {
            // No customer present - create first, using subscriber contact
            if (Mage::getModel('emarsys_suite2/api_customer')->exportEmail($vars['email'], $websiteId)) {
                $this->setSentSuccess(
                    Mage::getSingleton('emarsys_suite2/api_event')->triggerEvent(
                        $event->getData('event_id'),
                        Mage::getSingleton('emarsys_suite2/config')->getEmarsysEmailKeyId(),
                        $vars['email'],
                        $email_data,
                        $websiteId
                    )
                );
                return $this;
            }
        }
        
        $this->setSentSuccess(Mage::getSingleton('emarsys_suite2/api_event')->triggerEvent($event->getData('event_id'), $keyId, $externalId, $email_data, $websiteId));
        return $this;
    }
    
    /**
     * Formats price
     * 
     * @param float $value
     * 
     * @return string
     */
    protected function _formatPrice($value)
    {
        return sprintf('%01.2f', $value);
    }   
    
    private function getOrderData($item, $itemOptions) 
    {
        $optionGlue = " - ";
        $optionSeparator = " : ";
        
        $unitTaxAmount = $item->getTaxAmount() / $item->getQtyOrdered();
        $order = array(
            'unitary_price_exc_tax' => $this->_formatPrice($item->getPriceInclTax() - $unitTaxAmount),
            'unitary_price_inc_tax' => $this->_formatPrice($item->getPriceInclTax()),
            'unitary_tax_amount' => $this->_formatPrice($unitTaxAmount),
            'line_total_price_exc_tax' => $this->_formatPrice($item->getRowTotalInclTax() - $item->getTaxAmount()),
            'line_total_price_inc_tax' => $this->_formatPrice($item->getRowTotalInclTax()),
            'line_total_tax_amount' => $this->_formatPrice($item->getTaxAmount())
        );
        $order['product_id'] = $item->getData('product_id');
        $order['product_type'] = $item->getData('product_type');
        $order['base_original_price'] = $this->_formatPrice($item->getData('base_original_price'));
        $order['sku'] = $item->getData('sku');
        $order['product_name'] = $item->getData('name');
        $order['product_weight'] = $item->getData('weight');
        $order['qty_ordered'] = $item->getData('qty_ordered');
        $order['original_price'] = $this->_formatPrice($item->getData('original_price'));
        $order['price'] = $this->_formatPrice($item->getData('price'));
        $order['base_price'] = $this->_formatPrice($item->getData('base_price'));
        $order['tax_percent'] = $this->_formatPrice($item->getData('tax_percent'));
        $order['tax_amount'] = $this->_formatPrice($item->getData('tax_amount'));
        $order['discount_amount'] = $this->_formatPrice($item->getData('discount_amount'));
        $order['price_line_total'] = $this->_formatPrice($order['qty_ordered'] * $order['price']);


        $_product = Mage::getModel('catalog/product')->load($order['product_id']);
        $base_url = trim(Mage::app()->getStore($item->getData('store_id'))->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB), '/');
        
        $order['_external_image_url'] = $base_url . '/media/catalog/product' . $_product->getData('thumbnail');

        $order['_url'] = $base_url . "/" . $_product->getUrlPath();

        $order['_url_name'] = $order['product_name'];

        $order['product_description'] = $_product->getData('description');
        $order['short_description'] = $_product->getData('short_description');

        $attributes = $_product->getAttributes();
        foreach ($attributes as $attribute)
        {
            if ($attribute->getFrontendInput() != "gallery")
            {
                  $order['attribute_' . $attribute->getAttributeCode()]  = $attribute->getFrontend()->getValue($_product);
            }
        }

        $order['full_options'] = array();
        if ($itemOptions) {
            foreach ($itemOptions as $option) {
                $order['full_options'][] = $option['label'] . $optionSeparator . $option['value'];
            }
        }

        $order['full_options'] = implode($optionGlue, $order['full_options']);
        $order = array_filter($order);
        $order['additional_data'] = ($item->getData('additional_data') ? $item->getData('additional_data') : "");
        return $order;
    }

    private function getOptionsBySKU()
    {
        $optionGlue = " - ";
        $optionSeparator = " : ";
        // Load the session
        $session = Mage::getSingleton('checkout/session');
        // Array to hold the final result
        $optionsBySKU = array();
        // Loop through all items in the cart
        foreach ($session->getQuote()->getAllItems() as $item)
        {
            // Array to hold the item's options
            $attributes_info = array();
            // Load the configured product options
            $options = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
            // Check for options
            if ($options!=null)
            { 
                if (isset($options['attributes_info']))
                { 
                    if (is_array($options['attributes_info']))
                    {
                       $attributes_info = $options['attributes_info'];
                    }
                }
            }

            if (is_array($attributes_info))
            {
                // this item has options
                if (array_key_exists($item->getData('sku'), $optionsBySKU))
                {
                    // there was another item with the same sku with options before (highly unlikely, but better being in the safer side) --> merge them!
                    $optionsBySKU[$item->getData('sku')] = array_merge($optionsBySKU[$item->getData('sku')], $attributes_info);
                }
                else
                {
                    // obviously, set the options
                    $optionsBySKU[$item->getData('sku')] = $attributes_info;
                }
            }
        }

        return $optionsBySKU;
    }
}
 