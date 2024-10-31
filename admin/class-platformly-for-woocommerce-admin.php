<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Platformly_WooCommerce_Admin extends Platformly_WooCommerce_Options{
    /**
     * Singleton instance of self.
     *
     * @var Platformly_WooCommerce_Admin
     */
    private static $instance = false;
    public $platformlyOfficialActive = false;
    public $platformlyOfficialProjectCodeId = 0;

    /**
     * We want a single instance of this class so we can accurately track registered menus and pages.
     */
    public static function get_instance(){
        if(!self::$instance){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function add_plugin_admin_menu(){
        add_menu_page(
            'Platformly - WooCommerce Setup',
            'Platform.ly for WooCommerce',
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_setup_page'),
            plugins_url('img/logo.png', __FILE__)
        );
    }

    /**
     * Render the settings page for this plugin.
     *
     */
    public function display_plugin_setup_page(){
        wp_enqueue_style('platfromly-wc-select2css');
        wp_enqueue_script('platfromly-wc-select2');
        wp_enqueue_script('platformly_wc_admin_script', PLATFORLY_WC_PLUGIN_DIR_URL . "admin/js/platformly-woocommerce-admin.js", array(), time());
        wp_enqueue_style('platformly_wc_admin_style', PLATFORLY_WC_PLUGIN_DIR_URL . "admin/css/platformly-woocommerce-admin.css");

        $apiKey = $this->getOption('API_key');
        if ($apiKey === null) {
            $apiKeyCorrect = false;
        } else {
            $user = Platformly_WooCommerce_PlatformApi::get_instance()->checkApiKey($apiKey);
            if($user === false){
                add_settings_error('platformly-wc-api-key-not-correct', 'settings_updated', 'The API key you added is not correct.');
                $apiKeyCorrect = false;
            }else{
                $apiKeyCorrect = true;
            }
        }

        include_once('partials/platformly-woocommerce-admin.php');
    }

    /**
     *
     */
    public function options_update(){
        register_setting($this->plugin_name, $this->plugin_name, array($this, 'validate'));
        $this->checkProjectCode();
        $this->checkImport();
    }

    /**
     * @param $input
     * @return array
     */
    public function validate($input){
        // Work around double execution before the first saving
        if (!isset($input['palatformly_wc_active_tab'])) {
            return $input;
        }

        $active_tab = sanitize_text_field($input['palatformly_wc_active_tab']);
        switch ($active_tab){
            case 'connect':
                $data = $this->validateConnectSettings($input);
                if(isset($data['platformly-wc-project-id']) && isset($data['platformly-wc-project-code'])){
                    do_action('platformly_wc_project_changed', sanitize_text_field($data['platformly-wc-project-id']), sanitize_text_field($data['platformly-wc-project-code']));
                }
                break;
            case 'contacts_settings':
                $data = $this->validatePostContactsSettings($input);
                break;
            case 'events_settings':
                $data = $this->validateEventsSettings($input);
                break;
            case 'sync_data':
                $data = $this->validateSyncData($input);
                break;
        }
        return (isset($data) && is_array($data)) ? array_merge($this->getOptions(), $data) : $this->getOptions();
    }

    protected function validateConnectSettings($input){
        $data = array();

        if(isset($input['is_enable_PLY'])){
            $data['is_enable_PLY'] = true;
        }else{
            $data['is_enable_PLY'] = false;
        }

        if(isset($input['API_key'])){
            if(empty($input['API_key'])){
                add_settings_error('platformly-wc-ipn-api-key', 'settings_updated', 'API key is required');
            }else{
                if(platformly_wc_check_api_acceess($input['API_key'])){
                    $data['API_key'] = sanitize_text_field($input['API_key'] ,true);

                    if(!empty($input['platformly_wc_project_id'])){
                        $data['platformly-wc-project-id'] = absint($input['platformly_wc_project_id']);
                    }else{
                        add_settings_error('platformly-wc-ipn-project-id', 'settings_updated', 'Project is required');
                    }

                    $data['platformly-wc-ipn-url'] = '';
                    if(!empty($input['platformly-wc-ipn-url'])){
                        $url = wp_http_validate_url(trim($input['platformly-wc-ipn-url']));
                        if($url !== false){
                            if(platformly_wc_check_ipn_url($url)){
                                $data['platformly-wc-ipn-url'] = $url;
                            }else{
                                add_settings_error('platformly-wc-ipn-url', 'settings_updated', 'The payment processor you have chosen is not available within your account.');
                            }
                        } else {
                            add_settings_error('platformly-wc-ipn-url', 'settings_updated', 'There is some error with IPN URL');
                        }
                    }else{
                        add_settings_error('platformly-wc-ipn-url', 'settings_updated', 'You need to choose a payment processor for the connection to work.');
                    }

                    if(!empty($input['platformly_wc_forward_transactions']) && is_array($input['platformly_wc_forward_transactions']) && !in_array('all', $input['platformly_wc_forward_transactions'])){
                        $forwardTransactions = platform_wc_clean($input['platformly_wc_forward_transactions']);
                    }else{
                        $forwardTransactions = array();
                    }
                    $data['forward_transactions'] = $forwardTransactions;
                    $data['platformly-wc-project-code'] = esc_js($input['platformly_wc_project_code']);
                    if(isset($data['platformly-wc-ipn-url']) && !empty($data['platformly-wc-ipn-url']) && $data['platformly-wc-ipn-url'] != $this->getOption('platformly-wc-ipn-url')){
                        $data['projects_sync_contacts'] = null;
                        $data['segment_sync_contacts'] = null;
                        $data['tags_sync_contacts'] = null;
                        $data['sync_contacts'] = null;
                        $data['sync_sales'] = null;
                        $data['sync_progress'] = null;
                        $data['sync_contacts_finished'] = null;
                        $data['sync_sales_finished'] = null;
                    }
                }else{
                    add_settings_error('platformly-wc-ipn-api-key', 'settings_updated', 'The API key you added is not correct.');
                }
            }
        }
        return $data;
    }

    protected function validatePostContactsSettings($input){
        $allowed_html = array(
            'a' => array(
                'href' => array(),
                'title' => array(),
                'target' => array()
            ),
            'br' => array()
        );
        $data = array(
            'gdpr_label' => (isset($input['gdpr_label']) && $input['gdpr_label'] != '') ? wp_kses($input['gdpr_label'], $allowed_html) : $this->getOption('gdpr_label', 'Subscribe to our newsletter'),
            'paltformly_wc_checkbox_defaults' => isset($input['paltformly_wc_checkbox_defaults']) && !empty($input['paltformly_wc_checkbox_defaults']) ? sanitize_text_field($input['paltformly_wc_checkbox_defaults']) : $this->getOption('paltformly_wc_checkbox_defaults', 'check'),
            'projects_purchased' => isset($input['projects_purchased']) ? platform_wc_intval($input['projects_purchased']) : array(),
            'segments_purchased' => isset($input['segments_purchased']) ? platform_wc_intval($input['segments_purchased']) : array(),
            'tags_purchased' => isset($input['tags_purchased']) ? platform_wc_intval($input['tags_purchased']) : array(),
            'projects_refunded' => isset($input['projects_refunded']) ? platform_wc_intval($input['projects_refunded']) : array(),
            'segments_refunded' => isset($input['segments_refunded']) ? platform_wc_intval($input['segments_refunded']) : array(),
            'tags_refunded' => isset($input['tags_refunded']) ? platform_wc_intval($input['tags_refunded']) : array(),
            'projects_failed' => isset($input['projects_failed']) ? platform_wc_intval($input['projects_failed']) : array(),
            'segments_failed' => isset($input['segments_failed']) ? platform_wc_intval($input['segments_failed']) : array(),
            'tags_failed' => isset($input['tags_failed']) ? platform_wc_intval($input['tags_failed']) : array(),
            'projects_cancelled' => isset($input['projects_cancelled']) ? platform_wc_intval($input['projects_cancelled']) : array(),
            'segments_cancelled' => isset($input['segments_cancelled']) ? platform_wc_intval($input['segments_cancelled']) : array(),
            'tags_cancelled' => isset($input['tags_cancelled']) ? platform_wc_intval($input['tags_cancelled']) : array(),
            'projects_registered' => isset($input['projects_registered']) ? platform_wc_intval($input['projects_registered']) : array(),
            'segments_registered' => isset($input['segments_registered']) ? platform_wc_intval($input['segments_registered']) : array(),
            'tags_registered' => isset($input['tags_registered']) ? platform_wc_intval($input['tags_registered']) : array(),
            'gdpr_project' => isset($input['gdpr_project']) ? platform_wc_intval($input['gdpr_project']) : array(),
            'gdpr_tag' => isset($input['gdpr_tag']) ? platform_wc_intval($input['gdpr_tag']) : array(),
            'gdpr_segment' => isset($input['gdpr_segment']) ? platform_wc_intval($input['gdpr_segment']) : array()
        );
        return $data;
    }

    protected function validateEventsSettings($input){
        $data = array();

        if (!empty($input['events']['abandoned_cart_recovered']) && empty($input['events']['abandoned_cart'])) {
            add_settings_error('platformly-wc-abandoned-cart-recovered', 'settings_updated', 'Before choosing event for Abandoned Cart Recovered you need to choose event for Abandoned Cart');
        } else {
            $data = array(
                'events' => array(
                    'view_product' => isset($input['events']['view_product']) ? absint($input['events']['view_product']) : 0,
                    'add_to_cart' => isset($input['events']['add_to_cart']) ? absint($input['events']['add_to_cart']) : 0,
                    'view_cart' => isset($input['events']['view_cart']) ? absint($input['events']['view_cart']) : 0,
                    'checkout' => isset($input['events']['checkout']) ? absint($input['events']['checkout']) : 0,
                    'place_order' => isset($input['events']['place_order']) ? absint($input['events']['place_order']) : 0,
                    'abandoned_cart' => isset($input['events']['abandoned_cart']) ? absint($input['events']['abandoned_cart']) : 0,
                    'abandoned_cart_recovered' => isset($input['events']['abandoned_cart_recovered']) ? absint($input['events']['abandoned_cart_recovered']) : 0
                )
            );

            $events = $this->getOption('events', false);
            $abandonedCartEnabled = empty($events['abandoned_cart']) ? 0 : 1;

            if (!empty($data['events']['abandoned_cart'])) {
                $data['abandoned_cart'] = array(
                    'time'              => !empty($input['abandoned_cart']['time']) ? absint($input['abandoned_cart']['time']) : Platformly_WooCommerce_Abandoned_Cart::$timeDefault,
                    'remove_after_days' => !empty($input['abandoned_cart']['remove_after_days']) ? absint($input['abandoned_cart']['remove_after_days']) : Platformly_WooCommerce_Abandoned_Cart::$removeAfterDays
                );

                if (!$abandonedCartEnabled) {
                    $abandonedCart = new Platformly_WooCommerce_Abandoned_Cart();
                    $abandonedCart->abandonedCartTurnOn();
                }
            } else {
                if ($abandonedCartEnabled) {
                    $abandonedCart = new Platformly_WooCommerce_Abandoned_Cart();
                    $abandonedCart->abandonedCartTurnOff();
                }
            }
        }

        return $data;
    }
    
    protected function validateSyncData($input){
        $projectId = $this->getOption('platformly-wc-project-id');
        $sync = new Platformly_WooCommerce_Sync_Data();
        $syncData = $sync->get_sync_data();
        $data = array();
        $started = false;
        if(!$syncData['sync_contacts']){
            $data['sync_contacts'] = isset($input['sync_contacts']) ? true : false;
            $data['projects_sync_contacts'] = $projectId ? $projectId : 0;
            $data['segment_sync_contacts'] = isset($input['segment_sync_contacts']) ? platform_wc_intval($input['segment_sync_contacts']) : 0;
            $data['tags_sync_contacts'] = isset($input['tags_sync_contacts']) ? platform_wc_intval($input['tags_sync_contacts']) : 0;
            if($data['sync_contacts'] == true){
                $started = true;
            }
        }
        if(!$syncData['sync_sales']){
            $data['sync_sales'] = isset($input['sync_sales']) ? true : false;
            if($data['sync_sales'] == true){
                $started = true;
            }
        }
        if($started === true && (!$syncData['sync_progress'] || $syncData['sync_progress'] == 'completed')){
            $data['sync_progress'] = 'started';
        }
        return $data;
    }
    
    private function checkProjectCode(){
        $this->platformlyOfficialProjectCodeId = platform_wc_get_ply_official_project_id();
        $this->platformlyOfficialActive = platform_wc_check_ply_official_plugin_is_active();
        $projectId = platformly_wc_get_option('platformly-wc-project-id');
        if(empty($projectId) && !empty($this->platformlyOfficialProjectCodeId)){
            platformly_wc_set_option('platformly-wc-project-id', $this->platformlyOfficialProjectCodeId);
            platformly_wc_set_option('ply_official_project_code_active', 1);
        }else if(empty($this->platformlyOfficialProjectCodeId) && platformly_wc_get_option('ply_official_project_code_active')){
            platformly_wc_set_option('ply_official_project_code_active', 0);
        }
    }
    
    private function checkImport(){
        $syncProgress = $this->getOption('sync_progress');
        if($syncProgress == 'started'){
            $sync = new Platformly_WooCommerce_Sync_Data();
            $sync->start_sync();
        }
    }
    
}
