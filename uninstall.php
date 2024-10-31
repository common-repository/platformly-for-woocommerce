<?php

// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

delete_option('platformly-woocommerce');
delete_option('platformly_wc_cid');

global $wpdb;
$table_name = $wpdb->prefix . 'ply_wc_abandoned_carts';
$sql = 'DROP TABLE IF EXISTS ' . $table_name;
$wpdb->query($sql);
