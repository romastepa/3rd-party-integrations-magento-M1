<?php

/* @var $this Mage_Eav_Model_Entity_Setup */
$this->startSetup();

$table = $this->getConnection()->newTable($this->getTable('emarsys_suite2/flag_order'))
    ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('primary' => true, 'nullable' => false, 'unsigned' => true), 'Order ID')
    ->addColumn('is_exported', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array('unsigned' => true), 'Is exported')
    ->addForeignKey('FK_ORDER_ID', 'order_id', $this->getTable('sales/order'), 'entity_id', Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_RESTRICT);
$this->getConnection()->createTable($table);

$table = $this->getConnection()->newTable($this->getTable('emarsys_suite2/flag_creditmemo'))
    ->addColumn('creditmemo_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('primary' => true, 'nullable' => false, 'unsigned' => true), 'Creditmemo ID')
    ->addColumn('is_exported', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array('unsigned' => true), 'Is exported')
    ->addForeignKey('FK_CREDITMEMO_ID', 'creditmemo_id', $this->getTable('sales/creditmemo'), 'entity_id', Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_RESTRICT);

$this->getConnection()->createTable($table);
// Clean empty entity_type_ids //
$this->run('DELETE FROM ' . $this->getTable('emarsys_suite2/queue') . ' WHERE entity_type_id IS NULL');
