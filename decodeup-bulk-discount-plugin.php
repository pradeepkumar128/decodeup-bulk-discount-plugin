<?php
/**
 * Plugin Name: DecodeUp practical WooCommerce Bulk Discount Plugin
 * Description: DecodeUp practical
 * Version: 1.1
 * Author: DecodeUP
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('DUBDP_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Include necessary files
require_once DUBDP_PLUGIN_DIR . 'includes/admin.php';
require_once DUBDP_PLUGIN_DIR . 'includes/functions.php';

// Activation hook
function dubdp_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'bulk_discount_rules';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        start_range float NOT NULL,
        end_range float NOT NULL,
        discount_type varchar(10) NOT NULL,
        discount_amount float NOT NULL,
        applied_on varchar(10) NOT NULL,
        product_id mediumint(9) DEFAULT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'dubdp_activate');

// Deactivation hook
function dubdp_deactivate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'bulk_discount_rules';
    $sql = "DROP TABLE IF EXISTS $table_name;";
    $wpdb->query($sql);
}

register_deactivation_hook(__FILE__, 'dubdp_deactivate');
