<?php

class Emarsys_Suite2_Model_System_Config_Syncorder
{
    /**
     * Returns options array
     * 
     * @return array
     */
    public function toOptionArray()
    {
        $list = array(Emarsys_Suite2_Model_Config::SYNC_LAST_UPDATE_OPTIN_CONTACT_EXPORT => 'Last Update Optin Sync -> Contact Export',Emarsys_Suite2_Model_Config::SYNC_DAILY_IMPORT_TO_EXPORT => 'Subscription update -> Contacts export', Emarsys_Suite2_Model_Config::SYNC_DAILY_EXPORT_TO_IMPORT => 'Contacts export -> Subscription update');
        return $list;
    }
}
