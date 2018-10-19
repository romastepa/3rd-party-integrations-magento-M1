<?php
abstract class Emarsys_Suite2_Model_Api_Abstract extends Varien_Object
{
    protected $_profilerKey = 'abstract';
    protected $_processedEntities = array();
    protected $_cleanEntitiesOnEachWebsiteExport = true;
    
    const BATCH_SIZE = 1000;
    
    /**
     * Returns global module flag
     * 
     * @return boolean
     */
    protected function _isEnabled()
    {
        return $this->_getConfig()->isEnabled();
    }
    
    /**
     * Runs export per website
     * 
     * @param mixed $website
     * 
     * @return \Emarsys_Suite2_Model_Api_Abstract
     */
    protected function _exportWebsiteData($website)
    {
        return $this;
    }
    
    /**
     * Returns export flag
     * 
     * @return boolean
     */
    public function isExportEnabled()
    {
        return false;
    }
    
    protected function _beforeExport()
    {
        return $this;
    }
    
    protected function _afterExport()
    {
        return $this;
    }
    
    protected function _afterExportWebsiteData($website)
    {
        $this->cleanupQueue(array_unique($this->_processedEntities));
        return $this;
    }
    
    /**
     * Exports all websites
     * 
     * @return Emarsys_Suite2_Model_Api_Abstract
     */
    public function export()
    {
        $this->_beforeExport();
        Mage::getSingleton('emarsys_suite2/queue')->setBatchSize(static::BATCH_SIZE);
        foreach (Mage::app()->getWebsites() as $website) {
            if ($this->_cleanEntitiesOnEachWebsiteExport) {
                $this->_processedEntities = array();
            }

            $this->_getConfig()->setWebsite($website);
            $_profilerKey = str_replace('Emarsys_Suite2_Model_', '', get_class($this)) . '(' . $website->getCode() . ')';
            if ($this->_isEnabled() && $this->isExportEnabled()) {
                $this->log('Staring ' . $_profilerKey . ' export.');
                Varien_Profiler::start('EmarsysSuite2::' . $this->_profilerKey);
                $this->_exportWebsiteData($website);
                $this->_afterExportWebsiteData($website);
                Varien_Profiler::stop('EmarsysSuite2::' . $this->_profilerKey);
                $this->log('Finished ' . $_profilerKey . ' export.');
            }
        }

        $this->_afterExport();
        return $this;
    }

    /**
     * Returns API client object
     * 
     * @return Emarsys_Suite2_Model_Api
     */
    public function getClient()
    {
        return Mage::helper('emarsys_suite2')->getClient();
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
     * Returns Emarsys Suite2 helper
     * 
     * @return Emarsys_Suite2_Helper_Data
     */
    public function helper()
    {
        return Mage::helper('emarsys_suite2');
    }
    
    /**
     * Logs line into emarsys.log file
     * 
     * @param mixed $line
     */
    protected function log($line)
    {
        Mage::helper('emarsys_suite2')->log($line, $this);
    }
    
    protected function _profilerStart($code)
    {
        Varien_Profiler::start('EmarsysSuite2::' . $code);
    }
    
    protected function _profilerStop($code)
    {
        Varien_Profiler::stop('EmarsysSuite2::' . $code);
    }
    
    /**
     * Removes entities from queue
     * 
     * @param Emarsys_Suite2_Model_Resource_Queue_Collection $queue
     * @param array $entityIds
     */
    public function cleanupQueue($entityIds)
    {
        if ($entityIds) {
            $this->log('Removing ' . count($entityIds). ' success item(s) from queue.');
            Mage::getSingleton('emarsys_suite2/queue')->setMainEntity($this->_getEntity())->removeEntities($entityIds);
            $this->log('Removed ' . count($entityIds). ' success item(s) from queue.');
        }

        return $this;
    }    
}
