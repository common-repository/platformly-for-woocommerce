<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Platformly_WooCommerce_Options{
    protected $plugin_name = 'platformly-woocommerce';
    protected $plugin_options = null;
    
    /**
     * @return array
     */
    protected function getOptions(){
        if(empty($this->plugin_options)){
            $this->plugin_options = get_option($this->plugin_name);
        }
        return is_array($this->plugin_options) ? $this->plugin_options : array();
    }
    
    /**
     * @param $key
     * @param null $default
     * @return null
    */
    public function getOption($key, $default = null){
        $options = $this->getOptions();
        if (isset($options[$key])) {
            return $options[$key];
        }
        return $default;
    }
    
    /**
     * @return array
    */
    public function resetOptions(){
        return $this->plugin_options = get_option($this->plugin_name);
    }
    
    /**
     * @param $key
     * @param $value
     * @return $this
    */
    public function setData($key, $value){
        update_option($this->plugin_name.'-'.$key, $value, 'yes');
        return $this;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|void
    */
    public function getData($key, $default = null)    {
        return get_option($this->plugin_name.'-'.$key, $default);
    }

    /**
     * @param $key
     * @return bool
    */
    public function removeData($key){
        return delete_option($this->plugin_name.'-'.$key);
    }
    
    /**
     * @return Platformly_WooCommerce_PlatformIpnApi
     */
    public function api(){
        if(empty($this->api)){
            $this->api = new Platformly_WooCommerce_PlatformIpnApi($this->getOption('platformly-wc-ipn-url', false));
        }
        return $this->api;
    }
}
