<?php
/**
 * @category   Webextend
 * @package    Emarsys_Webextend
 */

class Emarsys_Webextend_Model_Mysql4_Emarsysproductexport extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Resource initialization
     */
    public function _construct()
    {
        $this->_init('webextend/emarsysproductexport', 'entity_id');
    }


    /**
     * Save Products to temp table
     *
     * @param array $products
     * @return Zend_Db_Statement_Interface
     */
    public function saveBulkProducts($products)
    {
        $lines = $bind = array();

        foreach ($products as $row) {
            $line = array();
            foreach ($row as $value) {
                $line[] = '?';
                $bind[] = $value;
            }
            $lines[] = sprintf('(%s)', implode(', ', $line));
        }

        $sql =  sprintf(
            'INSERT INTO %s (%s) VALUES%s ON DUPLICATE KEY UPDATE %s',
            $this->getMainTable(),
            'entity_id, params',
            implode(', ', $lines),
            '`params` = CONCAT(`params` , \'' . Emarsys_Webextend_Model_Emarsysproductexport::EMARSYS_DELIMITER . '\' , VALUES(`params`))'
        );

        return $this->_getWriteAdapter()->query($sql , $bind);
    }

    /**
     * Truncate a table
     *
     * @return Varien_Db_Adapter_Interface
     */
    public function truncateTable()
    {
        return $this->_getReadAdapter()->truncateTable($this->getMainTable());
    }
}