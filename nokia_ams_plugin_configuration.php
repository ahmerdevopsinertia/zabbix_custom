<?php
class NokiaAMSPluginConfiguration
{
    function get()
    {
        $configuration = array(
            'idm_server_host' => '172.16.66.20:8443',
            'idm_service_url' => 'idm/services/InventoryRetrievalMgrExtns',
            'idm_service_name' => 'idm/services',
            'idm_server_user' => 'techno',
            'idm_server_password' => '4EPf3nme',
            'sdc_server_host' => '172.16.66.20:8443',
            'sdc_service_url' => 'sdc/services/PerformanceManagementRetrievalExtns',
            'sdc_service_name' => 'sdc/services',
            'sdc_server_user' => 'techno',
            'sdc_server_password' => '4EPf3nme'
        );
        return $configuration;
    }
}
