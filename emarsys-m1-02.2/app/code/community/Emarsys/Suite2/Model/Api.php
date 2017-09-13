<?php
/**
 *  @method Emarsys_Suite2_Model_Adapter_Http_Curl getAdapter()
 */
class Emarsys_Suite2_Model_Api extends Varien_Http_Client
{
    protected $_apiUrl;
    protected $_username;
    protected $_password;
    protected $_debug;
    protected $_debugInfo = array();
    
    const API_ERROR_CONNECTION = 'Connection error';
    const API_ERROR_RESPONSE_INVALID = 'Invalid response';
    const DEBUG_KEY = 'EMARSYS_DEBUG_INFO';
    
    protected function _debug($apiCall, $apiMethod, $data)
    {
        if ($this->_debug) {
            Mage::getSingleton('emarsys_suite2/debug')->debug($apiCall, $apiMethod, $data);
        }
    }
    
    /**
     * @inheritdoc
     */
    protected function _trySetCurlAdapter()
    {
        if (extension_loaded('curl')) {
            $this->setAdapter(Mage::getModel('emarsys_suite2/adapter_http_curl'));
        }

        return $this;
    }
    
    /**
     * @inheritdoc
     */
    public function __construct($params)
    {
        $this->_apiUrl = $params['api_url'];
        $this->_username = $params['api_username'];;
        $this->_password = $params['api_password'];;
        $this->config['timeout'] = 60;
        $this->_debug = Mage::registry(self::DEBUG_KEY);
        return parent::__construct($this->_apiUrl);
    }
    
    /**
     * Generates WSSE header value
     * 
     * @param string $username Username
     * @param string $password Secret key
     * 
     * @return string
     */
    protected function _getWSSEHeader()
    {
        $nonce = md5(time());
        $timestamp = gmdate("c");
        $passwordDigest = base64_encode(sha1($nonce . $timestamp . $this->_password, false));
        return sprintf(
            'UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"',
            $this->_username,
            $passwordDigest,
            $nonce,
            $timestamp
        );
    }
    
    /**
     * Requests API call
     * 
     * @param string $apiCall API path
     * 
     * @return array
     * 
     * @throws Exception
     */
    protected function _request($apiCall, $method = Zend_Http_Client::GET, $data = array(), $jsonDecode = true)
    {
        $this->_debug($apiCall, $method, $data);
        Mage::helper('emarsys_suite2')->log('REST Tx: ' . $method . ' ' . $this->_apiUrl . '/' . $apiCall . ' ==> data: ' . json_encode($data));
        $this->setUri($this->_apiUrl . '/' . $apiCall);
        $this->setHeaders(
            array(
                'Content-Type'      => 'application/json',
                'Accept-encoding'   => 'utf-8',
                'X-WSSE'            => $this->_getWSSEHeader()
            )
        );
        try {

            if ($method == "GET" && ! (empty($data))) {
                $this->setParameterGet($data);
            }else {
                if (!empty($data)) {
                    $this->setRawData(Mage::helper('core')->jsonEncode($data));
                }
            }

            $responseObject = $this->request($method);
            $response = $responseObject->getBody();
            Mage::helper('emarsys_suite2')->log('REST Rx: ' . $method . ' ' . $this->_apiUrl . '/' . $apiCall . ' ==> data: ' . $response);
            if ($jsonDecode) {
                try {
                    $response = Mage::helper('core')->jsonDecode($response);
                } catch (Exception $e) {
                    Mage::log('JSON Decode failed. Got response headers from API:');
                    Mage::log($responseObject->getHeadersAsString(true, '. '), $this);
                    throw $e;
                }

                if ($response['replyCode'] !== 0) {
                    throw new Exception($response['replyText']);
                }
            }
        } catch (Exception $e) {
            throw $e;
        }

        return $response;
    }
    
    public function post($apiCall, $data = array())
    {
        if (is_object($data)) {
            $data = $data->toArray();
        }

        return $this->_request($apiCall, Zend_Http_Client::POST, $data);
    }
    
    public function put($apiCall, $data = array())
    {
        if (is_object($data)) {
            $data = $data->toArray();
        }

        return $this->_request($apiCall, Zend_Http_Client::PUT, $data);
    }
        
    public function get($apiCall, $data = array(), $jsonDecode = true)
    {
        return $this->_request($apiCall, Zend_Http_Client::GET, $data, $jsonDecode);
    }
    
    public function ping()
    {
        $result = 1;
        try {
            $response = $this->get('settings');
        } catch (Zend_Http_Client_Exception $e) {
            $result = self::API_ERROR_CONNECTION;
        } catch (Exception $e) {
            $result = $e->getMessage();
        }

        return $result;
    }
}