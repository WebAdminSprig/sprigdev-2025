<?php
/*
Plugin Name: Sales Representative Management
Description: Manage and assign Sales Representatives based on state and country.
Version: 1.0
Author: Sunaina Udo
*/

function get_sales_representative_by_state_and_country($state, $country) {
    $sales_reps = get_option('sales_representatives', array());
    foreach ($sales_reps as $rep) {
        if ($rep['state'] === $state && $rep['country'] === $country) {
            error_log("Matched Sales Rep: " . $rep['name'] . " for State: " . $state . " and Country: " . $country);
            return $rep['name'];
        }
    }
    error_log("No Sales Rep matched for State: " . $state . " and Country: " . $country);
    return null;
}

function update_sales_representative_field($user_id) {
    $billing_state = get_user_meta($user_id, 'billing_state', true);
    $billing_country = get_user_meta($user_id, 'billing_country', true);

    if ($billing_state && $billing_country) {
        $sales_rep_name = get_sales_representative_by_state_and_country($billing_state, $billing_country);

        if ($sales_rep_name) {
            error_log("Updating Sales Rep for User ID: $user_id - Sales Rep: $sales_rep_name");

            // Update the custom field for Sales Representative
            update_user_meta($user_id, 'sales_representative', $sales_rep_name);

            // Update the ACF field for Sales Representative if it exists
            if (function_exists('update_field')) {
                update_field('sales_representative', $sales_rep_name, 'user_' . $user_id);
                error_log("ACF Field updated for User ID: $user_id - Sales Rep: $sales_rep_name");
            } else {
                error_log("ACF function update_field does not exist.");
            }
        } else {
            // If no Sales Rep is found, clear the field
            delete_user_meta($user_id, 'sales_representative');
            error_log("Sales Rep cleared for User ID: $user_id");
        }
    } else {
        error_log("Billing state or country missing for User ID: $user_id");
    }
}

// Hook the function to user profile updates and registration
add_action('profile_update', 'update_sales_representative_field');
add_action('user_register', 'update_sales_representative_field');

// Function to display Sales Representative field on user profile page
function show_sales_representative_field($user) {
    // Get the Sales Representative field value
    $sales_rep_name = get_user_meta($user->ID, 'sales_representative', true);
    ?>
    <h3><?php _e("Sales Representative", "textdomain"); ?></h3>

    <table class="form-table">
        <tr>
            <th><label for="sales_representative"><?php _e("Sales Representative", "textdomain"); ?></label></th>
            <td>
                <input type="text" name="sales_representative" id="sales_representative" value="<?php echo esc_attr($sales_rep_name); ?>" class="regular-text" readonly />
                <span class="description"><?php _e("This is the Sales Representative assigned to the user based on their state and country."); ?></span>
            </td>
        </tr>
    </table>
    <?php
}

// Hook into user profile display
add_action('show_user_profile', 'show_sales_representative_field');
add_action('edit_user_profile', 'show_sales_representative_field');

// Function to create the admin menu for Sales Representatives settings
add_action('admin_menu', 'sales_rep_admin_menu');

function sales_rep_admin_menu() {
    add_menu_page('Sales Representatives', 'Sales Representatives', 'manage_options', 'sales-representatives', 'sales_representatives_settings_page');
}

// Function to handle Sales Representatives settings page
function sales_representatives_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['save_sales_reps'])) {
        check_admin_referer('save_sales_reps_nonce');
        $sales_reps = array();

        foreach ($_POST['sales_rep_name'] as $key => $name) {
            if (!empty($name) && !empty($_POST['sales_rep_email'][$key]) && !empty($_POST['sales_rep_state'][$key]) && !empty($_POST['sales_rep_country'][$key])) {
                $sales_reps[] = array(
                    'name' => sanitize_text_field($name),
                    'email' => sanitize_email($_POST['sales_rep_email'][$key]),
                    'state' => sanitize_text_field($_POST['sales_rep_state'][$key]),
                    'country' => sanitize_text_field($_POST['sales_rep_country'][$key]),
                );
            }
        }

        update_option('sales_representatives', $sales_reps);
        echo '<div class="updated"><p>Sales Representatives saved successfully!</p></div>';
    }

    $sales_reps = get_option('sales_representatives', array());

    ?>
    <div class="wrap">
        <h1>Sales Representatives</h1>
        <form method="post" action="">
            <?php wp_nonce_field('save_sales_reps_nonce'); ?>
            <table class="form-table" id="sales-reps-table">
                <thead>
                    <tr>
                        <th>Sales Representative</th>
                        <th>Email</th>
                        <th>State</th>
                        <th>Country</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($sales_reps)): ?>
                        <?php foreach ($sales_reps as $key => $rep): ?>
                            <tr>
                                <td><input type="text" name="sales_rep_name[]" value="<?php echo esc_attr($rep['name']); ?>" /></td>
                                <td><input type="email" name="sales_rep_email[]" value="<?php echo esc_attr($rep['email']); ?>" /></td>
                                <td><input type="text" name="sales_rep_state[]" value="<?php echo esc_attr($rep['state']); ?>" /></td>
                                <td><input type="text" name="sales_rep_country[]" value="<?php echo esc_attr($rep['country']); ?>" /></td>
                                <td><button type="button" class="button remove-row">Remove</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td><input type="text" name="sales_rep_name[]" /></td>
                            <td><input type="email" name="sales_rep_email[]" /></td>
                            <td><input type="text" name="sales_rep_state[]" /></td>
                            <td><input type="text" name="sales_rep_country[]" /></td>
                            <td><button type="button" class="button remove-row">Remove</button></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="empty-row screen-reader-text">
                        <td><input type="text" name="sales_rep_name[]" /></td>
                        <td><input type="email" name="sales_rep_email[]" /></td>
                        <td><input type="text" name="sales_rep_state[]" /></td>
                        <td><input type="text" name="sales_rep_country[]" /></td>
                        <td><button type="button" class="button remove-row">Remove</button></td>
                    </tr>
                </tbody>
            </table>
            <p><button type="button" class="button add-row">Add Sales Representative</button></p>
            <p class="submit">
                <input type="submit" name="save_sales_reps" class="button-primary" value="Save Changes" />
            </p>
        </form>
    </div>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.add-row').on('click', function() {
                var row = $('.empty-row.screen-reader-text').clone(true);
                row.removeClass('empty-row screen-reader-text');
                row.insertBefore('#sales-reps-table tbody>tr:last');
                return false;
            });

            $('.remove-row').on('click', function() {
                $(this).parents('tr').remove();
                return false;
            });
        });
    </script>
    <?php
}
