<?php
/**
 *
 * @category   Webextend
 * @package    Emarsys_Webextend
 */

class Emarsys_Webextend_Model_System_Config_Options_Exporttype
{

    const TYPE_CONSOLIDATED = 1;
    const TYPE_SEPARATED = 2;

    /**
     * Get Export Types
     * @return mixed
     */
    public function toOptionArray()
    {
        $productTypes = array();

        $productTypes[] = array('value' => self::TYPE_CONSOLIDATED, 'label' => Mage::helper('webextend')->__('Consolidated'));
        $productTypes[] = array('value' => self::TYPE_SEPARATED, 'label' => Mage::helper('webextend')->__('Separated'));

        return $productTypes;
    }
}
