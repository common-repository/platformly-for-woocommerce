<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Platformly_WooCommerce_Product extends Platformly_WooCommerce_Options{
    private static $instance = false;
    public static function get_instance(){
        if(!self::$instance){
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function addPlatformTabToWcProduct($dataTabs){
        if(platformly_wc_check_enable_ply()){
            $dataTabs['platformly_wc_product_tab'] = array(
                'label' => 'Platform.ly',
                'target' => 'platformly_wc_tab_content'
            );
        }
        return $dataTabs;
    }
    
    public function platformlyWcTabContent(){
        if(platformly_wc_check_enable_ply()){
            wp_enqueue_style('platfromly-wc-select2css');
            wp_enqueue_script('platfromly-wc-select2');
            wp_enqueue_script('platformly_wc_admin_script', PLATFORLY_WC_PLUGIN_DIR_URL . "admin/js/platformly-woocommerce-admin.js", array(), time());
            wp_enqueue_style('platformly_wc_admin_style', PLATFORLY_WC_PLUGIN_DIR_URL . "admin/css/platformly-woocommerce-admin.css");
            include_once(PLATFORLY_WC_ABSPATH.'/admin/partials/platformly-woocommerce-product-tab.php');
        }else{
            echo '';
        }
    }
}

