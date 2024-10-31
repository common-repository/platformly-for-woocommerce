<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Platformly_WooCommerce_PlatformIpnApi{
    protected $version = '1.0';
    protected $ipnUrl = null;

    /**
     * @param null $ipnUrl
     */
    public function __construct($ipnUrl = null){
        if (!empty($ipnUrl)) {
            $this->setIpnUrl($ipnUrl);
        }
    }

    /**
     * @param $ipnUrl
     * @return $this
     */
    public function setIpnUrl($ipnUrl){
        $this->ipnUrl = $ipnUrl;
        return $this;
    }

    public function post($data, $blocking = false){
        // Sync REST API URL
        $data['rest_api_url'] = get_platformly_wc_rest_url();

        // Prepare and send post
        $postData = array(
            'blocking'  => $blocking,
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json; charset=utf-8'
            ),
            'body' => json_encode($data),
        );
        $response = wp_remote_post($this->ipnUrl, $postData);

        // Verify response
        if($blocking === true){
            if(is_wp_error($response) || !in_array($response['response']['code'], array(200, 301, 302))){
                return false;
            }
        }
        return true;
    }
}
