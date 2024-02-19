<?php
/**
 * Plugin Name: CF7 Form Testing
 * Description: An add-on plugin for CF7 to set test email IDs and automatic form testing using CF7 API.
 * Version: 2.2
 * Author: Jainish Brahmbhatt
 * Author URI: https://beardog.digital
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Plugin update checker
require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/jybrahmbhatt1994/CF7-Form-Testing/', // Your GitHub repo URL
    __FILE__, // Full path to the main plugin file
    'cf7-form-testing' // The slug of your plugin
);

$myUpdateChecker->setBranch('main');
$myUpdateChecker->getVcsApi()->enableReleaseAssets();


include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
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
add_action('wpcf7_before_send_mail', 'cf7_addon_modify_email_properties');
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

function get_all_cf7_forms() {
    $args = array(
        'post_type' => 'wpcf7_contact_form',
        'posts_per_page' => -1, // Retrieve all forms
    );

    $cf7_posts = get_posts($args);

    $forms = array();
    foreach ($cf7_posts as $post) {
        $forms[] = array(
            'id' => $post->ID,
            'title' => $post->post_title,
			'content' => $post->post_content,
        );
    }

    return $forms;
}

// Function to get Contact Form 7 form content by form ID
function get_cf7_form_content_by_id() {
	global $wpdb;
	$forms = get_all_cf7_forms();

	$quiz_shortcode = '[quiz quiz-math class:quiz class:form-control id:quiz-math "1+1=?|2" "1+2=?|3" "1+3=?|4" "1+4=?|5" "1+5=?|6" "1+6=?|7" "1+7=?|8" "1+8=?|9" "1+9=?|10" "2+1=?|3" "2+2=?|4" "2+3=?|5" "2+4=?|6" "2+5=?|7" "2+6=?|8" "2+7=?|9" "2+8=?|10" "2+9=?|11" "3+1=?|4" "3+2=?|5" "3+3=?|6" "3+4=?|7" "3+5=?|8" "3+6=?|9" "3+7=?|10" "3+8=?|11" "3+9=?|12" "4+1=?|5" "4+2=?|6" "4+3=?|7" "4+4=?|8" "4+5=?|9" "4+6=?|10" "4+7=?|11" "4+8=?|12" "4+9=?|13" "5+1=?|6" "5+2=?|7" "5+3=?|8" "5+4=?|9" "5+5=?|10" "5+6=?|11" "5+7=?|12" "5+8=?|13" "5+9=?|14" "6+1=?|7" "6+2=?|8" "6+3=?|9" "6+4=?|10" "6+5=?|11" "6+6=?|12" "6+7=?|13" "6+8=?|14" "6+9=?|15" "7+1=?|8" "7+2=?|9" "7+3=?|10" "7+4=?|11" "7+5=?|12" "7+6=?|13" "7+7=?|14" "7+8=?|15" "7+9=?|16" "8+1=?|9" "8+2=?|10" "8+3=?|11" "8+4=?|12" "8+5=?|13" "8+6=?|14" "8+7=?|15" "8+8=?|16" "8+9=?|17" "9+1=?|10" "9+2=?|11" "9+3=?|12" "9+4=?|13" "9+5=?|14" "9+6=?|15" "9+7=?|16" "9+8=?|17" "9+9=?|18"]';

	$quiz_reverse = 'forms-are-being-tested';

	if (get_option('cf7_form_testing_mode') && get_option('cf7_form_testing_emails') != '') {
		foreach ($forms as $form) {
			$query = $wpdb->prepare("SELECT post_content FROM {$wpdb->prefix}posts WHERE post_type = 'wpcf7_contact_form' AND ID = ".$form['id']);

			// Execute the query
			$form_content = $wpdb->get_var($query);

			$delimiterPattern = "/1\r\nBEARDOG DIGITAL Lead:|1\nBEARDOG DIGITAL Lead:|1\rBEARDOG DIGITAL Lead:/";

			// Split the content using preg_split and the regular expression
			$parts = preg_split($delimiterPattern, $form_content, 2);

			// The content before the delimiter will be in the first part of the array
			$desiredContent = $parts[0];
			// var_dump($desiredContent); exit;

			$updated_content = str_replace($quiz_shortcode, 'forms-are-being-tested', $desiredContent);

			$wpdb->update(
				"{$wpdb->prefix}posts",
				['post_content' => $updated_content], // Data array
				['ID' => $form['id']] // Where array
			);

			update_post_meta($form['id'], '_form', $updated_content);

		}
	}else{
		foreach ($forms as $form) {
			$query = $wpdb->prepare("SELECT post_content FROM {$wpdb->prefix}posts WHERE post_type = 'wpcf7_contact_form' AND ID = ".$form['id']);

			// Execute the query
			$form_content = $wpdb->get_var($query);

			$delimiterPattern = "/1\r\nBEARDOG DIGITAL Lead:|1\nBEARDOG DIGITAL Lead:|1\rBEARDOG DIGITAL Lead:/";

			// Split the content using preg_split and the regular expression
			$parts = preg_split($delimiterPattern, $form_content, 2);

			// The content before the delimiter will be in the first part of the array
			$desiredContent = $parts[0];
			// var_dump($updated_content); exit;

			$updated_content = str_replace($quiz_reverse, $quiz_shortcode, $desiredContent);

			$wpdb->update(
				"{$wpdb->prefix}posts",
				['post_content' => $updated_content], // Data array
				['ID' => $form['id']] // Where array
			);

			update_post_meta($form['id'], '_form', $updated_content);

		}
	}
}

function automate_cf7_form_submission() {
	
	$forms = get_all_cf7_forms();
	
	foreach ($forms as $form) {

		$form_id = $form['id'];
    	$form1 = WPCF7_ContactForm::get_instance($form_id);
	
		$url = get_bloginfo('wpurl').'/wp-json/contact-form-7/v1/contact-forms/'.$form_id.'/feedback';
		$boundary = wp_generate_uuid4();

		if ($form1) {
			$form_properties = $form1->get_properties();
        	$form_content = $form_properties['form'];

			preg_match_all('/\[([a-zA-Z]+)(.*?)\]/', $form_content, $matches);
        	$fields = $matches[0];
			$body = '';
			foreach ($fields as $field) {
				if (strpos($field, 'submit') !== false || strpos($field, 'response') !== false || strpos($field, 'quiz') !== false) {
					continue;
				}
				$parts = explode(' ', trim($field, "[]"));
				$tagName = rtrim($parts[0], "*");
				if (count($parts) >= 2) {
					$desiredString = $parts[1];
					// echo "Tag: " . $tagName . ", String: " . $desiredString . "<br>";

					if($tagName == "text"){
						$value = 'BD text field'.$form_id;
					}
					if($tagName == "email"){
						$value = 'email@example.com';
					}
					if($tagName == "tel"){
						$value = '1234567890';
					}
					if($tagName == "textarea"){
						$value = 'This is a test message from BearDog Digital.';
					}
					if($tagName == "url"){
						$value = 'http://example.com';
					}
					if($tagName == "number"){
						$value = '12345';
					}
					if($tagName == "date"){
						$value = date('Y-m-d');
					}
					if($tagName == "select"){
						$value = 'Dropdown test value';
					}
					if($tagName == "checkbox"){
						$value = 'Checkbox test value';
					}
					if($tagName == "radio"){
						$value = 'Radio test value';
					}

					$body .= '--' . $boundary . "\r\n";
					$body .= 'Content-Disposition: form-data; name="'.$desiredString.'"' . "\r\n\r\n";
					$body .= $value . "\r\n";

				} else {
					echo "Not enough parts in the field string: " . $field . "<br>";
				}
			}
			$body .= '--' . $boundary . "\r\n";
			$body .= 'Content-Disposition: form-data; name="_wpcf7_unit_tag"' . "\r\n\r\n";
			$body .= 'wpcf7-f'.$form_id.'-o1' . "\r\n";
			$body .= '--' . $boundary . '--';
		}

		$nonce = wp_create_nonce('wp_rest');
		$cookies = [];
		foreach( $_COOKIE as $name => $value ) {
			$cookies[] = new WP_Http_Cookie(
				[ 'name' => $name, 'value' => $value ]
			);
		}

		// Prepare the arguments for wp_remote_post.
		$args = [
			'body' => $body,
			'timeout' => '45',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => [
				'X-WP-Nonce' => $nonce,
				'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
			],
			'cookies' => $cookies,
		];
		
		$response = wp_remote_post($url, $args);

		if (is_wp_error($response)) {
			$error_message = $response->get_error_message();
			echo "Something went wrong: $error_message";
		} else {
			// echo 'Response:<pre>';
			// print_r($response);
			// echo '</pre>';
			// Assuming $response['body'] contains your JSON string
			$json_string = $response['body'];

			// Decode the JSON string into an associative array
			$data = json_decode($json_string, true);

			// Now you can access each element using its key
			$contact_form_id = $data['contact_form_id'];
			$status = $data['status'];
			$message = $data['message'];
			$posted_data_hash = $data['posted_data_hash'];
			$into = $data['into'];
			$invalid_fields = $data['invalid_fields']; // This is an array

			// Example of how to use the data
			echo "<div style='margin-top:20px; margin-bottom:20px;'>";
			echo "Contact Form ID: " . $contact_form_id . "<br>";
			echo "Status: " . $status . "<br>";
			echo "Message: " . $message . "<br>";
			// echo "Posted Data Hash: " . $posted_data_hash . "<br>";
			// echo "Into: " . $into . "<br>";

			// If you need to work with 'invalid_fields' and it's an array
			if (!empty($invalid_fields)) {
				foreach ($invalid_fields as $field) {
					// Process each invalid field. Example:
					echo "Invalid Field: " . $field . "<br>";
				}
			} else {
				echo "<strong>No invalid fields.</strong>";
			}
			echo "</div>";

		}
	}
}




