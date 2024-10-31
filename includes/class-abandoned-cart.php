<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Platformly_WooCommerce_Abandoned_Cart extends Platformly_WooCommerce_Options {

    public static $timeDefault = 10;
    public static $removeAfterDays = 10;

    public function __construct() {
    }

    public function abandonedCartTurnOn() {
        $this->send_abandoned_cart_settings(1);
    }

    public function abandonedCartTurnOff() {
        $this->send_abandoned_cart_settings(0);

        // Delete all records
        global $wpdb;
        $table = $wpdb->prefix . 'ply_wc_abandoned_carts';
        $query = "DELETE FROM `$table`";
        $wpdb->query($query);

        // Remove cron event for autoremove
        if (wp_next_scheduled('ply_wc_remove_old_abandoned_carts')) {
            wp_clear_scheduled_hook( 'ply_wc_remove_old_abandoned_carts' );
        }
    }

    protected function send_abandoned_cart_settings($enabled) {
        $ipnUrl = platformly_wc_get_option('platformly-wc-ipn-url', false);
        $api = new Platformly_WooCommerce_PlatformIpnApi($ipnUrl);
        return $api->post(array('status' => 'abandoned_cart', 'enabled' => $enabled, 'rest_api_url' => get_platformly_wc_rest_url()));
    }

    public function store_cart() {
        // Get cart and check if it's empty
        $current_cart = WC()->session->cart;
        if (empty($current_cart)) {
            // Empty cart. Maybe user clear it, so try to delete existing record
            $abandoned_cart_id = platform_wc_get_wc_session('abandoned_cart_id');
            if (!empty($abandoned_cart_id)) {
                $this->deleteAbandonedCartRecord($abandoned_cart_id);
                platform_wc_unset_wc_session('abandoned_cart_id');
            }
            return;
        }

        $user_id = get_current_user_id();
        // Get cart total data
        $current_cart_totals = WC()->session->cart_totals;
        if(empty($current_cart_totals)){
            $current_cart_totals = [];
        }

        $cart_data = json_encode([
            'cart' => $current_cart,
            'cart_totals' => $current_cart_totals,
        ]);

        global $wpdb;
        $table = $wpdb->prefix.'ply_wc_abandoned_carts';
        $current_time = time();

        // Try to get existing abandoned cart
        $query = "SELECT id FROM `$table` WHERE `user_id` = %d AND `abandoned` = 0";
        $results = $wpdb->get_results( $wpdb->prepare( $query, $user_id ) );

        if ( 0 === count( $results ) ) {
            // Insert new record
            $insert_query = "INSERT INTO `$table` ( `user_id`, `cart_data`, `time`, `abandoned` ) VALUES ( %d, %s, %d, 0 )";
            $wpdb->query( $wpdb->prepare( $insert_query, $user_id, $cart_data, $current_time ) );
            $abandoned_cart_id = $wpdb->insert_id;
            platform_wc_set_wc_session( 'abandoned_cart_id', $abandoned_cart_id );
        } else {
            // Update existing record
            $query_update = "UPDATE `$table` SET `cart_data` = %s, `time` = %d WHERE `user_id` = %d AND `abandoned` = 0";
            $wpdb->query( $wpdb->prepare( $query_update, $cart_data, $current_time, $user_id ) );

            // Get abandoned_cart_id
            $query = "SELECT id FROM `$table` WHERE `user_id` = %s AND `abandoned` = 0";
            $get_abandoned_record = $wpdb->get_results( $wpdb->prepare( $query, $user_id ) );
            if ( count( $get_abandoned_record ) > 0 ) {
                $abandoned_cart_id = $get_abandoned_record[0]->id;
                platform_wc_set_wc_session( 'abandoned_cart_id', $abandoned_cart_id );
            }
        }
    }

    /**
     * Process abandoned cart after placing the order
     */
    public function deleteAbandonedCartAfterPlacingOrder() {
        // If abandoned cart was restored
        $restored_abandoned_cart_id = platform_wc_get_wc_session('restored_abandoned_cart_id');
        if (!empty($restored_abandoned_cart_id)) {
            $this->triggerEventAbandonedCartRecovered($restored_abandoned_cart_id);
            $this->deleteAbandonedCartRecord($restored_abandoned_cart_id);
            platform_wc_unset_wc_session('restored_abandoned_cart_id');
        }

        $abandoned_cart_id = platform_wc_get_wc_session('abandoned_cart_id');
        if ($abandoned_cart_id !== false) {
            $this->deleteAbandonedCartRecord($abandoned_cart_id);
            platform_wc_unset_wc_session('abandoned_cart_id');
        }
    }

    public function triggerEventAbandonedCartRecovered($recovered_abandoned_cart_id) {
        $events = platformly_wc_get_option('events', FALSE);
        if (empty($events['abandoned_cart_recovered'])) {
            return;
        }

        $abandoned_cart = $this->getAbandonedCartRecord($recovered_abandoned_cart_id);

        $data = array(
            'status' => 'abandoned_cart_recovered',
            'event_id' => $events['abandoned_cart_recovered'],
            'restore_cart_key' => $abandoned_cart->restore_cart_key
        );

        // Get current user
        $currentUser = wp_get_current_user();
        $data['customer_id'] = $currentUser->ID;
        $data['first_name'] = $currentUser->user_firstname;
        $data['last_name'] = $currentUser->user_lastname;
        $data['email'] = $currentUser->user_email;

        // API
        $ipnUrl = platformly_wc_get_option('platformly-wc-ipn-url');
        $api = new Platformly_WooCommerce_PlatformIpnApi($ipnUrl);
        $api->post($data);
    }

    /*
     * Delete record from table
     */
    protected function getAbandonedCartRecord($abandoned_cart_id) {
        global $wpdb;
        $table = $wpdb->prefix.'ply_wc_abandoned_carts';
        $query = "SELECT * FROM `$table` WHERE `id` = %d";
        $result = $wpdb->get_results( $wpdb->prepare( $query, $abandoned_cart_id ) );
        return $result;
    }

    /*
     * Delete record from table
     */
    protected function deleteAbandonedCartRecord($abandoned_cart_id) {
        global $wpdb;
        $table = $wpdb->prefix.'ply_wc_abandoned_carts';
        $query = "DELETE FROM `$table` WHERE `id` = %d";
        $wpdb->query( $wpdb->prepare( $query, $abandoned_cart_id ) );
    }

    /**
     * Recover abandoned cart by restore link
     */
    public function track_abandoned_cart_link($template) {
        if (!empty($_GET['ply_wc_restore_cart'])) {
            $restore_code = $_GET['ply_wc_restore_cart'];

            global $wpdb;
            $table = $wpdb->prefix.'ply_wc_abandoned_carts';
            $query = "SELECT id FROM `$table` WHERE `user_id` = %d AND `restore_cart_key` = %s";
            $abandonedCart = $wpdb->get_results( $wpdb->prepare( $query, get_current_user_id(), $restore_code ) );

            if (!empty($abandonedCart)) {
                platform_wc_set_wc_session('restored_abandoned_cart_id', $abandonedCart[0]->id);
            }
        }

        return $template;
    }
}
