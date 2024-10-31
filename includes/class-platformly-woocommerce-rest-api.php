<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Platformly_WooCommerce_Rest_Api{
    protected static $namespace = 'platformly-for-woocommerce/v1';
    
    /**
     * Register all API routes.
     */
    public function register_routes()
    {
        $this->register_order_count();
        $this->register_orders();
        $this->register_abandoned_carts();
        $this->register_customers();
        $this->register_completed_sync();
    }
    
    /**
     * Prepare REST response
     * @param array $data
     * @param type $status
     * @return WP_REST_Response|mixed
     */
    private function rest_response($data, $status = 200){
        if(!is_array($data)){
            $data = array();
        }
        $response = rest_ensure_response($data);
        $response->set_status($status);
        return $response;
    }
    
    protected function register_order_count(){
        register_rest_route(static::$namespace, '/order_count', array(
            'methods' => 'GET',
            'callback' => array($this, 'order_count'),
            'permission_callback' => '__return_true'
        ));
    }
    protected function register_orders(){
        register_rest_route(static::$namespace, '/orders', array(
            'methods' => 'GET',
            'callback' => array($this, 'orders'),
            'permission_callback' => '__return_true'
        ));
    }
    protected function register_abandoned_carts(){
        register_rest_route(static::$namespace, '/abandoned_carts', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_abandoned_carts'),
            'permission_callback' => '__return_true'
        ));
    }
    protected function register_customers(){
        register_rest_route(static::$namespace, '/customers', array(
            'methods' => 'GET',
            'callback' => array($this, 'customers'),
            'permission_callback' => '__return_true'
        ));
    }
    protected function register_completed_sync(){
        register_rest_route(static::$namespace, '/completed_sync', array(
            'methods' => 'GET',
            'callback' => array($this, 'completed_sync'),
            'permission_callback' => '__return_true'
        ));
    }
    public function order_count(WP_REST_Request $request){
        $params = $request->get_params();
        $statuses = explode(',', $params['status']);
        $orderParams = array(
            'paginate' => true,
            'limit' => 1,
            'status' => $statuses,
            'parent' => 0
        );
        if(isset($params['from']) && !empty($params['from'])){
            $orderParams['date_modified'] = $params['from'];
        }else{
            $orderParams['date_modified'] = '01/01/1970';
        }
        if(isset($params['from']) && !empty($params['from'])){
            $orderParams['date_modified'] = $params['from'];
        }else{
            $orderParams['date_modified'] = '01/01/1970';
        }
        if(isset($params['to']) && !empty($params['to'])){
            $orderParams['date_modified'] .= "...".$params['to'];
        }else{
            $orderParams['date_modified'] .= "...".time();
        }
        $results = wc_get_orders($orderParams);
        $countOrders = $results->total;
        return $this->rest_response(array('number_orders' => $countOrders));
    }
    public function orders(WP_REST_Request $request){
        $params = $request->get_params();
        $statuses = explode(',', $params['status']);
        if(isset($params['page']) && $params['page'] > 0){
            $page = (int)$params['page'];
        }else{
            $page = 1;
        }
        $orderParams = array(
            'paginate' => true,
            'limit' => 10,
            'status' => $statuses,
            'parent' => 0,
            'paged' => $page
        );
        if(isset($params['from']) && !empty($params['from'])){
            $orderParams['date_modified'] = $params['from'];
        }else{
            $orderParams['date_modified'] = '01/01/1970';
        }
        if(isset($params['to']) && !empty($params['to'])){
            $orderParams['date_modified'] .= "...".$params['to'];
        }else{
            $orderParams['date_modified'] .= "...".time();
        }
        $results = wc_get_orders($orderParams);
        //wcs_get_subscriptions()
        $next_page = $results->max_num_pages == $page ? $page : $page++;
        $prev_page = $page == 1 ? 1 : $page--;
        $orders = array();
        $childOrders = array();
        foreach($results->orders as $order){
            $orderId = $order->get_id();
            $orders[] = platformly_wc_get_order_data($orderId);
            $childResult = wc_get_orders(array(
                'status' => $statuses,
                'parent' => $orderId
            ));
            foreach($childResult as $childOrder){
                $childOrders[$orderId][] = platformly_wc_get_order_data($childOrder->get_id());
            }
        }
        $response = array('orders' => $orders, 'child_orders' => $childOrders, 'next_page' => $next_page, 'prev_page' => $prev_page, 'max_num_pages' => $results->max_num_pages, 'total_orders' => $results->total);
        return $this->rest_response($response);
    }

    /**
     * Get list of abandoned carts if exists
     * @global type $wpdb
     * @return WP_REST_Response|mixed
     */
    public function get_abandoned_carts() {
        // Check is abandoned cart event configured
        $events = platformly_wc_get_option('events');
        if (empty($events['abandoned_cart'])) {
            $response = array('error' => 1, 'msg' => 'Abandoned cart event is not configured');
            return $this->rest_response($response);
        }

        // Check is abandoned cart time configured
        $abandonedCart = platformly_wc_get_option('abandoned_cart', false);
        if ($abandonedCart === false || empty($abandonedCart['time'])) {
            $response = array('error' => 1, 'msg' => 'Abandoned cart is not configured');
            return $this->rest_response($response);
        }

        $event_id = $events['abandoned_cart'];
        $abandoned_time = time() - $abandonedCart['time'] * 60;

        global $wpdb;
        $tableAcHistory = $wpdb->prefix.'ply_wc_abandoned_carts';
        $query = "SELECT ac.id, ac.user_id, ac.cart_data FROM `$tableAcHistory` ac WHERE `time` <= $abandoned_time AND `abandoned` = 0";
        $results = $wpdb->get_results( $query );

        if (count($results) === 0) {
            $response = array('error' => 0, 'abandoned_carts' => array(), 'total' => 0);
            return $this->rest_response($response);
        }

        $abandoned_carts = array();
        foreach ($results as $abandoned_cart) {
            $user_data = get_user_by('ID', $abandoned_cart->user_id);
            $user_meta = get_user_meta($abandoned_cart->user_id);
            $restore_cart_key = bin2hex(random_bytes(16));

            $cart_data = json_decode($abandoned_cart->cart_data, true);

            $cart = array();
            if(isset($cart_data['cart'])){
                $data = $cart_data['cart'];
            }else{
                $data = $cart_data;
            }
            foreach ($data as $_item) {
                $product = wc_get_product( $_item['product_id'] );

                $product_id = $product->get_id();
                $item = array(
                    'name' => $product->get_title(),
                    'product_link' => $product->get_permalink(),
                    'thumbnail' => platform_wc_get_product_thumbnail($product_id),
                    'sku' => $product->get_sku(),
                    'price' => $product->get_price(),
                    'currency' => get_woocommerce_currency_symbol(),
                    'quantity' => $_item['quantity'],
                    'subtotal' => $_item['line_subtotal'],
                    'total' => $_item['line_total'],
                    'tax' => $_item['line_tax']
                );
                $cart[$product_id] = $item;
            }

            $abandoned_carts[] = array(
                'event_id' => $event_id,
                'customer_id' => $abandoned_cart->user_id,
                'email' => $user_data->user_email,
                'first_name' => $user_meta['first_name'][0],
                'last_name' => $user_meta['last_name'][0],
                'cart' => $cart,
                'ply_wc_restore_cart' => $restore_cart_key,
                'cart_totals' => isset($cart_data['cart_totals']) ? $cart_data['cart_totals'] : []
            );

            // Assign restore code, mark cart as 'abandoned'
            $query = "UPDATE `$tableAcHistory` SET `restore_cart_key` = '$restore_cart_key', `abandoned` = 1 WHERE `id` = ".$abandoned_cart->id;
            $wpdb->query( $query );
        }

        $cart_page = wc_get_cart_url();
        $checkout_page = wc_get_checkout_url();

        $response = array('error' => 0, 'abandoned_carts' => $abandoned_carts, 'cart_page' => $cart_page, 'checkout_page' => $checkout_page);
        return $this->rest_response($response);
    }
    
    public function customers(WP_REST_Request $request){
        global $wpdb;
        $params = $request->get_params();
        if(isset($params['page']) && $params['page'] > 0){
            $page = intval($params['page']);
        }else{
            $page = 1;
        }
        $perPage = 10;
        $startItem = ($page-1) * $perPage;
        $queryLimit = "LIMIT {$startItem},{$perPage}";
        $customer_data = $wpdb->get_results(
            "SELECT * FROM wp_wc_customer_lookup ".$queryLimit, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            ARRAY_A
        );
        $db_records_count = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM wp_wc_customer_lookup" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        );
        $total_pages = (int)ceil($db_records_count / $perPage);
        $next_page = $total_pages == $page ? $page : $page++;
        $prev_page = $page == 1 ? 1 : $page--;
        
        $response = array('customers' => $customer_data, 'next_page' => $next_page, 'prev_page' => $prev_page, 'max_num_pages' => $total_pages, 'total_customers' => $db_records_count);
        return $this->rest_response($response);
    }
    
    public function completed_sync(WP_REST_Request $request){
        $syncContacts = platformly_wc_get_option('sync_contacts');
        $syncSales = platformly_wc_get_option('sync_sales');
        if($syncContacts === true && $syncSales === true){
            platformly_wc_set_option('sync_progress', 'disabled');
        }else{
            platformly_wc_set_option('sync_progress', 'completed');
        }
        if($syncContacts === true){
            platformly_wc_set_option('sync_contacts_finished', true);
        }
        if($syncSales === true){
            platformly_wc_set_option('sync_sales_finished', true);
        }
    }
}
//wcs_get_subscriptions
