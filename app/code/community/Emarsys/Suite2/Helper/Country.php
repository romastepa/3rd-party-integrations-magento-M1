<?php
class Emarsys_Suite2_Helper_Country
{
    protected $_mapping = array();
    protected $_overrides = array(
        'Hong Kong'         => 'HK',
        'Macau'             => 'MO',
        'Brunei Darussalam' => 'BN',
        'Cote d\'Ivoire'    => 'CI',
        'Gambia, The'       => 'GM',
        'Congo'             => 'CG',
        'Congo, Democratic Republic of the' => 'CD',
        'Korea, South'      => 'KR',
        'Korea, North'      => 'KP',
        'Myanmar'           => 'MM',
        'The Netherlands'   => 'NL',
        'St. Kitts and Nevis'=> 'KN',
        'St. Lucia'         => 'LC',
        'St. Vincent and The Grenadines' => 'VC',
        'United States of America' => 'US',
    );
    
    protected function _getMapping()
    {
        $client = Mage::helper('emarsys_suite2')->getClient();
        $countries = array();
        $magentoCountries = Mage::getResourceModel('directory/country_collection');
        /* @var $magentoCountries Mage_Directory_Model_Country_Collection */
        
        $mappedCountries = array();
        
        foreach ($magentoCountries as $country) {
            $countries[$country->getName()] = $country->getId();
        }

        $data = $client->get('field/14/choice/translate/en');
        foreach ($data['data'] as $item) {
            $_name = $item['choice'];
            $_id = $item['id'];
            if (isset($countries[$_name])) {
                $_magentoCountryId = $countries[$_name];
                $mappedCountries[$_magentoCountryId] = $_id;
            } elseif (isset($this->_overrides[$item['choice']])) {
                $_magentoCountryId = $this->_overrides[$item['choice']];
                $mappedCountries[$_magentoCountryId] = $_id;
            }
        }

        return $mappedCountries;
    }
    
    public function getMapping()
    {
        $cacheKey = 'emarsys_country_map';
        if (empty($this->_mapping)) {
            if ($mapping = Mage::app()->getCache()->load($cacheKey)) {
                $this->_mapping = Mage::helper('core')->jsonDecode($mapping);
            } else {
                $mapping = $this->_getMapping();
                Mage::app()->getCache()->save(Mage::helper('core')->jsonEncode($mapping), $cacheKey, array(), 86400);
                $this->_mapping = $mapping;
            }
        }

        return $this->_mapping;
    }
}
