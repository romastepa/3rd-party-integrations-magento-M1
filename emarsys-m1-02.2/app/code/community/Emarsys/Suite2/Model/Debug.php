<?php

class Emarsys_Suite2_Model_Debug
{
    protected $_debugInfo = array();
    
    public function debug($apiCall, $apiMethod, $data)
    {
        if (!array_key_exists($apiCall, $this->_debugInfo)) {
            $this->_debugInfo[$apiCall] = array();
        }

        if (!array_key_exists($apiMethod, $this->_debugInfo[$apiCall])) {
            $this->_debugInfo[$apiCall][$apiMethod] = array();
        }

        $this->_debugInfo[$apiCall][$apiMethod][] = $data;
    }
    
    public function clear()
    {
        $this->_debugInfo = array();
    }
    
    public function get($key = null)
    {
        if ($key) {
            return (isset($this->_debugInfo[$key]) ? $this->_debugInfo[$key] : array());
        } else {
            return $this->_debugInfo;
        }
    }
}