<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Platformly_WooCommerce_PlatformApi{
    /**
     * Singleton instance of self.
     *
     * @var Platformly_WooCommerce_PlatformApi
    */
    private static $instance = false;
    private $apiUrl = PLATFORMLY_WC_URL;
    private $apiKey = '';
        
    /**
     * We want a single instance of this class so we can accurately track registered menus and pages.
    */
    public static function get_instance($api_key = null){
        if(!self::$instance){
            self::$instance = new self($api_key);
        }
        return self::$instance;
    }
    
    public function __construct($api_key = null){
        if(empty($api_key)){
            $api_key = platformly_wc_get_option('API_key');
        }
        $this->setApiKey($api_key);
    }

    private function setApiKey($key){
        $this->apiKey = $key;
    }
            
    function getProjects(){
        return $this->_platformly_api_get(array('action' => 'listProjects'));
    }
    
    function getSegments($projectId){
        if(!is_array($projectId)){
            $projectId = array($projectId);
        }
        return $this->_platformly_api_get(array('action' => 'listSegments', 'projectId' => $projectId));
    }
    
    function getTags($projectId){
        if(!is_array($projectId)){
            $projectId = array($projectId);
        }
        return $this->_platformly_api_get(array('action' => 'listTags', 'projectId' => $projectId));
    }
    
    function getPaymentProcessors($projectId){
        return $this->_platformly_api_get(array('action' => 'listPaymentProcessors', 'projectId' => $projectId));
    }
    
    function getEvents($projectId){
        return $this->_platformly_api_get(array('action' => 'listEvents', 'projectId' => $projectId));
    }
    
    function getProjectCode($projectId){
        $response = $this->_platformly_api_get(array('action' => 'getProjectCode', 'projectId' => $projectId));
        if($response !== false && $response['status'] === 'success'){
            return esc_attr($response['projectCode']);
        }
        return false;
    }
    
    function checkApiKey($apiKey){
        $user = $this->_platformly_api_get(array('plugin_key' => $apiKey), 'plugin.check.key.php');

        if(!empty($user)){
            $default = PLATFORLY_WC_PLUGIN_DIR_URL. "admin/img/profile_img.png";
            if(isset($user['profile_image']) && strlen($user['profile_image']) > 0){
                if(!empty($user['use_gravatar'])){
                    $img_url = $user['profile_image']."?d=".urlencode( $default )."&s=90";
                }else{
                    $img_url = $user['profile_image'];
                }
            }else{
                $img_url = $default;
            }
            $user['profile_image'] = $img_url;
        }
        return $user;
    }
    
    function _platformly_api_get($params = array(), $file = 'plugin.actions.php'){
        if(!empty($params)){
            $query = "&".build_query($params);
        }else{
            $query = "";
        }

        $response = wp_remote_request(
            $this->apiUrl."/plugin/{$file}?plugin_key=".$this->apiKey.$query,
            array(
                'method' => 'GET'
            )
        );

        $responseData = wp_remote_retrieve_body($response);

        $responseData = json_decode($responseData, true);
        if(isset($responseData['status']) && $responseData['status'] == 'not_found'){ 
            return false;
        }else{
            return $responseData;
        }
    }
    
    function _platformly_api_post($data){
        $postData = array(
            //'timeout'   => 12,
            //redirection => 5,
            'blocking'  => false,
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
            'body' => json_encode($data),
        );
        //$response = wp_remote_post($this->ipnUrl, $postData);
    }
}

