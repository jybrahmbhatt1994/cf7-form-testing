<?php
/**
 * Plugin Name: CF7 Form Testing
 * Description: An add-on plugin for Contact Form 7 to set test email IDs.
 * Version: 2.3
 * Author: Jainish Brahmbhatt
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Plugin update checker
require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/jybrahmbhatt1994/cf7-form-testing/', // Your GitHub repo URL
    __FILE__, // Full path to the main plugin file
    'cf7-form-testing' // The slug of your plugin
);

$myUpdateChecker->setBranch('main');
$myUpdateChecker->getVcsApi()->enableReleaseAssets();

function cf7_form_testing_init() {
    if (!is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
        add_action('admin_notices', 'cf7_form_testing_admin_notice');
        return;
    }

    require_once plugin_dir_path(__FILE__) . 'admin-settings.php';
}

function cf7_form_testing_admin_notice() {
    echo '<div class="notice notice-warning"><p>Contact Form 7 is not active. CF7 Form Testing plugin requires Contact Form 7 to be installed and activated.</p></div>';
}

add_action('plugins_loaded', 'cf7_form_testing_init');

// By pass the email ids and additional headers ids
function cf7_addon_modify_email_properties($contact_form) {
    $submission = WPCF7_Submission::get_instance();
    if (!$submission) {
        return;
    }

    $mail = $contact_form->prop('mail'); // Get the main mail property

    // Check if our test mode is active and the test email field is not empty
    if (get_option('cf7_form_testing_mode') && get_option('cf7_form_testing_emails') != '') {
        $test_emails = explode(',', get_option('cf7_form_testing_emails'));
        $valid_emails = array_filter(array_map('trim', array_map('sanitize_email', $test_emails)), 'is_email');

        if (!empty($valid_emails)) {
            $test_email_string = implode(',', $valid_emails);

            // Override the recipient, CC, and BCC if valid test emails are available
            $mail['recipient'] = $test_email_string;
            $mail['additional_headers'] = "Cc: " . $test_email_string . "\r\nBcc: " . $test_email_string;

            $contact_form->set_properties(array('mail' => $mail));
        }
    }
}

add_action('wpcf7_before_send_mail', 'cf7_addon_modify_email_properties');

//cf7 registered client ids registered api = START
add_action('rest_api_init', function () {
  register_rest_route('clientids/v1', '/is_client/', array(
    'methods' => 'GET',
    'callback' => 'get_cf7_clients_mode',
  ));
});
function get_cf7_clients_mode() {
  $value = get_option('cf7_form_testing_mode'); // Retrieve the option value
  $booleanValue = boolval($value); // Cast the value to boolean
  return new WP_REST_Response($booleanValue, 200); // Return the boolean value
}
//cf7 registered client ids registered api = STOP