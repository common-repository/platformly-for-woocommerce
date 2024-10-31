<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function platformly_wc_is_configured($status = null){
    return (bool)platformly_wc_get_api_key() && platform_wc_check_forward($status);
}
/**
 * @param $key
 * @param null $default
 * @return null
*/
function platformly_wc_get_option($key, $default = null) {
    $options = get_option('platformly-woocommerce');
    if(!is_array($options)){
        return $default;
    }
    if(!array_key_exists($key, $options)){
        return $default;
    }
    return $options[$key];
}

function platformly_wc_set_option($key, $value){
    $options = get_option('platformly-woocommerce');
    if(is_array($options)){
        update_option('platformly-woocommerce', array_merge(get_option('platformly-woocommerce'), array($key => $value)));
    }else{
        update_option('platformly-woocommerce', array($key => $value));
    }
    //update_option('platformly-woocommerce'.'-'.$key, $value, 'yes');
}

function platformly_wc_get_order_data($orderId){
    $order = wc_get_order($orderId);

    if(!empty($order)){
        $orderData = $order->get_data();
        $orderData['refund_data'] = array();
        $orderData['refund_id'] = '';

        // Date modified
        $dateModified = $order->get_date_modified();
        if ($dateModified !== null) {
	        $dateModified = $dateModified->getOffsetTimestamp();
        }
	    $orderData['date_modified_timestamp'] = $dateModified;

        // Date completed
        if(!empty($orderData['date_completed'])){
            $dateCompleted = $order->get_date_completed();
            if ($dateCompleted !== null) {
                $dateCompleted = $dateCompleted->getOffsetTimestamp();
            }
            $orderData['date_completed_timestamp'] = $dateCompleted;
        }

        if(method_exists($order, 'get_refunds')){
            $refunds = $order->get_refunds();
            if(!empty($refunds)){
                foreach($refunds as $refund){
                    $refund_id = $refund->get_id();
                    $orderData['refund_data'][$refund_id]['refund_id'] = $refund_id;
                    $orderData['refund_data'][$refund_id]['amount'] = $refund->get_amount();
                    $orderData['refund_data'][$refund_id]['currency'] = $refund->get_currency();
                    $orderData['refund_data'][$refund_id]['reason'] = $refund->get_reason();
                    $orderData['refund_data'][$refund_id]['date_created'] = $refund->get_date_created();
                    $orderData['refund_data'][$refund_id]['date_created_timestamp'] = $refund->get_date_created()->getOffsetTimestamp();
                    $orderData['refund_data'][$refund_id]['refunded_by'] = $refund->get_refunded_by();
                }
            }
        }
        
        $orderData['items_data'] = array();
        $items = $order->get_items();
        
        foreach($items as $itemId => $item){
            $product = $item->get_product();

            // Get PLY settings from product meta
            if(platformly_wc_check_enable_ply()){
                $platformlyData = get_post_meta($item->get_product_id(), 'platformly-woocommerce', true);
                if (!empty($platformlyData)) {
                    $projectId = platformly_wc_get_option('platformly-wc-project-id');
                    $platformlyData['ply_projects'] = array($projectId);
                }
                $orderData['items_data'][$itemId]['platformlyData'] = $platformlyData;
            }

            $orderData['items_data'][$itemId]['item_id'] = $itemId;
            $orderData['items_data'][$itemId]['product_link'] = $product ? $product->get_permalink() : '';
            //$orderData['items_data'][$itemId]['thumbnail'] = $product ? $product->get_image('thumbnail', array('title' => ''), false) : '';
            if($product &&  has_post_thumbnail($item->get_product_id())){
                $attachment_ids = get_post_thumbnail_id($item->get_product_id());
                $orderData['items_data'][$itemId]['thumbnail'] = wp_get_attachment_image_url($attachment_ids, 'thumbnail');
            }else{
                $orderData['items_data'][$itemId]['thumbnail'] = '';
            }
            $orderData['items_data'][$itemId]['product_id'] = $item->get_product_id();
            $orderData['items_data'][$itemId]['name'] = $item->get_name();
            $orderData['items_data'][$itemId]['sku'] = $product->get_sku();
            $orderData['items_data'][$itemId]['item_subtotal'] = $order->get_item_subtotal($item, false, true);
            $orderData['items_data'][$itemId]['item_total'] = $order->get_item_total($item, false, true);
            $orderData['items_data'][$itemId]['quantity'] = $item->get_quantity();
            $orderData['items_data'][$itemId]['total'] = $item->get_total();
            $orderData['items_data'][$itemId]['currency'] = $order->get_currency();
            $orderData['items_data'][$itemId]['discount'] = 0;
            if(method_exists($order, 'get_total_refunded_for_item')){
                $orderData['items_data'][$itemId]['refunded'] = $order->get_total_refunded_for_item($itemId);
            }
            if($item->get_subtotal() !== $item->get_total()){
                $orderData['items_data'][$itemId]['discount'] = $item->get_subtotal() - $item->get_total();
            }
            $tax_data = wc_tax_enabled() ? $item->get_taxes() : false;
            if($tax_data){
            	$orderTaxes = $order->get_taxes();
            	if (!empty($orderTaxes)) {
		            $orderData['items_data'][$itemId]['order_taxes'] = $orderTaxes;
		            foreach($orderTaxes as $taxItem){
                        $tax_item_id = $taxItem->get_rate_id();
                        $orderData['items_data'][$itemId]['tax_item_total'] = isset($tax_data['total'][$tax_item_id]) ? $tax_data['total'][$tax_item_id] : '';
                        $orderData['items_data'][$itemId]['tax_item_subtotal'] = isset($tax_data['subtotal'][$tax_item_id]) ? $tax_data['subtotal'][$tax_item_id] : '';
                    }
	            }
            }
        }
        
        if(isset($_COOKIE['_ply'])){
            $orderData['_ply'] = $_COOKIE['_ply'];
        }
        
        return $orderData;
    }else{
        return false;
    }
}

function platformly_wc_get_api_key(){
    return platformly_wc_get_option('platformly-wc-ipn-url', false);
}

function platformly_wc_check_api_acceess($apiKey = null){
    if($apiKey === null){
        $apiKey = platformly_wc_get_option('API_key', null);
    }
    if(!empty($apiKey)){
        $check = wp_remote_get(PLATFORMLY_WC_URL."/plugin/plugin.check.key.php?plugin_key=".$apiKey);
        $check = wp_remote_retrieve_body($check);
        if($check){ //check if request is completed
            $check = json_decode($check, true);
            if($check['status'] == 'not_found'){ //if public key is not found block the plugin
                update_option('platformly_wc_cid', '');
            }else{
                $ply_plugin_cid = get_option('platformly_wc_cid');
                if($ply_plugin_cid != $check['id']){
                    update_option('platformly_wc_cid', $check['id']);
                }
                return true;
            }
        }
    }
    return false;
}

/**
 * @return bool
 */
function platformly_check_woocommerce_plugin_status(){
    // if you are using a custom folder name other than woocommerce just define the constant to TRUE
    if(defined("RUNNING_CUSTOM_WOOCOMMERCE") && RUNNING_CUSTOM_WOOCOMMERCE === true){
        return true;
    }
    // it the plugin is active, we're good.
    if(in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))){
        return true;
    }
    $plugins = get_site_option('active_sitewide_plugins');
    return isset($plugins['woocommerce/woocommerce.php']);
}

function platformly_wc_check_enable_ply(){
    $is_enable_PLY = platformly_wc_get_option('is_enable_PLY', false);
    return !empty($is_enable_PLY) ? true : false;
}

function platformly_wc_check_ipn_url($ipnUrl){
    $api = new Platformly_WooCommerce_PlatformIpnApi($ipnUrl);
    return $api->post(array('check_ipn' => true, 'rest_api_url' => get_platformly_wc_rest_url()), true);
}

function get_platformly_wc_rest_url(){
    return get_rest_url(null, 'platformly-for-woocommerce/v1', 'rest');
}

function platform_wc_check_forward($status = null){
    $alwaysAllowedForwardTransactions = array('add_to_cart');
    $forwardTransactions = platformly_wc_get_option('forward_transactions', array());
    if(empty($forwardTransactions) || in_array($status, $alwaysAllowedForwardTransactions) || in_array($status, $forwardTransactions)){
        return true;
    }else{
        return false;
    }
}

// WC session functions
function platform_wc_set_wc_session( $session_key, $session_value ) {
    WC()->session->set( $session_key, $session_value );
}
function platform_wc_get_wc_session( $session_key ) {
    if ( ! is_object( WC()->session ) ) {
        return false;
    }
    return WC()->session->get( $session_key );
}
function platform_wc_unset_wc_session( $session_key ) {
    if(is_object(WC()->session)){
        WC()->session->__unset($session_key);
    }
}

/**
 * Get product's thumbnail
 * @param type $product_id
 */
function platform_wc_get_product_thumbnail($product_id) {
    if (has_post_thumbnail($product_id)) {
        $attachment_ids = get_post_thumbnail_id($product_id);
        return wp_get_attachment_image_url($attachment_ids, 'thumbnail');
    } else {
        return '';
    }
}

function platform_wc_get_ply_official_project_code(){
    global $wpdb;
    $plyCid = get_option('ply_plugin_cid');
    if($plyCid){
        $sql = $wpdb->prepare("SELECT id, ply_project_code, ply_project_id  FROM {$wpdb->prefix}ply_project_code WHERE ply_cid = %d", array($plyCid));
        return $wpdb->get_row($sql, ARRAY_A);
    }
    return array();
}

function platform_wc_check_ply_official_plugin_is_active(){
    if(!function_exists( 'is_plugin_active')){
        include_once ABSPATH.'wp-admin/includes/plugin.php';
    }
    return is_plugin_active('platformly/platformly.php');
}

function platform_wc_get_ply_official_project_id(){
    $projectPlyOfficialCodeInclude = platform_wc_ply_official_code_include();
    if(platform_wc_check_ply_official_plugin_is_active() && $projectPlyOfficialCodeInclude){
        $projectCode = platform_wc_get_ply_official_project_code();
        if(isset($projectCode['ply_project_id'])){
            return $projectCode['ply_project_id'];
        }
    }
    return null;
}

function platform_wc_ply_official_code_include(){
    return get_option('ply_project_code_active');
}

/**
 * Get the integer value. Arrays are converted recursively.
 * Non-scalar values are ignored.
 *
 * @param string|array $var Data to converted.
 * @return string|array
 */
function platform_wc_intval($var){
	if(is_array($var)){
		return array_map('platform_wc_intval', $var);
	}else{
		return is_scalar($var) ? absint($var) : $var;
	}
}

/**
 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
 * Non-scalar values are ignored.
 *
 * @param string|array $var Data to sanitize.
 * @return string|array
 */
function platform_wc_clean($var){
	if(is_array($var)){
		return array_map( 'platform_wc_clean', $var);
	}else{
		return is_scalar($var) ? sanitize_text_field($var) : $var;
	}
}
/**
 * Get Platform ipn id from ipn url
*/
function platformly_wc_get_ply_id(){
    $options = get_option('platformly-woocommerce', array());
    if(isset($options['platformly-wc-ipn-url'])){
         $params = explode('/',parse_url($options['platformly-wc-ipn-url'], PHP_URL_PATH));
         if(!empty($params[2])){
             return $params[2];
         }
         return '';
    }
}