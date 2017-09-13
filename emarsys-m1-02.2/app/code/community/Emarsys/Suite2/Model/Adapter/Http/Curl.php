<?php

class Emarsys_Suite2_Model_Adapter_Http_Curl extends Varien_Http_Adapter_Curl
{
    /**
     * @inheritdoc
     */
    public function write($method, $url, $http_ver = '1.1', $headers = array(), $body = '')
    {
        // fixes inability of Varien_Http_Adapter_Curl to PUT
        if ($method == Zend_Http_Client::PUT) {
            $options = array(CURLOPT_CUSTOMREQUEST => Zend_Http_Client::PUT, CURLOPT_POSTFIELDS => $body);
            curl_setopt_array($this->_getResource(), $options);
        }

        return parent::write($method, $url, $http_ver, $headers, $body);;
    }
}
