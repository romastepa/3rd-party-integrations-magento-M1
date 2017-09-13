<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Suite2email_Model_Email_Template extends Mage_Core_Model_Email_Template
{
    /**
     * @inheritdoc
     */
    public function sendTransactional($templateId, $sender, $email, $name, $vars = array(), $storeId = null)
    {
        try {
            if (!Mage::getStoreConfig('emarsys_suite2_transmail/settings/enabled', $storeId)) {
                return Mage_Core_Model_Email_Template::sendTransactional($templateId, $sender, $email, $name, $vars, $storeId);
            }

            $websiteId = 0;
            $this->setSentSuccess(false);
            if (($storeId === null) && $this->getDesignConfig()->getStore()) {
                $storeId = $this->getDesignConfig()->getStore();
            }

            if ($storeId === null) {
                $storeId = Mage::app()->getStore()->getStoreId();
            }

            if (is_numeric($templateId)) {
                $this->load($templateId);
            } else {
                $localeCode = Mage::getStoreConfig('general/locale/code', $storeId);
                $this->loadDefault($templateId, $localeCode);
            }

            /* Get Magento Event ID by the Email template config path */
            $meventId = $this->getMagentoEventIdByEmailTemplate($templateId, $storeId);
            if (empty($meventId)) {
                return Mage_Core_Model_Email_Template::sendTransactional($templateId, $sender, $email, $name, $vars, $storeId);
            }

            /* Get Emarsys Event ID by the Magento Event ID */
            $emarsysEventId = $this->getEmarsysEventIdByEmailTemplate($meventId, $storeId);
            if (empty($emarsysEventId)) {
                return Mage_Core_Model_Email_Template::sendTransactional($templateId, $sender, $email, $name, $vars, $storeId);
            }

            $email_data = array();

            if (!isset($vars['store'])) {
                $vars['store'] = Mage::app()->getStore($storeId);
                $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
            }

            $emails = array_values((array)$email);
            $names = is_array($name) ? $name : (array)$name;
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

            $email_data['global'] = array();

            $keyId = Mage::getSingleton('emarsys_suite2/config')->getEmarsysCustomerKeyId();
            if (isset($vars['order'])) {
                $order = array();
                $externalId = $vars['order']->getCustomerId();
                $optionsBySKU = $this->getOptionsBySKU($vars['order']);
                if (!$websiteId) {
                    $websiteId = $vars['order']->getWebsiteId();
                }

                foreach ($vars['order']->getAllVisibleItems() as $item) {
                    if (isset($optionsBySKU[$item->getData('sku')])) {
                        $order[] = $this->getOrderData($item, $optionsBySKU[$item->getData('sku')]);
                    } else {
                        $order[] = $this->getOrderData($item, array());
                    }
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
            }

            /* Get Header Template placeholders  */
            $headerPlaceholdersData = $this->getHeaderTemplatePlaceholdersArray($vars, $storeId);
            foreach ($headerPlaceholdersData as $key => $headerPlaceholderValue) {
                $email_data['global'][$key] = $headerPlaceholderValue;
            }

            /* Get mapping placeholders by Magento Event ID  */
            $mappedPlaceholdersData = $this->getMappingPlaceholdersArray($vars, $meventId, $storeId);
            foreach ($mappedPlaceholdersData as $key => $mappedPlaceholdersValue) {
                $email_data['global'][$key] = $mappedPlaceholdersValue;
            }

            /* Get Footer Template placeholders  */
            $footerPlaceholdersData = $this->getFooterTemplatePlaceholdersArray($vars, $storeId);
            foreach ($footerPlaceholdersData as $key => $footerPlaceholderValue) {
                $email_data['global'][$key] = $footerPlaceholderValue;
            }

            if ($websiteId && !isset($vars['customer'])) {
                // No customer present - create first, using subscriber contact
                if (Mage::getModel('emarsys_suite2/api_customer')->exportEmail($vars['email'], $websiteId)) {
                    $this->setSentSuccess(
                        Mage::getSingleton('emarsys_suite2/api_event')->triggerEvent(
                            $emarsysEventId, //$event->getData('event_id'),
                            Mage::getSingleton('emarsys_suite2/config')->getEmarsysEmailKeyId(),
                            $vars['email'],
                            $email_data,
                            $websiteId
                        )
                    );
                    return $this;
                }
            }

            $this->setSentSuccess(Mage::getSingleton('emarsys_suite2/api_event')->triggerEvent($emarsysEventId, $keyId, $externalId, $email_data, $websiteId));

            return $this;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
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
        try {
            $value = sprintf('%01.2f', $value);
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }

        return $value;
    }

    private function getOrderData($item, $itemOptions)
    {
        try {
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
            $order['qty_ordered'] = number_format($item->getData('qty_ordered'));
            $order['original_price'] = $this->_formatPrice($item->getData('original_price'));
            $order['price'] = $this->_formatPrice($item->getData('price'));
            $order['base_price'] = $this->_formatPrice($item->getData('base_price'));
            $order['tax_percent'] = $this->_formatPrice($item->getData('tax_percent'));
            $order['tax_amount'] = $this->_formatPrice($item->getData('tax_amount'));
            $order['discount_amount'] = $this->_formatPrice($item->getData('discount_amount'));
            $order['price_line_total'] = $this->_formatPrice($order['qty_ordered'] * $order['price']);


            $_product = Mage::getModel('catalog/product')->load($order['product_id']);
            $order['_external_image_url'] = (string)Mage::helper('catalog/image')->init($_product, 'thumbnail');

            $order['_url'] = $_product->getUrlInStore();

            $order['_url_name'] = $order['product_name'];

            $order['product_description'] = $_product->getData('description');
            $order['short_description'] = $_product->getData('short_description');

            $attributes = $_product->getAttributes();
            foreach ($attributes as $attribute) {
                if ($attribute->getFrontendInput() != "gallery") {
                    if($attribute->getFrontendInput()=='price'){
                       $order['attribute_' . $attribute->getAttributeCode()] = $this->_formatPrice($attribute->getFrontend()->getValue($_product));
                    }else{
                       $order['attribute_' . $attribute->getAttributeCode()] = $attribute->getFrontend()->getValue($_product);
                    }
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
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Getting simple products by sku
     * @return array
     */
    private function getOptionsBySKU($order)
    {
        try {
            $optionGlue = " - ";
            $optionSeparator = " : ";
            // Load the session
            $session = Mage::getSingleton('checkout/session');
            // Array to hold the final result
            $optionsBySKU = array();
            // Loop through all items in the cart
            foreach ($order->getAllItems() as $item){
                // Array to hold the item's options
                $attributes_info = array();
                // Load the configured product options
                $options = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
                // Check for options
                if ($options != null) {
                    if (isset($options['attributes_info'])) {
                        if (is_array($options['attributes_info'])) {
                            $attributes_info = $options['attributes_info'];
                        }
                    }
                }

                if (is_array($attributes_info)) {
                    // this item has options
                    if (array_key_exists($item->getData('sku'), $optionsBySKU)) {
                        // there was another item with the same sku with options before (highly unlikely, but better being in the safer side) --> merge them!
                        $optionsBySKU[$item->getData('sku')] = array_merge($optionsBySKU[$item->getData('sku')], $attributes_info);
                    } else {
                        // obviously, set the options
                        $optionsBySKU[$item->getData('sku')] = $attributes_info;
                    }
                }
            }

            return $optionsBySKU;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Getting magento event id from email template
     * @param string $templateId
     * @return string
     */
    public function getMagentoEventIdByEmailTemplate($templateId = '', $storeId = 0)
    {
        try {
            if (empty($templateId)) {
                return;
            }

            $meventId = '';
            $collection = Mage::getModel('suite2email/emarsysmagentoevents')->getCollection();
            foreach ($collection as $magEvent) {
                if (Mage::getStoreConfig($magEvent->getConfigPath(), $storeId) == $templateId) {
                    $meventId = $magEvent->getId();
                    break;
                }
            }

            return $meventId;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
    /*
     * Getting Emarsys event id from email teplate
     */
    public function getEmarsysEventIdByEmailTemplate($meventId = '', $storeId = 0)
    {
        try {
            if (empty($meventId)) {
                return;
            }

            $eeventId = '';
            if (empty($meventId)) {
                return;
            } else {
                $eEvent = Mage::getModel('suite2email/emarsyseventsmapping')->getCollection()
                    ->addFieldToFilter("store_id", $storeId)
                    ->addFieldToFilter("magento_event_id", $meventId)
                    ->getFirstItem();
                if ($eEvent->getId()) {
                    $eeventId = $eEvent->getEmarsysEventId();
                }
            }

            $emarsysEventId = '';
            if (empty($eeventId)) {
                return;
            } else {
                $emarsysEvent = Mage::getModel('suite2email/emarsysevents')->load($eeventId);
                if ($emarsysEvent->getId()) {
                    $emarsysEventId = $emarsysEvent->getEventId();
                }
            }

            if (empty($emarsysEventId)) {
                return;
            }

            return $emarsysEventId;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Getting header templates placeholders
     * @param $vars
     * @param $storeId
     * @return array
     */
    public function getHeaderTemplatePlaceholdersArray($vars, $storeId)
    {
        try {
            $returnArray = array();
            $mEvent = Mage::getModel('suite2email/emarsysmagentoevents')->getCollection()
                ->addFieldToFilter("config_path", 'design/email/header')
                ->getFirstItem();
            if ($meventId = $mEvent->getId()) {
                $returnArray = $this->getMappingPlaceholdersArray($vars, $meventId, $storeId);
            }

            return $returnArray;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Getting footer templates placeholders
     * @param $vars
     * @param $storeId
     * @return array
     */
    public function getFooterTemplatePlaceholdersArray($vars, $storeId)
    {
        try {
            $returnArray = array();
            $mEvent = Mage::getModel('suite2email/emarsysmagentoevents')->getCollection()
                ->addFieldToFilter("config_path", 'design/email/footer')
                ->getFirstItem();
            if ($meventId = $mEvent->getId()) {
                $returnArray = $this->getMappingPlaceholdersArray($vars, $meventId, $storeId);
            }

            return $returnArray;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Getting mapping placeholders
     * @param $vars
     * @param $meventId
     * @param $storeId
     * @return array
     */
    public function getMappingPlaceholdersArray($vars, $meventId, $storeId)
    {
        try {
            $returnArray = array();
            $eEvent = Mage::getModel('suite2email/emarsyseventsmapping')->getCollection()
                ->addFieldToFilter("store_id", $storeId)
                ->addFieldToFilter("magento_event_id", $meventId)
                ->getFirstItem();
            if ($mappingId = $eEvent->getId()) {
                $returnArray = $this->_getMappingPlaceholdersArray($vars, $mappingId, $storeId);
                if (count($returnArray) == 0) {
                    Mage::getModel('suite2email/emarsysplaceholdermapping')->insertFirstime($mappingId, $storeId);
                    $returnArray = $this->_getMappingPlaceholdersArray($vars, $mappingId, $storeId);
                }
            }

            return $returnArray;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * @param $vars
     * @param $meventId
     * @param $storeId
     * @return array
     * @throws Exception
     */
    public function _getMappingPlaceholdersArray($vars, $meventId, $storeId)
    {
        try {
            $returnArray = array();

            $collection = Mage::getModel('suite2email/emarsysplaceholdermapping')->getCollection()
                ->addFieldToFilter("event_mapping_id", $meventId)
                ->addFieldToFilter("store_id", $storeId);

            foreach ($collection as $placeHolders) {
                $_variable = $placeHolders->getMagentoPlaceholderName();
                if (strstr($placeHolders->getMagentoPlaceholderName(), "{{if")) {
                    $_variable = $_variable . "1{{/if}}";
                } elseif (strstr($placeHolders->getMagentoPlaceholderName(), "{{depend")) {
                    $_variable = $_variable . "1{{/depend}}";
                }

                $returnArray[$placeHolders->getEmarsysPlaceholderName()] = $this->getProcessedVariable($vars, $_variable);
            }

            return $returnArray;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Process email variable
     *
     * @param   array $variables
     * @return  string
     */
    public function getProcessedVariable(array $variables = array(), $placeholder = ' ')
    {
        try {
            if (empty($placeholder)) {
                return;
            }

            $processor = $this->getTemplateFilter();
            $processor->setUseSessionInUrl(false)
                ->setPlainTemplateMode($this->isPlain());

            if (!$this->_preprocessFlag) {
                $variables['this'] = $this;
            }

            if (isset($variables['subscriber']) && ($variables['subscriber'] instanceof Mage_Newsletter_Model_Subscriber)) {
                $processor->setStoreId($variables['subscriber']->getStoreId());
            }

            if (!isset($variables['logo_url'])) {
                $variables['logo_url'] = $this->_getLogoUrl($processor->getStoreId());
            }

            if (!isset($variables['logo_alt'])) {
                $variables['logo_alt'] = $this->_getLogoAlt($processor->getStoreId());
            }

            // Populate the variables array with store, store info, logo, etc. variables
            if ((Mage::getEdition() == 'Community' && version_compare(Mage::getVersion(),'1.9.0.1', '>=')) || version_compare(Mage::getVersion(),'1.14.0.1', '>=')) {
            $variables = $this->_addEmailVariables($variables, $processor->getStoreId());
            }
            $processor->setIncludeProcessor(array($this, 'getInclude'))
                ->setVariables($variables);

            $this->_applyDesignConfig();

            try {
                $processedResult = $processor->filter($placeholder);
            } catch (Exception $e) {
                $this->_cancelDesignConfig();
                throw $e;
            }

            $this->_cancelDesignConfig();
            return $processedResult;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}
 
