<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function dubdp_add_admin_menu() {
    add_menu_page('Bulk Discount Rules', 'Bulk Discounts', 'manage_options', 'bulk-discount-rules', 'dubdp_discount_rules_page');
}
add_action('admin_menu', 'dubdp_add_admin_menu');

// Display Discount Rules Page
function dubdp_discount_rules_page() {
    if (isset($_POST['save_discount_rule'])) {
        dubdp_save_discount_rule();
    }
    dubdp_display_discount_rules();
}

// Displaying Discount Rules
function dubdp_display_discount_rules() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'bulk_discount_rules';
    $rules = $wpdb->get_results("SELECT * FROM $table_name");

    // Check if an edit request has been made
    $edit_rule = null;
    if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
        $edit_rule = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_GET['edit']));
    }

    ?>
    <div class="wrap">
        <h1>Manage Bulk Discount Rules</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="start_range">Start Range</label></th>
                    <td><input name="start_range" type="number" step="0.01" required value="<?php echo esc_attr($edit_rule->start_range ?? ''); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="end_range">End Range</label></th>
                    <td><input name="end_range" type="number" step="0.01" required value="<?php echo esc_attr($edit_rule->end_range ?? ''); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="discount_type">Discount Type</label></th>
                    <td>
                        <select name="discount_type" required>
                            <option value="fixed" <?php selected($edit_rule->discount_type ?? '', 'fixed'); ?>>Fixed Discount</option>
                            <option value="percentage" <?php selected($edit_rule->discount_type ?? '', 'percentage'); ?>>Percentage Discount</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="discount_amount">Discount Amount</label></th>
                    <td><input name="discount_amount" type="number" step="0.01" required value="<?php echo esc_attr($edit_rule->discount_amount ?? ''); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="applied_on">Applied On</label></th>
                    <td>
                        <select name="applied_on" id="applied_on" required>
                            <option value="all" <?php selected($edit_rule->applied_on ?? '', 'all'); ?>>All Products</option>
                            <option value="single" <?php selected($edit_rule->applied_on ?? '', 'single'); ?>>Single Product</option>
                        </select>
                    </td>
                </tr>
                <tr id="product_id_row" style="display: none;">
                    <th><label for="product_id">Product ID</label></th>
                    <td><input name="product_id" type="number" value="<?php echo esc_attr($edit_rule->product_id ?? ''); ?>" /></td>
                </tr>
            </table>
            <p class="submit">
                <input type="hidden" name="discount_id" value="<?php echo esc_attr($edit_rule->id ?? ''); ?>" />
                <input type="submit" name="save_discount_rule" class="button-primary" value="<?php echo $edit_rule ? 'Update Discount Rule' : 'Save Discount Rule'; ?>">
            </p>
        </form>

        <h2>Existing Rules</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Start Range</th>
                    <th>End Range</th>
                    <th>Discount Type</th>
                    <th>Discount Amount</th>
                    <th>Applied On</th>
                    <th>Product ID</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rules as $rule): ?>
                    <tr>
                        <td><?php echo esc_html($rule->id); ?></td>
                        <td><?php echo esc_html($rule->start_range); ?></td>
                        <td><?php echo esc_html($rule->end_range); ?></td>
                        <td><?php echo esc_html($rule->discount_type); ?></td>
                        <td><?php echo esc_html($rule->discount_amount); ?></td>
                        <td><?php echo esc_html($rule->applied_on); ?></td>
                        <td><?php echo esc_html($rule->product_id); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=bulk-discount-rules&edit=' . $rule->id); ?>">Edit</a> | 
                            <a href="<?php echo admin_url('admin.php?page=bulk-discount-rules&delete=' . $rule->id); ?>" onclick="return confirm('Are you sure you want to delete this rule?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- Dynamic Product ID Field -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const appliedOnField = document.getElementById('applied_on');
            const productIdRow = document.getElementById('product_id_row');

            appliedOnField.addEventListener('change', function() {
                if (this.value === 'single') {
                    productIdRow.style.display = 'table-row';
                } else {
                    productIdRow.style.display = 'none';
                }
            });

            if (appliedOnField.value === 'single') {
                productIdRow.style.display = 'table-row';
            }
        });
    </script>
    <?php
}

// Handling Form Submission 
function dubdp_save_discount_rule() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'bulk_discount_rules';

    $data = [
        'start_range' => floatval($_POST['start_range']),
        'end_range' => floatval($_POST['end_range']),
        'discount_type' => sanitize_text_field($_POST['discount_type']),
        'discount_amount' => floatval($_POST['discount_amount']),
        'applied_on' => sanitize_text_field($_POST['applied_on']),
        'product_id' => ($_POST['applied_on'] === 'single') ? intval($_POST['product_id']) : null,
    ];

    if (!empty($_POST['discount_id'])) {
        $wpdb->update($table_name, $data, ['id' => intval($_POST['discount_id'])]);
    } else {
        $wpdb->insert($table_name, $data);
    }

    wp_redirect(admin_url('admin.php?page=bulk-discount-rules'));
    exit;
}

// Deleting Discount Rule
function dubdp_delete_discount_rule() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'bulk_discount_rules';

    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $wpdb->delete($table_name, ['id' => intval($_GET['delete'])]);
        wp_redirect(admin_url('admin.php?page=bulk-discount-rules'));
        exit;
    }
}
add_action('admin_init', 'dubdp_delete_discount_rule');
