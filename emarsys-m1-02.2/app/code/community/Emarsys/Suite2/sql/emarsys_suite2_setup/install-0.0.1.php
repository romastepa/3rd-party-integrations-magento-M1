<?php

/* @var $this Mage_Eav_Model_Entity_Setup */
$this->startSetup();

$table = $this->getConnection()->newTable($this->getTable('emarsys_suite2/queue'))
      ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('primary' => true, 'identity' => true, 'nullable' => false, 'unsigned' => true), 'Event ID')
      ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('unsigned' => true), 'Entity ID')
      ->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('unsigned' => true), 'Website ID')
      ->addColumn('entity_type_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('unsigned' => true), 'Entity type ID')
      ->addColumn('params', Varien_Db_Ddl_Table::TYPE_TEXT, null, array('nullable' => true), 'Extra parameters');
$this->getConnection()->createTable($table);

$table = $this->getConnection()->newTable($this->getTable('emarsys_suite2/email_event'))
      ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('primary' => true, 'identity' => true, 'nullable' => false, 'unsigned' => true), 'ID')
      ->addColumn('name', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array('unsigned' => true), 'Name')
      ->addColumn('template_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('unsigned' => true), 'Template ID')
      ->addColumn('event_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('unsigned' => true), 'Event ID');
$this->getConnection()->createTable($table);

$table = $this->getConnection()->newTable($this->getTable('emarsys_suite2/email_var'))
      ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('primary' => true, 'identity' => true, 'nullable' => false, 'unsigned' => true), 'ID')
      ->addColumn('magento_code', Varien_Db_Ddl_Table::TYPE_VARCHAR, null,  array('unsigned' => true), 'Magento code')
      ->addColumn('emarsys_code', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array('unsigned' => true), 'Emarsys code');
$this->getConnection()->createTable($table);

$this->endSetup();