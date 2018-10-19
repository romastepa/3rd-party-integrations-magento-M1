<?php
/* @var $this Mage_Eav_Model_Entity_Setup */
$this->run("DELETE FROM `{$this->getTable('emarsys_suite2/email_var')}` WHERE emarsys_code='prodcut_name'");
$this->run("INSERT INTO `{$this->getTable('emarsys_suite2/email_var')}` (`magento_code`, `emarsys_code`) VALUES ('{{var product_name}}', 'product_name')");
