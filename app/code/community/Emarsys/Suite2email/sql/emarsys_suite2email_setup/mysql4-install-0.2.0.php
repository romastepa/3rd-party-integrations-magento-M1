<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
try {
    $installer = $this;
    $installer->startSetup();
    $table = $this->getConnection()->newTable($this->getTable('emarsys_magento_events'))
        ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('primary' => true, 'identity' => true, 'nullable' => false, 'unsigned' => true), 'Event ID')
        ->addColumn('magento_event', Varien_Db_Ddl_Table::TYPE_TEXT, null, array('nullable' => true), 'Magento Event Name')
        ->addColumn('config_path', Varien_Db_Ddl_Table::TYPE_TEXT, null, array('nullable' => true), 'Magento Template Config Path');
    $this->getConnection()->createTable($table);

    $table = $this->getConnection()->newTable($this->getTable('emarsys_events'))
        ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('primary' => true, 'identity' => true, 'nullable' => false, 'unsigned' => true), 'ID')
        ->addColumn('event_id', Varien_Db_Ddl_Table::TYPE_TEXT, null, array('nullable' => true), 'Event Id')
        ->addColumn('emarsys_event', Varien_Db_Ddl_Table::TYPE_TEXT, null, array('nullable' => true), 'Emarsys Event')
        ->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('primary' => false, 'identity' => false, 'unsigned' => true), 'Website Id');
    $this->getConnection()->createTable($table);

    $table = $this->getConnection()->newTable($this->getTable('emarsys_event_mapping'))
        ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('primary' => true, 'identity' => true, 'nullable' => false, 'unsigned' => true), 'ID')
        ->addColumn('magento_event_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('primary' => false, 'identity' => false, 'unsigned' => true), 'Magento Event ID')
        ->addColumn('emarsys_event_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('primary' => false, 'identity' => false, 'unsigned' => true), 'Emarsys Event ID')
        ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('primary' => false, 'identity' => false, 'unsigned' => true), 'Store Id');
    $this->getConnection()->createTable($table);

    $table = $this->getConnection()->newTable($this->getTable('emarsys_placeholders_mapping'))
        ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('primary' => true, 'identity' => true, 'nullable' => false, 'unsigned' => true), 'ID')
        ->addColumn('event_mapping_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('primary' => false, 'identity' => false, 'unsigned' => true), 'Emarsys Event Mapping ID')
        ->addColumn('magento_placeholder_name', Varien_Db_Ddl_Table::TYPE_TEXT, null, array('nullable' => false), 'Magento Placeholder Name')
        ->addColumn('emarsys_placeholder_name', Varien_Db_Ddl_Table::TYPE_TEXT, null, array('nullable' => false), 'Emarsys Placeholder Name')
        ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('primary' => false, 'identity' => false, 'unsigned' => true), 'Store Id');
    $this->getConnection()->createTable($table);

    $mageObj = new Mage();
    if (method_exists($mageObj, 'getEdition')) {
        $current_edition = Mage::getEdition();
        if ($current_edition == Mage::EDITION_ENTERPRISE) {
            $installer->run(
                "
			INSERT INTO {$this->getTable('emarsys_magento_events')} (`magento_event`,`config_path`) VALUES
			('Email Header','design/email/header'),
			('Email Footer','design/email/footer'),
			('Contact Form','contacts/email/email_template'),
			('Credit Memo Update', 'sales_email/creditmemo_comment/template'),
			('Credit Memo Update for Guest', 'sales_email/creditmemo_comment/guest_template'),
			('Forgot Admin Password', 'admin/emails/forgot_email_template'),
			('Forgot Password', 'customer/password/forgot_email_template'),
			('Gift Registry Sharing', 'enterprise_giftregistry/sharing_email/template'),
			('Gift Registry Update', 'enterprise_giftregistry/update_email/template'),
			('Invoice Update', 'sales_email/invoice_comment/template'),
			('Invoice Update for Guest', 'sales_email/invoice_comment/guest_template'),
			('New Credit Memo', 'sales_email/creditmemo/template'),
			('New Credit Memo for Guest', 'sales_email/creditmemo/guest_template'),
			('New Invoice', 'sales_email/invoice/template'),
			('New Invoice for Guest', 'sales_email/invoice/guest_template'),
			('New Order', 'sales_email/order/template'),
			('New Order for Guest', 'sales_email/order/guest_template'),
			('New RMA', 'sales_email/enterprise_rma/template'),
			('New RMA for Guest', 'sales_email/enterprise_rma/guest_template'),
			('New Shipment', 'sales_email/shipment/template'),
			('New Shipment for Guest', 'sales_email/shipment/guest_template'),
			('New Account', 'customer/create_account/email_template'),
			('New Account Confirmation Key', 'customer/create_account/email_confirmation_template'),
			('New Account Confirmed', 'customer/create_account/email_confirmed_template'),
			('Newsletter Subscription Confirmation', 'newsletter/subscription/confirm_email_template'),
			('Newsletter Subscription Success', 'newsletter/subscription/success_email_template'),
			('Newsletter Unsubscribe Success', 'newsletter/subscription/un_email_template'),
			('Order Update', 'sales_email/order_comment/template'),
			('Order Update For Guest', 'sales_email/order_comment/guest_template'),
			('RMA Admin Comments', 'sales_email/enterprise_rma_comment/template'),
			('RMA Admin Comments for Guest', 'sales_email/enterprise_rma_comment/guest_template'),
			('RMA Authorization', 'sales_email/enterprise_rma_auth/template'),
			('RMA Authorization for Guest', 'sales_email/enterprise_rma_auth/guest_template'),
			('RMA Customer Comments', 'sales_email/enterprise_rma_customer_comment/template'),
			('Remind Password', 'customer/password/remind_email_template'),
			('Rewards Points Balance Update', 'enterprise_reward/notification/balance_update_template'),
			('Rewards Points Expiry Warning', 'enterprise_reward/notification/expiry_warning_template'),
			('Send Product to a Friend', 'sendfriend/email/template'),
			('Share Wish List', 'wishlist/email/email_template'),
			('Shipment Update', 'sales_email/shipment_comment/template'),
			('Shipment Update for Guest', 'sales_email/shipment_comment/guest_template'),
			('Store Credit Update', 'customer/enterprise_customerbalance/email_template');"
            );
        } else {
            $installer->run(
                "
			INSERT INTO {$this->getTable('emarsys_magento_events')} (`magento_event`,`config_path`) VALUES
			('Email Header','design/email/header'),
			('Email Footer','design/email/footer'),
			('Contact Form','contacts/email/email_template'),
			('Credit Memo Update', 'sales_email/creditmemo_comment/template'),
			('Credit Memo Update for Guest', 'sales_email/creditmemo_comment/guest_template'),
			('Forgot Admin Password', 'admin/emails/forgot_email_template'),
			('Forgot Password', 'customer/password/forgot_email_template'),
			('Invoice Update', 'sales_email/invoice_comment/template'),
			('Invoice Update for Guest', 'sales_email/invoice_comment/guest_template'),
			('New Credit Memo', 'sales_email/creditmemo/template'),
			('New Credit Memo for Guest', 'sales_email/creditmemo/guest_template'),
			('New Invoice', 'sales_email/invoice/template'),
			('New Invoice for Guest', 'sales_email/invoice/guest_template'),
			('New Order', 'sales_email/order/template'),
			('New Order for Guest', 'sales_email/order/guest_template'),
			('New Shipment', 'sales_email/shipment/template'),
			('New Shipment for Guest', 'sales_email/shipment/guest_template'),
			('New Account', 'customer/create_account/email_template'),
			('New Account Confirmation Key', 'customer/create_account/email_confirmation_template'),
			('New Account Confirmed', 'customer/create_account/email_confirmed_template'),
			('Newsletter Subscription Confirmation', 'newsletter/subscription/confirm_email_template'),
			('Newsletter Subscription Success', 'newsletter/subscription/success_email_template'),
			('Newsletter Unsubscribe Success', 'newsletter/subscription/un_email_template'),
			('Order Update', 'sales_email/order_comment/template'),
			('Order Update For Guest', 'sales_email/order_comment/guest_template'),
			('Remind Password', 'customer/password/remind_email_template'),
			('Send Product to a Friend', 'sendfriend/email/template'),
			('Share Wish List', 'wishlist/email/email_template'),
			('Shipment Update', 'sales_email/shipment_comment/template'),
			('Shipment Update for Guest', 'sales_email/shipment_comment/guest_template');"
            );
        }
    }

    $installer->endSetup();
    try {
        $config = Mage::getConfig()->getResourceConnectionConfig("default_setup");
        $exportDir = Mage::getBaseDir() . '/var/export';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0777, true);
        }

        $emarsySuite2EmailEvent = $exportDir . '/emarsys_suite2_email_event.sql';
        $dbUserName = $config->username;
        $dbPassword = $config->password;
        $dbHost = $config->host;
        $dbName = $config->dbname;
        $emarsysSuite2EmailEventTable = $this->getTable('emarsys_suite2_email_event');
        $backupCommand = "mysqldump -u$dbUserName -p$dbPassword -h$dbHost $dbName  $emarsysSuite2EmailEventTable > $emarsySuite2EmailEvent";
        exec($backupCommand);
    } catch (Exception $e) {
        Mage::log($e->getMessage());
    }

    /* Script to delete the registered events & relavant email templates created by old extension */
    $eventsCollection = Mage::getModel('emarsys_suite2/email_event')->getCollection();
    foreach ($eventsCollection as $_event) {
        Mage::getModel('emarsys_suite2/email_event')->load($_event->getId())->delete();
    }
} catch (Exception $e) {

    Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
}
