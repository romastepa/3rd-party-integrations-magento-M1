<?php

abstract class Emarsys_Suite2_Model_Api_Payload_Abstract extends Varien_Object
{
    const FORMAT_TIME = 'YYYY-MM-dd HH:mm:ss';
    const FORMAT_DATE = 'YYYY-MM-dd';
    
    /**
     * Formats price
     * 
     * @param float $value
     * 
     * @return string
     */
    protected function _formatPrice($value)
    {
        return Mage::helper('emarsys_suite2')->formatPrice($value);
    }
    
    /**
     * Formats datetime
     * 
     * @param type $value
     * @return type
     */
    protected function _formatDatetime($value)
    {
        $dt = new Zend_Date($value);
        return $dt->toString(static::FORMAT_TIME);
    }
    
    /**
     * Formats date
     * 
     * @param type $value
     * @return type
     */
    protected function _formatDate($value)
    {
        $dt = new Zend_Date($value);
        return $dt->toString(static::FORMAT_DATE);
    }
    
    /**
     * Returns config object
     * 
     * @return Emarsys_Suite2_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('emarsys_suite2/config');
    }
    
    /**
     * Converts timezone
     * 
     * @param type $datetime
     * @param type $format
     * @return type
     */
    protected function _convertTimezone($datetime, $format='Y-m-d H:i:s') 
    {

        $datetimeConverted = NULL;
        
        if ($datetime) {
            $dateTimestamp = Mage::getModel('core/date')->timestamp(strtotime($datetime));
            $datetimeConverted = date($format, $dateTimestamp);
        }        
        
        return $datetimeConverted;
    }

    public function useBaseCurrency($storeId){

        return Mage::getStoreConfig('emarsys_suite2_smartinsight/settings/use_base_currency',$storeId);
    }
}