<?php

/* @var $this Mage_Eav_Model_Entity_Setup */
$this->startSetup();
$this->getConnection()->addKey(
    $this->getTable('emarsys_suite2/queue'),
    'ES_ENTITY',
    array(
        'entity_id',
        'website_id',
        'entity_type_id'
    ),
    Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
);
$this->endSetup();