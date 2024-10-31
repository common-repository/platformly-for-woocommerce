<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Platformly_WooCommerce_Sync_Data extends Platformly_WooCommerce_Options{
    private $ipnApi = false;

    public function __construct(){
        $ipnUrl = platformly_wc_get_option('platformly-wc-ipn-url', false);
        $this->ipnApi = new Platformly_WooCommerce_PlatformIpnApi($ipnUrl);
    }

    public function start_sync(){
        $progress = $this->get_progress();
        if($progress == 'started'){
            $syncData = $this->get_sync_data();
            $this->ipnApi->post(array('status' => 'sync_data', 'data' => $syncData));
            platformly_wc_set_option('sync_progress', 'in_the_process');
        }
    }
    
    public function get_progress(){
        return $this->getOption('sync_progress', false);
    }
    
    public function get_sync_data(){
        return array(
            'projects_sync_contacts' => $this->getOption('projects_sync_contacts'),
            'segment_sync_contacts' => $this->getOption('segment_sync_contacts'),
            'tags_sync_contacts' => $this->getOption('tags_sync_contacts'),
            'sync_contacts' => $this->getOption('sync_contacts'),
            'sync_sales' => $this->getOption('sync_sales'),
            'sync_progress' => $this->getOption('sync_progress'),
            'sync_contacts_finished' => $this->getOption('sync_contacts_finished'),
            'sync_sales_finished' => $this->getOption('sync_sales_finished')
        );
    }
    
    public function reset_sync_data(){
        platformly_wc_set_option('projects_sync_contacts', null);
        platformly_wc_set_option('segment_sync_contacts', null);
        platformly_wc_set_option('tags_sync_contacts', null);
        platformly_wc_set_option('sync_contacts', null);
        platformly_wc_set_option('sync_sales', null);
        platformly_wc_set_option('sync_progress', null);
        platformly_wc_set_option('sync_contacts_finished', null);
        platformly_wc_set_option('sync_sales_finished', null);
    }
}

