<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Admin menu
function cf7_addon_plugin_menu() {
    add_menu_page('CF7 Form Testing Settings', 'CF7 Form Test', 'manage_options', 'cf7-form-testing', 'cf7_addon_plugin_settings_page');
}

// Settings page
function cf7_addon_plugin_settings_page() {
    ?>
    <div class="wrap">
        <h2>CF7 Form Testing Settings</h2>
        
        <form method="post" action="options.php">
            <?php
            settings_fields('cf7-form-testing-settings-group');
            do_settings_sections('cf7-form-testing-settings-group');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Set Email IDs for Testing Forms:</th>
                    <td>
                        <input id="cf7_form_testing_mode" type="checkbox" name="cf7_form_testing_mode" <?php echo get_option('cf7_form_testing_mode') ? 'checked' : ''; ?> /><br/><br/>
                        <input id="cf7_form_testing_emails" type="text" name="cf7_form_testing_emails" value="<?php echo esc_attr(get_option('cf7_form_testing_emails')); ?>" style="width:60%;"/>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
		
		
        <?php if (get_option('cf7_form_testing_mode') && get_option('cf7_form_testing_emails') != '') { ?>
        <h2>WP Form Tester</h2>
        <form action="" method="post" class="test-forms">
            <?php submit_button('Test Forms'); ?>
        </form>
        <?php } ?>
		
		<?php
		// Check if the button has been clicked
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			automate_cf7_form_submission();
		} ?>
		
    </div>
    <?php
}

function my_custom_option_update_hook( $option_name, $old_value, $new_value ) {
    // Check if the updated option is the one you care about
    if ( 'cf7_form_testing_mode' === $option_name ) {
        get_cf7_form_content_by_id();
    }
}
add_action( 'updated_option', 'my_custom_option_update_hook', 10, 3 );


// Register settings
function cf7_addon_plugin_register_settings() {
    register_setting('cf7-form-testing-settings-group', 'cf7_form_testing_mode', 'cf7_addon_sanitize_checkbox');
    register_setting('cf7-form-testing-settings-group', 'cf7_form_testing_emails', 'cf7_addon_sanitize_emails');
}

// Sanitization callbacks
function cf7_addon_sanitize_checkbox($input) {
    return $input ? '1' : '';
}

function cf7_addon_sanitize_emails($input) {
    $emails = explode(',', $input);
    $sanitized_emails = array();
    foreach ($emails as $email) {
        if (is_email(trim($email))) {
            $sanitized_emails[] = sanitize_email(trim($email));
        }
    }
    return implode(', ', $sanitized_emails);
}

add_action('admin_menu', 'cf7_addon_plugin_menu');
add_action('admin_init', 'cf7_addon_plugin_register_settings');

// Hook into the admin_notices action to display notices
add_action('admin_notices', 'my_custom_admin_notices');

// Function to display admin notices
function my_custom_admin_notices()
{
    if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
        // Display a success notice
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
    } elseif (isset($_GET['settings-error']) && $_GET['settings-error']) {
        // Display an error notice
        echo '<div class="notice notice-error is-dismissible"><p>Error saving settings!</p></div>';
    }
}

// Enqueue scripts and styles for the admin page
function enqueue_admin_scripts() {
    // Check if it's the admin page
    if (is_admin()) {
        // Enqueue jQuery
        wp_enqueue_script('jquery');
        // Enqueue custom script
        wp_enqueue_script('custom-admin-script', 'http://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js', array('jquery'), '1.0', true);
        wp_enqueue_script('custom-admin-script', plugin_dir_url(__FILE__) . 'js/custom-script.js', array('jquery'), '1.0', true);
    }
}

// Hook the function to the admin_enqueue_scripts action
add_action('admin_enqueue_scripts', 'enqueue_admin_scripts');