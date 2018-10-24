<?php
/**
 * @category   Webextend
 * @package    Emarsys_Webextend
 */
/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();
$tableName = $installer->getTable('webextend/emarsysproductexport');
$productExportTable = $installer->getConnection()
    ->newTable($tableName)
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'unsigned' => true,
        'nullable' => false,
        'primary' => true,
    ], 'Product Id')
    ->addColumn('params', Varien_Db_Ddl_Table::TYPE_BLOB, '64k', [
    ], 'Product Params')
    ->setComment('Catalog Product Export');

$installer->getConnection()->createTable($productExportTable);
$tableName = $installer->getTable('webextend/emarsysproductattributes');
$installer->getConnection()
    ->query('UPDATE ' . $tableName . ' SET attribute_code = REPLACE(LOWER(TRIM(attribute_code)), " ", "_")');
$installer->endSetup();