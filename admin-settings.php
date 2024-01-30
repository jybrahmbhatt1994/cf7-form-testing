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
                        <input type="checkbox" name="cf7_form_testing_mode" <?php echo get_option('cf7_form_testing_mode') ? 'checked' : ''; ?> /><br/><br/>
                        <input type="text" name="cf7_form_testing_emails" value="<?php echo esc_attr(get_option('cf7_form_testing_emails')); ?>" style="width:60%;" />
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

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
