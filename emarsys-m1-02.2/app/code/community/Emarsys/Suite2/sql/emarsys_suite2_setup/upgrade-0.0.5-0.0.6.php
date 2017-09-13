<?php
/**
 * Add unique index to 'emarsys_suite2/email_var' module table.
 */

$this->startSetup();

$intaller = $this;
$intaller->run(
    "

DELETE t1 FROM {$this->getTable('emarsys_suite2/email_var')} t1,
               {$this->getTable('emarsys_suite2/email_var')} t2
          WHERE t1.magento_code = t2.magento_code AND t1.id > t2.id;

ALTER TABLE {$this->getTable('emarsys_suite2/email_var')} ADD UNIQUE INDEX (magento_code(255));

"
);
$this->endSetup();