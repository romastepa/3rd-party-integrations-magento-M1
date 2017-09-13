<?php

/**
 *
 * @category   Webextend
 * @package    Emarsys_Webextend
 * @copyright  Copyright (c) 2017 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
try {
    $this->startSetup();
    $table = $this->getConnection()->newTable($this->getTable('emarsys_product_attributes'))
        ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('primary' => true, 'identity' => true, 'nullable' => false, 'unsigned' => true), 'Attribute ID')
        ->addColumn('attribute_code', Varien_Db_Ddl_Table::TYPE_TEXT, null, array('nullable' => true), 'Emarsys Attribute Name')
        ->addColumn('attribute_label', Varien_Db_Ddl_Table::TYPE_TEXT, null, array('nullable' => true), 'Emarsys Attribute Label')
        ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('primary' => false, 'identity' => false, 'unsigned' => true), 'Store Id');
        $this->getConnection()->createTable($table);

    $table = $this->getConnection()->newTable($this->getTable('emarsys_product_attributes_mapping'))
        ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('primary' => true, 'identity' => true, 'nullable' => false, 'unsigned' => true), 'Attribute ID')
        ->addColumn('magento_attribute_code', Varien_Db_Ddl_Table::TYPE_TEXT, null, array('nullable' => true), 'Magento Attribute Code')
        ->addColumn('magento_attribute_code_label', Varien_Db_Ddl_Table::TYPE_TEXT, null, array('nullable' => true), 'magento_attribute_code_label')
        ->addColumn('emarsys_attribute_code_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('primary' => false, 'identity' => false, 'unsigned' => true), 'Emarsys Attribute Code ID')
        ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('primary' => false, 'identity' => false, 'unsigned' => true), 'Store Id');
    $this->getConnection()->createTable($table);
} catch (Exception $e) {
    Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
}