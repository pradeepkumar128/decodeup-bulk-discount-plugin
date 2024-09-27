<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add custom discount logic during checkout
add_action('woocommerce_cart_calculate_fees', 'dubdp_apply_bulk_discount');
function dubdp_apply_bulk_discount() {
    global $woocommerce;

    // Initialize discount variable
    $discount = 0;

    // Fetch discount rules
    global $wpdb;
    $table_name = $wpdb->prefix . 'bulk_discount_rules';
    $rules = $wpdb->get_results("SELECT * FROM $table_name");

    // Iterate over each item in the cart
    $items = $woocommerce->cart->get_cart();
    foreach ($items as $item) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];
        $product_price = $item['data']->get_price(); // Fetch product price

        // Check discount rules for each item
        foreach ($rules as $rule) {
            // Condition: Check if rule is applied for 'all' products or specific 'single' product
            if ($rule->applied_on === 'all' || ($rule->applied_on === 'single' && $rule->product_id == $product_id)) {
                if ($quantity >= $rule->start_range && $quantity <= $rule->end_range) {
                    if ($rule->discount_type == 'fixed') {
                        // Apply fixed discount based on the discount amount
                        $discount += $rule->discount_amount;
                    } elseif ($rule->discount_type == 'percentage') {
                        // Calculate percentage discount based on product's total price (price * quantity)
                        $discount += ($rule->discount_amount / 100) * ($product_price * $quantity);
                    }
                }
            }
        }
    }

    // Apply discount if applicable
    if ($discount > 0) {
        $woocommerce->cart->add_fee(__('Bulk Discount', 'woocommerce'), -$discount);
    }
}

// Fetch all discount rules and manage  discount rules
class DecodeUp_Bulk_Discount_Rules {
    public static function get_all_discount_rules() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bulk_discount_rules';
        return $wpdb->get_results("SELECT * FROM $table_name");
    }
}

// Display pricing table on single product page
add_action('woocommerce_single_product_summary', 'decodeup_show_pricing_table', 20);

function decodeup_show_pricing_table() {
    $discount_rules = DecodeUp_Bulk_Discount_Rules::get_all_discount_rules();
    global $product;

    if ($discount_rules) {
        echo '<h3 style="color: #0073aa;">Bulk Discount Pricing</h3>';
        echo '<table class="woocommerce-bulk-discount-table" style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
        echo '<tr style="background-color: #0073aa; color: white; text-align: left;">';
        echo '<th style="padding: 10px; border: 1px solid #ddd;">Quantity</th>';
        echo '<th style="padding: 10px; border: 1px solid #ddd;">Discount</th>';
        echo '</tr>';

        foreach ($discount_rules as $rule) {
            // Display rule only if it's applied to all products or to this specific product
            if ($rule->applied_on === 'all' || ($rule->applied_on === 'single' && $rule->product_id == $product->get_id())) {
                echo '<tr style="border-bottom: 1px solid #ddd;">';
                echo '<td style="padding: 10px; border: 1px solid #ddd;">' . esc_html($rule->start_range) . ' - ' . esc_html($rule->end_range) . '</td>';
                echo '<td style="padding: 10px; border: 1px solid #ddd;">' . esc_html($rule->discount_amount) . ' ' . ($rule->discount_type === 'percentage' ? '%' : 'Fixed') . '</td>';
                echo '</tr>';
            }
        }

        echo '</table>';
    }
}
