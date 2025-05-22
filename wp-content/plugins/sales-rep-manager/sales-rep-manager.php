<?php
/**
 * Plugin Name: Sales Representatives Manager
 * Description: Manage Sales Representatives and update users based on state.
 * Version: 1.0
 * Author: Sunaina Udo
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'srm_add_admin_menu');
function srm_add_admin_menu() {
    add_menu_page(
        'Sales Representatives',
        'Sales Reps',
        'manage_options',
        'sales-reps-manager',
        'srm_settings_page'
    );
}

add_action('admin_init', 'srm_register_settings');
function srm_register_settings() {
    register_setting('srm_settings_group', 'srm_sales_reps');
}

function srm_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'srm_nonce')) {
            echo '<div class="error"><p>Security check failed. Please try again.</p></div>';
        } else {
            if (isset($_POST['srm_save_sales_reps'])) {
                $sales_reps = isset($_POST['srm_sales_reps']) ? $_POST['srm_sales_reps'] : [];

                $clean_sales_reps = [];
                foreach ($sales_reps as $rep) {
                    $clean_sales_reps[] = [
                        'name' => sanitize_text_field($rep['name']),
                        'email' => sanitize_email($rep['email']),
                        'state' => sanitize_text_field(strtoupper($rep['state'])),
                        'country' => sanitize_text_field($rep['country']),
                    ];
                }

                update_option('srm_sales_reps', $clean_sales_reps);

                echo '<div class="updated"><p>Sales Representatives saved.</p></div>';
            }

            if (isset($_POST['srm_update_users'])) {
                $sales_reps = get_option('srm_sales_reps', []);

                $state_name_map = [];
                foreach ($sales_reps as $rep) {
                    if (!empty($rep['state']) && !empty($rep['name'])) {
                        $state_name_map[$rep['state']] = $rep['name'];
                    }
                }

                $args = [
                    'meta_key' => 'billing_state',
                    'meta_compare' => 'EXISTS',
                    'number' => -1,
                ];
                $user_query = new WP_User_Query($args);
                $users = $user_query->get_results();

                $updated_count = 0;
                foreach ($users as $user) {
                    $user_id = $user->ID;
                    $user_state = get_user_meta($user_id, 'billing_state', true);
                    if (!$user_state) continue;
                    $user_state = strtoupper($user_state);

                    if (isset($state_name_map[$user_state])) {
                        update_user_meta($user_id, 'sales_representative', $state_name_map[$user_state]);
                        $updated_count++;
                    }
                }

                echo '<div class="updated"><p>Updated ' . esc_html($updated_count) . ' users with Sales Representative.</p></div>';
            }
        }
    }

    $sales_reps = get_option('srm_sales_reps', []);
    ?>
    <div class="wrap">
        <h1>Sales Representatives Manager</h1>
        <form method="post" action="">
            <?php wp_nonce_field('srm_nonce'); ?>
            <table class="wp-list-table widefat fixed striped" id="srm_sales_reps_table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>State</th>
                        <th>Country</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($sales_reps)) :
                        foreach ($sales_reps as $index => $rep) :
                    ?>
                        <tr>
                            <td><input type="text" name="srm_sales_reps[<?php echo esc_attr($index); ?>][name]" value="<?php echo esc_attr($rep['name']); ?>" required></td>
                            <td><input type="email" name="srm_sales_reps[<?php echo esc_attr($index); ?>][email]" value="<?php echo esc_attr($rep['email']); ?>" required></td>
                            <td><input type="text" name="srm_sales_reps[<?php echo esc_attr($index); ?>][state]" value="<?php echo esc_attr($rep['state']); ?>" required></td>
                            <td><input type="text" name="srm_sales_reps[<?php echo esc_attr($index); ?>][country]" value="<?php echo esc_attr($rep['country']); ?>" required></td>
                            <td>
                                <?php if ($index === 0): ?>
                                    <em>Cannot remove</em>
                                <?php else: ?>
                                    <button type="button" class="button srm-remove-row">Remove</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <tr>
                            <td><input type="text" name="srm_sales_reps[0][name]" required></td>
                            <td><input type="email" name="srm_sales_reps[0][email]" required></td>
                            <td><input type="text" name="srm_sales_reps[0][state]" required></td>
                            <td><input type="text" name="srm_sales_reps[0][country]" required></td>
                            <td><em>Cannot remove</em></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <p>
                <button type="button" class="button" id="srm_add_row">Add Sales Representative</button>
            </p>

            <p>
                <input type="submit" name="srm_save_sales_reps" class="button button-primary" value="Save Sales Representatives">
                <input type="submit" name="srm_update_users" class="button button-secondary" value="Update Existing Users">
            </p>
        </form>
    </div>

    <script type="text/template" id="srm_template_row">
        <tr>
            <td><input type="text" name="srm_sales_reps[__INDEX__][name]" required></td>
            <td><input type="email" name="srm_sales_reps[__INDEX__][email]" required></td>
            <td><input type="text" name="srm_sales_reps[__INDEX__][state]" required></td>
            <td><input type="text" name="srm_sales_reps[__INDEX__][country]" required></td>
            <td><button type="button" class="button srm-remove-row">Remove</button></td>
        </tr>
    </script>

    <script>
    jQuery(document).ready(function($){
        var rowCount = $('#srm_sales_reps_table tbody tr').length;

        $('#srm_add_row').on('click', function() {
            var template = $('#srm_template_row').html();
            template = template.replace(/__INDEX__/g, rowCount);
            $('#srm_sales_reps_table tbody').append(template);
            rowCount++;
        });

        $('#srm_sales_reps_table').on('click', '.srm-remove-row', function() {
            $(this).closest('tr').remove();
        });
    });
    </script>
<?php
}
