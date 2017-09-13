<?php

class Emarsys_Suite2_Model_Email_Template_Filter extends Mage_Core_Model_Email_Template_Filter
{
    /**
     * Returns email data
     * 
     * @return array
     */
    public function getEmailData()
    {
        $emarsys_variable_mapping = Mage::getSingleton('emarsys_suite2/email_var')->getMapping();
        $email_data = array();
        $value = implode(';', array_keys($emarsys_variable_mapping));

        // "depend" and "if" operands should be first
        foreach (array(self::CONSTRUCTION_DEPEND_PATTERN => 'dependDirective', self::CONSTRUCTION_IF_PATTERN => 'ifDirective') as $pattern => $directive) {
            if (preg_match_all($pattern, $value, $constructions, PREG_SET_ORDER)) {
                foreach ($constructions as $index => $construction) {
                    $replacedValue = '';
                    $callback = array($this, $directive);
                    if (!is_callable($callback)) {
                        continue;
                    }

                    try {
                        $replacedValue = call_user_func($callback, $construction);
                    } catch (Exception $e) {
                        throw $e;
                    }

                    if ($replacedValue && (!is_object($replacedValue)) && (key_exists($construction[0], $emarsys_variable_mapping))) {
                        $email_data[$emarsys_variable_mapping[$construction[0]]] = $replacedValue;
                    }
                }
            }
        }

        if (preg_match_all(self::CONSTRUCTION_PATTERN, $value, $constructions, PREG_SET_ORDER)) {
            foreach ($constructions as $index => $construction) {
                $replacedValue = '';
                $callback = array($this, $construction[1] . 'Directive');
                if (!is_callable($callback)) {
                    continue;
                }

                try {
                    $replacedValue = call_user_func($callback, $construction);
                } catch (Exception $e) {
                    throw $e;
                }

                if ($replacedValue && (!is_object($replacedValue)) && (key_exists($construction[0], $emarsys_variable_mapping))) {
                    $email_data[$emarsys_variable_mapping[$construction[0]]] = $replacedValue;
                }
            }
        }
        
        // checking the emarsys variables.
        // if one is not set (as for instance shipping_costs (see CNCT-212), then it will set it as "___Not Applicable___"
        foreach ($emarsys_variable_mapping as $magento_code => $emarsys_name)
        {
            if (!key_exists($emarsys_name, $email_data))
            {
                //Mage::log("The placeholder $emarsys_name was not generated",null,"emarsys.log");
                $email_data[$emarsys_name] = "";
            }
        }

        // GROUP of placeholders that are not easy to build with the table emarsys_var:
        // currency_code
        //$email_data["currency_code"] = Mage::app()->getStore()->getCurrentCurrencyCode();
        // currency_symbol ()
        if (isset($email_data["currency_code"]))
        {
             $value = Mage::app()->getLocale()->currency($email_data["currency_code"])->getSymbol();
        }
        else
        {
             $value = "";
        }

        $email_data["currency_symbol"] = $value;
        // GET the total taxes amoung from $email_data["order_tax_info"], that should be created from the table emarsys_vars [order.getFullTaxInfo()]
        if (is_array($email_data["order_tax_info"]))
        {
            $email_data["total_tax_amount"] = 0;
            foreach($email_data["order_tax_info"] as $tax_group)
            {
                 $email_data["total_tax_amount"] = $email_data["total_tax_amount"] + $tax_group["amount"];
            }

            unset($email_data["order_tax_info"]);
        }
        else
        {
            $email_data["total_tax_amount"] = "";
        }

        unset($email_data["order_tax_info"]);
        // end of group

        if (isset($this->_templateVars['product_image'])) {
            $base_url = Mage::app()->getStore($this->_storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
            $base_url = (substr($base_url, -1) == '/') ? substr($base_url, 0, -1) : $base_url;
            $email_data['_external_image_url'] = $this->_templateVars['product_image']->__toString();
        }

        if (isset($this->_templateVars['product_url'])) {
            $email_data['_url'] = $this->_templateVars['product_url'];
            $email_data['_url_name'] = $email_data['product_name'];
        }

        return $email_data;
    }
} 