<?php

/**
 * Plugin Name: Platform.ly for WooCommerce
 * Description: Easily connect WooCommerce to your Platformly CRM, set up abandoned cart campaigns and access detailed customer reporting: lifetime value and more...
 * Version: 1.1.5
 * Author: Platform.ly
 * Author URI: https://www.platform.ly/
 * 
 * WC requires at least: 3.5.0
 * WC tested up to: 9.2.3
 * 
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
if (!defined('ABSPATH')) {
    exit;
}

class Platformly_WooCommerce {

    /**
     * Platform.ly for WooCommerce version.
     *
     * @var string
     */
    public $version = '1.1.5';

    /**
     * The single instance of the class.
     *
     * @var object
     */
    protected static $instance = null;
    protected $options = array();

    protected function __construct() {
        
    }

    /**
     * Get class instance.
     *
     * @return object Instance.
     */
    final public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        $this->define_constants();
        // Load options
        $this->options = get_option('platformly-woocommerce', array());

        require_once PLATFORLY_WC_ABSPATH . 'includes/functions.php';
        register_activation_hook(PLATFORLY_WC_PLUGIN_FILE, array($this, 'on_activation'));
        register_deactivation_hook(PLATFORLY_WC_PLUGIN_FILE, array($this, 'on_deactivation'));
        if(is_admin()){
            $this->remove_ply_cookie();
        }
        add_action('plugins_loaded', array($this, 'on_plugins_loaded'));
    }

    protected function define_constants() {
        $this->define('PLATFORMLY_WC_URL', 'https://pageserver.platform.ly');
        $this->define('PLATFORLY_WC_ABSPATH', dirname(__FILE__) . '/');
        $this->define('PLATFORLY_WC_PLUGIN_FILE', __FILE__);
        $this->define('PLATFORLY_WC_PLUGIN_BASENAME', plugin_basename(PLATFORLY_WC_PLUGIN_FILE));
        $this->define('PLATFORLY_WC_PLUGIN_DIR_URL', plugin_dir_url(__FILE__));
    }

    public function includes() {
        require_once PLATFORLY_WC_ABSPATH . 'includes/class-platformly-woocommerce-rest-api.php';
        require_once PLATFORLY_WC_ABSPATH . 'includes/class-platform-ipn-api.php';
        require_once PLATFORLY_WC_ABSPATH . 'includes/class-platform-api.php';
        require_once PLATFORLY_WC_ABSPATH . 'includes/class-platformly-woocommerce-options.php';
        require_once PLATFORLY_WC_ABSPATH . 'admin/class-platformly-for-woocommerce-admin.php';
        require_once PLATFORLY_WC_ABSPATH . 'includes/class-platformly-woocommerce-product.php';
        require_once PLATFORLY_WC_ABSPATH . 'includes/class-abandoned-cart.php';
        require_once PLATFORLY_WC_ABSPATH . 'includes/class-platformly-sync-data.php';
    }

    protected function hooks() {
        if (is_admin()) {
            // Admin hooks
            add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
            add_action('admin_init', array($this, 'options_update'));
        }

        $service = new Platformly_WooCommerce_Rest_Api();
        add_action('rest_api_init', array($service, 'register_routes'));

        add_action('woocommerce_new_order', array($this, 'create_order'));
        add_action('woocommerce_order_status_changed', array($this, 'update_order'), 10, 3);
        add_action('woocommerce_order_refunded', array($this, 'order_refunded'));
        add_filter('woocommerce_get_checkout_order_received_url', array($this, 'add_ply_params_to_received_url'), 10, 2);

        // Possible customer's events
        add_action('woocommerce_before_single_product', array($this, 'view_product'), 10);
        add_action('woocommerce_add_to_cart', array($this, 'product_add_to_cart'), 10, 4);
        add_action('woocommerce_before_cart_contents', array($this, 'view_cart'), 10);
        add_action('woocommerce_before_checkout_billing_form', array($this, 'view_checkout_page'), 10);
        add_action('user_register', array($this, 'on_user_registered'), 10, 1);

        // Abandoned cart
        if ($this->is_enabled_abandoned_cart()) {
            $abandonedCart = new Platformly_WooCommerce_Abandoned_Cart();

            if (is_user_logged_in()) {
                add_action( 'woocommerce_add_to_cart',                      array( $abandonedCart, 'store_cart' ), 100 );
                add_action( 'woocommerce_cart_item_removed',                array( $abandonedCart, 'store_cart' ), 100 );
                add_action( 'woocommerce_cart_item_restored',               array( $abandonedCart, 'store_cart' ), 100 );
                add_action( 'woocommerce_after_cart_item_quantity_update',  array( $abandonedCart, 'store_cart' ), 100 );
                add_action( 'woocommerce_calculate_totals',                 array( $abandonedCart, 'store_cart' ), 100 );
            }

            add_action('ply_wc_remove_old_abandoned_carts', 'ply_wc_remove_old_abandoned_carts_f', 100);
            if ( ! wp_next_scheduled( 'ply_wc_remove_old_abandoned_carts' ) ) {
                wp_schedule_event( time()+86400, 'daily', 'ply_wc_remove_old_abandoned_carts' );
            }

            if ($this->is_enabled_abandoned_cart_recovered()) {
                add_filter( 'template_include', array( $abandonedCart, 'track_abandoned_cart_link' ), 10, 1 );
            }
        }

        add_action('woocommerce_process_product_meta', array($this, 'product_save'), 10, 2);

        // TODO: subscription hook
        /* if (class_exists('WC_Subscriptions_Order')) {
            add_action('woocommerce_subscription_status_updated', array($this, 'update_subscriptions_status'), 10, 3);
            add_action('woocommerce_scheduled_subscription_payment', array($this, 'update_subscriptions_status_payment'));
            add_action('woocommerce_subscription_status_cancelled', array($this, 'update_subscriptions_status_cancelled'));
            add_action('woocommerce_subscription_status_active', array($this, 'update_subscriptions_status_active'));
        } */

        add_action('wp_trash_post', array($this, 'trash_order'));
        add_action('before_delete_post', array($this, 'delete_order'), 5);

        $render_gdpr = platformly_wc_get_option('paltformly_wc_checkbox_action', 'woocommerce_after_checkout_billing_form');
        add_action($render_gdpr, array($this, 'applyGdprCheckbox'), 10);

        add_action('woocommerce_checkout_order_processed', array($this, 'processGdprField'));
        add_filter('woocommerce_product_data_tabs', array($this, 'addPlatformWcTab'));
        add_action('woocommerce_product_data_panels', array($this, 'addPlatformWcProductPanel'));

        add_action('admin_enqueue_scripts', array($this, 'admin_styles'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        
        //add_action('admin_head', array($this, 'setProjectCode'));
        add_action('wp_head', array($this, 'setProjectCode'));

        //ajax hooks
        add_action('wp_ajax_platformly_wc_get_projects', array($this, 'platformly_wc_get_projects_callback'));
        add_action('wp_ajax_platformly_wc_get_segments', array($this, 'platformly_wc_get_segments_callback'));
        add_action('wp_ajax_platformly_wc_get_tags', array($this, 'platformly_wc_get_tags_callback'));
        add_action('wp_ajax_platformly_wc_get_payment_processors', array($this, 'platformly_wc_get_payment_processors_callback'));
        add_action('wp_ajax_platformly_wc_check_api_key', array($this, 'platformly_wc_check_api_key_callback'));
        add_action('wp_ajax_platformly_wc_get_events', array($this, 'platformly_wc_get_events_callback'));
        add_action('wp_ajax_platformly_wc_get_project_code', array($this, 'platformly_wc_get_project_code_callback'));

        // Allow using local hosts
        add_filter( 'http_request_host_is_external', '__return_true' );
        
        // Platform.ly Official hooks
        add_action('platform_ly_project_changed', array($this, 'platform_ly_project_changed'), 10, 1);
    }

    //woocommerce_scheduled_subscription_payment - rebill
    //woocommerce_subscription_renewal_payment_complete - rebill
    //woocommerce_subscription_status_updated

    /**
     * Deactivates this plugin.
     */
    public function deactivate_self() {
        deactivate_plugins(PLATFORLY_WC_PLUGIN_FILE);
    }

    /**
     * Install DB and create cron events when activated.
     *
     * @return void
     */
    public function on_activation() {
        if (!platformly_check_woocommerce_plugin_status()) {
            // Deactivate the plugin
            deactivate_plugins(PLATFORLY_WC_PLUGIN_FILE);
            $error_message = 'The Platform.ly For WooCommerce plugin requires the <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> plugin to be active!';
            wp_die($error_message);
        }
        
        if(platform_wc_check_ply_official_plugin_is_active()){
            $projectId = platform_wc_get_ply_official_project_id();
            if(!$this->platform_wc_check_project_id($projectId)){
                platformly_wc_set_option('platformly-wc-project-id', '');
                platformly_wc_set_option('platformly-wc-project-code', '');
                platformly_wc_set_option('platformly-wc-ipn-url', '');
            }
        }

        $this->create_abandoned_cart_db_structure();
    }

    public function on_deactivation() {
        // Unshedule events for this hook
        if( wp_next_scheduled( 'ply_wc_remove_old_abandoned_carts' ) ) {
            wp_clear_scheduled_hook( 'ply_wc_remove_old_abandoned_carts' );
        }
    }

    /**
     * Create tables
     * 
     * @global type $wpdb
     */
    protected function create_abandoned_cart_db_structure() {
        global $wpdb;

        $wcap_collate = '';
        if ( $wpdb->has_cap( 'collation' ) ) {
            $wcap_collate = $wpdb->get_charset_collate();
        }

        $table_name = $wpdb->prefix . 'ply_wc_abandoned_carts';
        $abandoned_cart_query = "CREATE TABLE IF NOT EXISTS $table_name (
                         `id` int(11) NOT NULL AUTO_INCREMENT,
                         `user_id` int(11) NOT NULL,
                         `cart_data` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
                         `time` int(11) NOT NULL,
                         `abandoned` int(11) NOT NULL,
                         `restore_cart_key` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
                         PRIMARY KEY (`id`)
                         ) $wcap_collate";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $abandoned_cart_query );
    }

    public function on_plugins_loaded() {
        $this->includes();
        $this->hooks();
    }

    /**
     * Define constant if not already set.
     *
     * @param string      $name  Constant name.
     * @param string|bool $value Constant value.
     */
    protected function define($name, $value) {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    public static function add_plugin_admin_menu() {
        Platformly_WooCommerce_Admin::get_instance()->add_plugin_admin_menu();
    }

    public static function options_update() {
        if (!platformly_check_woocommerce_plugin_status()) {
            // Deactivate the plugin
            deactivate_plugins(PLATFORLY_WC_PLUGIN_FILE);
        }
        Platformly_WooCommerce_Admin::get_instance()->options_update();
    }

    public function create_order($orderId) {
        if (!platformly_wc_is_configured()) {
            return false;
        }

        if (is_user_logged_in() && $this->is_enabled_abandoned_cart()) {
            $abandonedCart = new Platformly_WooCommerce_Abandoned_Cart();
            $abandonedCart->deleteAbandonedCartAfterPlacingOrder();
        }

        $options = get_option('platformly-woocommerce', array());
        $orderData = platformly_wc_get_order_data($orderId);
        if ($orderData['status'] !== 'refunded') {
            // Check for subscription
            $subscriber_meta = get_post_meta($orderId, 'paltformly_wc_is_subscribed', true);
            $subscribed_order = $subscriber_meta === '' ? false : (bool) $subscriber_meta;
            $orderData['subscribedOrder'] = $subscribed_order;
            if ($subscribed_order === true) {
                if (!empty($options['gdpr_segment'])) {
                    $orderData['subscribed_data']['ply_segments'] = $options['gdpr_segment'];
                }
                if (!empty($options['gdpr_tag'])) {
                    $orderData['subscribed_data']['ply_tags'] = $options['gdpr_tag'];
                }
                if (!empty($orderData['subscribed_data'])) {
                    $projectId = $options['platformly-wc-project-id'];
                    $orderData['subscribed_data']['ply_projects'] = array($projectId);
                }
            }

            // Event
            $status = 'place_order';
            $eventId = $this->get_event_id($status);
            $orderData['event_id'] = $eventId;

            // Get ply_activity
            $ply_activity = $this->get_ply_activity();
            if (!empty($ply_activity)) {                
                $orderData['ply_activity'] = $ply_activity;
                $this->unset_ply_activity();
            }

            $ipnUrl = $options['platformly-wc-ipn-url'];
            $api = new Platformly_WooCommerce_PlatformIpnApi($ipnUrl);
            $api->post($orderData);
        }
    }

    /*
     * send order when change status order
     */

    public function update_order($orderId, $statusFrom, $statusTo) {
        if (!platformly_wc_is_configured($statusTo)) {
            return false;
        }

        $options = get_option('platformly-woocommerce', array());
        $orderData = platformly_wc_get_order_data($orderId);

        if ($orderData['status'] !== 'refunded') {
            if (in_array($orderData['status'], array('completed', 'failed', 'cancelled')) && platformly_wc_check_enable_ply()) {
                switch ($orderData['status']) {
                    case 'completed':
                        $status = 'purchased';
                        break;
                    default:
                        $status = $orderData['status'];
                        break;
                }

                if (!empty($options["segments_{$status}"])) {
                    $orderData['platform_data']['ply_segments'] = $options["segments_{$status}"];
                }
                if (!empty($options["tags_{$status}"])) {
                    $orderData['platform_data']['ply_tags'] = $options["tags_{$status}"];
                }
                if (!empty($orderData['platform_data'])) {
                    $projectId = $options['platformly-wc-project-id'];
                    $orderData['platform_data']['ply_projects'] = array($projectId);
                }
            }

            // Get subscribe options
            $subscriber_meta = get_post_meta($orderId, 'paltformly_wc_is_subscribed', true);
            $subscribed_order = $subscriber_meta === '' ? false : (bool) $subscriber_meta;
            $orderData['subscribedOrder'] = $subscribed_order;
            if ($subscribed_order === true) {
                if (!empty($options['gdpr_segment'])) {
                    $orderData['subscribed_data']['ply_segments'] = $options['gdpr_segment'];
                }
                if (!empty($options['gdpr_tag'])) {
                    $orderData['subscribed_data']['ply_tags'] = $options['gdpr_tag'];
                }
                if (!empty($orderData['subscribed_data'])) {
                    $projectId = $options['platformly-wc-project-id'];
                    $orderData['subscribed_data']['ply_projects'] = array($projectId);
                }
            }

            $ipnUrl = isset($options['platformly-wc-ipn-url']) ? $options['platformly-wc-ipn-url'] : false;
            $api = new Platformly_WooCommerce_PlatformIpnApi($ipnUrl);
            $api->post($orderData);
        }
    }

    /*
     * send order when happen refund order or part money
     */

    public function order_refunded($orderId) {
        if (!platformly_wc_is_configured()) {
            return false;
        }

        $options = get_option('platformly-woocommerce', array());
        $ipnUrl = $options['platformly-wc-ipn-url'];
        $api = new Platformly_WooCommerce_PlatformIpnApi($ipnUrl);
        $orderData = platformly_wc_get_order_data($orderId);
        
        if (!empty($orderData['refund_data'])) {
            $orderData['status'] = 'refunded';
            $refundData = array_shift($orderData['refund_data']);

            // Max refund amount must not be more that total order's amount
            $orderTotal = 0;
            foreach ($orderData['items_data'] as $item) {
                $orderTotal += $item['total'];
            }
            $orderData['total'] = min($refundData['amount'], $orderTotal);

            $orderData['currency'] = $refundData['currency'];
            $orderData['refund_id'] = $refundData['refund_id'];

            if (platformly_wc_check_enable_ply()) {
                if (!empty($options['segments_refunded'])) {
                    $orderData['platform_data']['ply_segments'] = $options['segments_refunded'];
                }
                if (!empty($options['tags_refunded'])) {
                    $orderData['platform_data']['ply_tags'] = $options['tags_refunded'];
                }
                if (!empty($orderData['platform_data'])) {
                    $projectId = $options['platformly-wc-project-id'];
                    $orderData['platform_data']['ply_projects'] = array($projectId);
                }
            }
            $api->post($orderData);
        }
    }

    /*
     * send order when order moved to trash
     */

    public function trash_order($orderId) {
        if (!platformly_wc_is_configured()) {
            return false;
        }
        $options = get_option('platformly-woocommerce', array());
        $ipnUrl = $options['platformly-wc-ipn-url'];
        $api = new Platformly_WooCommerce_PlatformIpnApi($ipnUrl);

        $orderData = platformly_wc_get_order_data($orderId);
        if (!empty($orderData)) {
            $orderData['status'] = 'trash';
            $api->post($orderData);
        }
    }

    /*
     * send order when order delete
     */

    public function delete_order($orderId) {
        if (!platformly_wc_is_configured()) {
            return false;
        }
        $options = get_option('platformly-woocommerce', array());
        $ipnUrl = $options['platformly-wc-ipn-url'];
        $api = new Platformly_WooCommerce_PlatformIpnApi($ipnUrl);

        $orderData = platformly_wc_get_order_data($orderId);
        if (!empty($orderData)) {
            $orderData['status'] = 'deleted';
            $api->post($orderData);
        }
    }

    /*
     * apply Subscribe checkbox on checkout page
     */

    public function applyGdprCheckbox() {
        // if the user has chosen to hide the checkbox, don't do anything.
        if (($default_setting = platformly_wc_get_option('paltformly_wc_checkbox_defaults', 'check')) === 'hide') {
            return;
        }

        // allow the user to specify the text in the newsletter label.
        $label = platformly_wc_get_option('gdpr_label', 'Subscribe to our newsletter');

        // if the user chose 'check' or nothing at all, we default to true.
        $default_checked = $default_setting === 'check';
        $status = $default_checked;

        // if the user is logged in, we will pull the 'is_subscribed' property out of the meta for the value.
        // otherwise we use the default settings.
        if (is_user_logged_in()) {
            $status = get_user_meta(get_current_user_id(), 'paltformly_wc_is_subscribed', true);

            /// if the user is logged in - and is already subscribed - just ignore this checkbox.
            if ((bool) $status) {
                return;
            }
            if ($status === '' || $status === null) {
                $status = $default_checked;
            }
        }

        // echo out the checkbox.
        $checkbox = '<p class="form-row form-row-wide paltformly-wc-gdpr">';
        $checkbox .= '<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="paltformly_wc_gdpr" type="checkbox" name="paltformly_wc_gdpr" value="1"' . ($status ? ' checked="checked"' : '') . '> ';
        $checkbox .= '<label for="paltformly_wc_gdpr" class="woocommerce-form__label woocommerce-form__label-for-checkbox inline"><span>' . $label . '</span></label>';
        $checkbox .= '</p>';
        $checkbox .= '<div class="clear"></div>';

        echo apply_filters('paltformly_wc_gdpr_field', $checkbox, $status, $label);
    }

    function processGdprField($order_id) {
        $post_key = 'paltformly_wc_gdpr';
        $meta_key = 'paltformly_wc_is_subscribed';
        $logged_in = is_user_logged_in();

        // if the post key is available we use it - otherwise we null it out.
        $status = isset($_POST[$post_key]) ? absint($_POST[$post_key]) : null;

        // if the status is null, we don't do anything
        if ($status === null) {
            return false;
        }

        // if we passed in an order id, we update it here.
        if ($order_id) {
            update_post_meta($order_id, $meta_key, $status);
        }

        // if the user is logged in, we will update the status correctly.
        if ($logged_in) {
            update_user_meta(get_current_user_id(), $meta_key, $status);
            return $status;
        }

        return false;
    }

    function addPlatformWcTab($productDataTabs) {
        return Platformly_WooCommerce_Product::get_instance()->addPlatformTabToWcProduct($productDataTabs);
    }

    function addPlatformWcProductPanel() {
        return Platformly_WooCommerce_Product::get_instance()->platformlyWcTabContent();
    }

    public function admin_styles() {
        wp_register_style('platfromly-wc-select2css', PLATFORLY_WC_PLUGIN_DIR_URL . '/assets/css/select2.min.css');
    }

    public function admin_scripts() {
        wp_register_script('platfromly-wc-select2', PLATFORLY_WC_PLUGIN_DIR_URL . '/assets/js/select2/select2.full.min.js', array('jquery'));
    }

    public function platformly_wc_get_projects_callback() {
        if (!empty($_POST['apiKey'])) {
            $projectsList = Platformly_WooCommerce_PlatformApi::get_instance(sanitize_text_field($_POST['apiKey']))->getProjects();
        } else {
            $projectsList = Platformly_WooCommerce_PlatformApi::get_instance()->getProjects();
        }
        if ($projectsList === false) {
            wp_send_json_error($projectsList);
        }
        wp_send_json_success($projectsList);
    }

    public function platformly_wc_get_segments_callback() {
        $projectId = absint($_POST['platformly_wc_project_id']);
        $segmentsList = Platformly_WooCommerce_PlatformApi::get_instance()->getSegments($projectId);
        if ($segmentsList === false) {
            wp_send_json_error($segmentsList);
        }
        wp_send_json_success($segmentsList);
    }

    public function platformly_wc_get_tags_callback() {
        $projectId = absint($_POST['platformly_wc_project_id']);
        $tagsList = Platformly_WooCommerce_PlatformApi::get_instance()->getTags($projectId);
        if ($tagsList === false) {
            wp_send_json_error($tagsList);
        }
        wp_send_json_success($tagsList);
    }

    public function platformly_wc_get_payment_processors_callback() {
        $projectId = absint($_POST['platformly_wc_project_id']);
        if (!empty($_POST['apiKey'])) {
            $paymentProcessorsList = Platformly_WooCommerce_PlatformApi::get_instance(sanitize_text_field($_POST['apiKey']))->getPaymentProcessors($projectId);
        } else {
            $paymentProcessorsList = Platformly_WooCommerce_PlatformApi::get_instance()->getPaymentProcessors($projectId);
        }
        if ($paymentProcessorsList === false) {
            wp_send_json_error($paymentProcessorsList);
        }
        wp_send_json_success($paymentProcessorsList);
    }

    public function product_save($id, $post) {
        update_post_meta($id, 'platformly-woocommerce', platform_wc_intval(wp_unslash($_POST['platformly-woocommerce'])));
    }

    protected function get_event_id($event_name) {
        if (!empty($this->options['events'][$event_name])) {
            return $this->options['events'][$event_name];
        }
        return false;
    }

    /**
     * Event "view_product"
     * @global WC_Product_Simple $product
     */
    public function view_product() {
        if (!platformly_wc_check_enable_ply()) {
            return;
        }

        // Get eventId
        $status = 'view_product';
        $eventId = $this->get_event_id($status);
        if ($eventId === false) {
            return;
        }

        // Get product
        /* @var $product WC_Product_Simple */
        global $product;
        $productId = $product->get_id();

        $data = array(
            'status' => $status,
            'event_id' => $eventId,
            'product_data' => array(
                'name' => $product->get_title(),
                'product_link' => $product->get_permalink(),
                'thumbnail' => platform_wc_get_product_thumbnail($productId),
                'sku' => $product->get_sku(),
                'price' => $product->get_price()
            )
        );

        // User
        $currentUser = wp_get_current_user();
        if (empty($currentUser->ID)) {
            $this->set_ply_activity($data);
        } else {
            $data['customer_id'] = $currentUser->ID;
            $data['first_name'] = $currentUser->user_firstname;
            $data['last_name'] = $currentUser->user_lastname;
            $data['email'] = $currentUser->user_email;

            // API
            $ipnUrl = isset($this->options['platformly-wc-ipn-url']) ? $this->options['platformly-wc-ipn-url'] : false;
            $api = new Platformly_WooCommerce_PlatformIpnApi($ipnUrl);
            $api->post($data, true);
        }
    }

    /**
     * Event "add_to_cart"
     */
    public function product_add_to_cart($cart_item_key, $productId, $quantity, $variationId) {
        // Check is enabled PLY
        if (!platformly_wc_check_enable_ply()) {
            return;
        }

        // Get product
        $product = wc_get_product($productId);
        if (empty($product)) {
            return;
        }

        // Get eventId
        $status = 'add_to_cart';
        $eventId = $this->get_event_id($status);

        $data = array(
            'status' => $status,
            'event_id' => $eventId,
            'cart_item_key' => $cart_item_key,
            'product_data' => array(
                'name' => $product->get_title(),
                'product_link' => $product->get_permalink(),
                'thumbnail' => platform_wc_get_product_thumbnail($productId),
                'sku' => $product->get_sku(),
                'price' => $this->getPriceOfProductsVariation($productId, $variationId), //$product->get_price(),
                'quantity' => $quantity
            ),
            'platform_data' => array()
        );

        // Check for PLY settings at product meta
        $platformlyData = get_post_meta($productId, 'platformly-woocommerce', true);
        if (!empty($platformlyData)) {
            if (!empty($platformlyData['segmentsCart'])) {
                $data['platform_data']['ply_segments'] = $platformlyData['segmentsCart'];
            }
            if (!empty($platformlyData['tagsCart'])) {
                $data['platform_data']['ply_tags'] = $platformlyData['tagsCart'];
            }
            if (!empty($data['platform_data'])) {
                $projectId = platformly_wc_get_option('platformly-wc-project-id');
                $data['platform_data']['ply_projects'] = array($projectId);
            }
        }

        // Get current user
        $currentUser = wp_get_current_user();
        if (empty($currentUser->ID)) {
            $this->set_ply_activity($data);
        } else {
            $data['customer_id'] = $currentUser->ID;
            $data['first_name'] = $currentUser->user_firstname;
            $data['last_name'] = $currentUser->user_lastname;
            $data['email'] = $currentUser->user_email;

            // API
            $ipnUrl = isset($this->options['platformly-wc-ipn-url']) ? $this->options['platformly-wc-ipn-url'] : false;
            $api = new Platformly_WooCommerce_PlatformIpnApi($ipnUrl);
            $api->post($data);
        }
    }

    /**
     * Event "view_cart"
     */
    public function view_cart() {
        if (!platformly_wc_check_enable_ply()) {
            return;
        }

        // Event
        $status = 'view_cart';
        $eventId = $this->get_event_id($status);
        if ($eventId === false) {
            return;
        }

        $data = array(
            'status' => $status,
            'event_id' => $eventId
        );

        // Get current user
        $currentUser = wp_get_current_user();
        if (empty($currentUser->ID)) {
            $this->set_ply_activity($data);
        } else {
            $data['customer_id'] = $currentUser->ID;
            $data['first_name'] = $currentUser->user_firstname;
            $data['last_name'] = $currentUser->user_lastname;
            $data['email'] = $currentUser->user_email;

            // API
            $ipnUrl = isset($this->options['platformly-wc-ipn-url']) ? $this->options['platformly-wc-ipn-url'] : false;
            $api = new Platformly_WooCommerce_PlatformIpnApi($ipnUrl);
            $api->post($data);
        }
    }

    public function view_checkout_page($checkout) {
        if (!platformly_wc_check_enable_ply()) {
            return;
        }

        // Event
        $status = 'checkout';
        $eventId = $this->get_event_id($status);
        if ($eventId === false) {
            return;
        }

        $data = array(
            'status' => $status,
            'event_id' => $eventId
        );

        // Get current user
        $currentUser = wp_get_current_user();
        if (empty($currentUser->ID)) {
            $this->set_ply_activity($data);
        } else {
            $data['customer_id'] = $currentUser->ID;
            $data['first_name'] = $currentUser->user_firstname;
            $data['last_name'] = $currentUser->user_lastname;
            $data['email'] = $currentUser->user_email;

            // API
            $ipnUrl = isset($this->options['platformly-wc-ipn-url']) ? $this->options['platformly-wc-ipn-url'] : false;
            $api = new Platformly_WooCommerce_PlatformIpnApi($ipnUrl);
            $api->post($data);
        }
    }

    public function platformly_wc_check_api_key_callback() {
        $apiKey = sanitize_text_field($_POST['api_key']);
        $user = Platformly_WooCommerce_PlatformApi::get_instance()->checkApiKey($apiKey);

        if (empty($user)) {
            wp_send_json_error($user);
        }
        if(!$this->platformly_wc_check_user($user['id'])){
            wp_send_json_error(array('msg' => 'Only one user can be used in  Platform.ly applications'));
        }
        wp_send_json_success($user);
    }

    /*
     * send order when change status subscription
     */

    public function update_subscriptions_status($subscription, $new_status, $old_status) {
        //subscription status: pending, active, on-hold, pending-cancel, cancelled, or expired
        /* if(!platformly_wc_is_configured($statusTo)){
          return false;
          } */
        $options = get_option('platformly-woocommerce', array());
        $ipnUrl = isset($options['platformly-wc-ipn-url']) ? $options['platformly-wc-ipn-url'] : false;
        $api = new Platformly_WooCommerce_PlatformIpnApi($ipnUrl);
        $orderId = method_exists($subscription, 'get_parent_id') ? $subscription->get_parent_id() : $subscription->order->id;
        $orderData = platformly_wc_get_order_data($orderId);
        $orderData['status'] = 'subscription_' . $new_status;
        $api->post($orderData);
    }

    public function update_subscriptions_status_payment($subscription_id) {
        $status = 'subscription_payment_complete';
        if (!platformly_wc_is_configured($status)) {
            return false;
        }
        $options = get_option('platformly-woocommerce', array());
        $ipnUrl = isset($options['platformly-wc-ipn-url']) ? $options['platformly-wc-ipn-url'] : false;
        $api = new Platformly_WooCommerce_PlatformIpnApi($ipnUrl);
        $subscription = wcs_get_subscription($subscription_id);
        $orderId = method_exists($subscription, 'get_parent_id') ? $subscription->get_parent_id() : $subscription->order->id;
        $orderData = platformly_wc_get_order_data($orderId);
        $orderData['status'] = $status;
        $api->post($orderData);
    }

    public function update_subscriptions_status_cancelled($subscription) {
        $status = 'subscription_cancelled';
        if (!platformly_wc_is_configured($status)) {
            return false;
        }
        $options = get_option('platformly-woocommerce', array());
        $ipnUrl = isset($options['platformly-wc-ipn-url']) ? $options['platformly-wc-ipn-url'] : false;
        $api = new Platformly_WooCommerce_PlatformIpnApi($ipnUrl);
        $orderId = method_exists($subscription, 'get_parent_id') ? $subscription->get_parent_id() : $subscription->order->id;
        $orderData = platformly_wc_get_order_data($orderId);
        $orderData['status'] = $status;
        $api->post($orderData);
    }

    public function update_subscriptions_status_active($subscription) {
        $status = 'subscription_active';
        if (!platformly_wc_is_configured($status)) {
            return false;
        }
        $options = get_option('platformly-woocommerce', array());
        $ipnUrl = isset($options['platformly-wc-ipn-url']) ? $options['platformly-wc-ipn-url'] : false;
        $api = new Platformly_WooCommerce_PlatformIpnApi($ipnUrl);
        $orderId = method_exists($subscription, 'get_parent_id') ? $subscription->get_parent_id() : $subscription->order->id;
        $orderData = platformly_wc_get_order_data($orderId);
        $orderData['status'] = $status;
        $api->post($orderData);
    }

    public function platformly_wc_get_events_callback() {
        $projectId = platformly_wc_get_option('platformly-wc-project-id');
        $eventsList = Platformly_WooCommerce_PlatformApi::get_instance()->getEvents($projectId);
        if ($eventsList === false) {
            wp_send_json_error($eventsList);
        }
        wp_send_json_success($eventsList);
    }

    private function set_ply_activity($event) {
        $event['time'] = time();
        $ply_activity = $this->get_ply_activity();
        if(!is_array($ply_activity)){
            $ply_activity = [];
        }
        $ply_activity[] = $event;
        platform_wc_set_wc_session('ply_activity', $ply_activity);
    }

    private function get_ply_activity() {
        return platform_wc_get_wc_session('ply_activity');
    }

    private function unset_ply_activity() {
        platform_wc_unset_wc_session('ply_activity');
    }

    /**
     * User registration hook
     * @param $user_id
     */
    public function on_user_registered($user_id) {
        $currentUser = get_userdata($user_id);
        if ($currentUser === false) {
            return;
        }

        // As we don't have access to some metafields let's try to save those
        // from POST if present
        $first_name = !empty($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = !empty($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';

        $data = array(
            'status' => 'user_registered',
            'event_id' => false,
            'customer_id' => $currentUser->ID,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $currentUser->user_email,
            'ply_activity' => $this->get_ply_activity(),
            'platform_data' => array()
        );

        // Get Segments/Tags if exists
        if (!empty($this->options['segments_registered'])) {
            $data['platform_data']['ply_segments'] = $this->options['segments_registered'];
        }
        if (!empty($this->options['tags_registered'])) {
            $data['platform_data']['ply_tags'] = $this->options['tags_registered'];
        }
        if (!empty($data['platform_data'])) {
            $projectId = platformly_wc_get_option('platformly-wc-project-id');
            $data['platform_data']['ply_projects'] = array($projectId);
        }

        // API
        $ipnUrl = isset($this->options['platformly-wc-ipn-url']) ? $this->options['platformly-wc-ipn-url'] : false;
        $api = new Platformly_WooCommerce_PlatformIpnApi($ipnUrl);
        $api->post($data);

        // Remove 'ply_activity' from session
        $this->unset_ply_activity();
    }

    protected function is_enabled_abandoned_cart() {
        return empty($this->options['events']['abandoned_cart']) ? false : true;
    }

    protected function is_enabled_abandoned_cart_recovered() {
        return empty($this->options['events']['abandoned_cart_recovered']) ? false : true;
    }

    protected function getPriceOfProductsVariation($productId, $variationId) {
        $product = new WC_Product_Variable($productId);
        $variations = $product->get_available_variations();
        foreach ($variations as $variation) {
            if($variation['variation_id'] == $variationId) {
                if (!empty($variation['display_price'])) {
                    return $variation['display_price'];
                } else {
                    return $variation['display_regular_price'];
                }
            }
        }
    }
    
    public function platformly_wc_get_project_code_callback(){
        $projectId = absint($_POST['platformly_wc_project_id']);
        if(!empty($_POST['apiKey'])){
            $projectCode = Platformly_WooCommerce_PlatformApi::get_instance(sanitize_text_field($_POST['apiKey']))->getProjectCode($projectId);
        }else{
            $projectCode = Platformly_WooCommerce_PlatformApi::get_instance()->getProjectCode($projectId);
        }
        if($projectCode === false){
            wp_send_json_error($projectCode);
        }
        wp_send_json_success($projectCode);
    }
    
    public function setProjectCode(){
        $projectCode = platformly_wc_get_option('platformly-wc-project-code');
        if(!empty($projectCode) && empty(platform_wc_get_ply_official_project_id())){
            echo wp_specialchars_decode($projectCode, ENT_QUOTES);
        }
    }

    public function add_ply_params_to_received_url($order_received_url, $order){
        $plyId = platformly_wc_get_ply_id();
        $order_received_url = add_query_arg(
            array(
                'wccheckout' => $order->get_id().'-'.$plyId,
            ),
            $order_received_url
        );
        return $order_received_url;
    }
    
    protected function remove_ply_cookie(){
        if(isset($_COOKIE['_ply'])){
            unset($_COOKIE['_ply']);
            setcookie('_ply', null, 1, '/');
        }
    }
    
    public function platform_ly_project_changed($projectId){
        if(!$this->platform_wc_check_project_id($projectId)){
            $projectCode = platform_wc_get_ply_official_project_code();
            platformly_wc_set_option('platformly-wc-project-id', $projectCode['ply_project_id']);
            platformly_wc_set_option('platformly-wc-project-code', $projectCode['ply_project_code']);
            platformly_wc_set_option('platformly-wc-ipn-url', '');
        };
    }
    
    protected function platform_wc_check_project_id($plyProjctId){
        $plyWcPid = platformly_wc_get_option('platformly-wc-project-id');
        return $plyProjctId == $plyWcPid;
    }
    
    public function platformly_wc_check_user($userId){
        $platformlyOfficialUserId = get_option('ply_plugin_cid');
        if($platformlyOfficialUserId && $platformlyOfficialUserId != $userId){
            return false;
        }
        return true;
    }
}

function ply_wc_remove_old_abandoned_carts_f() {
    $options = get_option('platformly-woocommerce');
    $abandonedCartOptions = $options['abandoned_cart'];
    if (empty($abandonedCartOptions['remove_after_days'])) {
        return;
    }

    $daysBeforeRemove = $abandonedCartOptions['remove_after_days'];
    $timestampToRemove = time() - $daysBeforeRemove * 86400;

    global $wpdb;
    $table = $wpdb->prefix.'ply_wc_abandoned_carts';
    $query = "DELETE FROM `$table` WHERE `time` <= $timestampToRemove";
    $wpdb->query( $query );
}

Platformly_WooCommerce::instance()->init();
