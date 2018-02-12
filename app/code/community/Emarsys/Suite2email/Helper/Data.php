<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Suite2email_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Getting order status and returning as an array.
     *
     * @param $websiteCode
     * @return array
     */
    public function getOrderStatuses($websiteCode)
    {
        try {
            $website = Mage::app()->getWebsite($websiteCode);
            $orderStatuses = $website->getConfig('emarsys_suite2_smartinsight/statuses_selection/order_export_status');
            return explode(",", $orderStatuses);
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Get the Substring with start and end expression
     *
     * @param $haystack
     * @param string $start
     * @param string $end
     * @return bool|string
     */

    public function substringBetween($haystack, $start = "{{", $end = "}}")
    {
        try {
            if (strpos($haystack, $start) === false || strpos($haystack, $end) === false) {
                return false;
            } else {
                $start_position = strpos($haystack, $start) + strlen($start);
                $end_position = strpos($haystack, $end);
                $string = substr($haystack, $start_position, $end_position - $start_position);
                return $start . $string . $end;
            }
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Getting placeholders from template
     *
     * @param string $variable
     * @return string|void
     */
    public function getPlaceholderName($variable = '')
    {
        try {
            if (empty($variable)
                || in_array($variable, array("{{/if}}", "{{/depend}}", "{{else}}", "{{var non_inline_styles}}"))
                || strstr($variable, 'inlinecss')
            ) {
                return;
            }

            $findReplace = array(
                " "     => "_",
                ".get"  => "_",
                "."     => "_",
                "{{"    => "_",
                "}}"    => "_",
                "()"    => "_",
                "("     => "_",
                ")"     => "_",
                "=$"    => "_",
                "="     => "_",
                "/"     => "_",
                "$"     => "_",
                "|"     => "_",
                "___"   => "_",
                "__"    => "_",
                "'"     => "",
                '"'     => "",
                "var"   => "",
            );

            $emarsysVariable = str_replace(array_keys($findReplace), $findReplace, strtolower($variable));

            return trim(trim($emarsysVariable, "_"));
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Getting magento readonly events.
     */
    public function getReadonlyMagentoEventIds()
    {
        try {
            return array(1, 2);
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Checking readonly magento events
     *
     * @param int $id
     * @return bool
     */
    public function isReadonlyMagentoEventId($id = 0)
    {
        try {
            if (in_array($id, $this->getReadonlyMagentoEventIds())) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Returning default store id.
     *
     * @return int|mixed
     */
    public function getDefaultStoreID()
    {
        try {
            return Mage::app()->getWebsite(true)->getDefaultGroup()->getDefaultStoreId();
        } catch (Exception $e) {
            return 0;
        }
    }

}
