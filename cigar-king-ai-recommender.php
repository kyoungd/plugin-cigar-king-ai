<?php
/**
 * Plugin Name: Cigar King AI Recommender
 * Plugin URI: https://example.com/cigar-king-ai-recommender
 * Description: AI-powered cigar recommendation system for WooCommerce
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: cigar-king-ai-recommender
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 3.0
 * WC tested up to: 6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

// Include the settings class
require_once plugin_dir_path(__FILE__) . 'includes/class-cigar-king-ai-settings.php';

class Cigar_King_AI_Recommender {
    private $api_url;
    private $api_key;
    private $settings;

    public function __construct() {
        // Initialize settings immediately
        $this->settings = new Cigar_King_AI_Settings();

        // Hook init() to the 'init' action
        add_action('init', array($this, 'init'));
    }

    public function init() {
        error_log('Cigar_King_AI_Recommender init method called');

        $options = get_option('cigar_king_ai_options');
        $this->api_url = isset($options['api_url']) ? $options['api_url'] : '';
        $this->api_key = isset($options['api_key']) ? $options['api_key'] : '';

        // Directly register the shortcode
        $this->register_shortcodes();

        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_send_ai_message', array($this, 'send_ai_message'));
        add_action('wp_ajax_nopriv_send_ai_message', array($this, 'send_ai_message'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('cigar-ai-style', plugin_dir_url(__FILE__) . 'css/cigar-ai-style.css', array(), '1.0');
        wp_enqueue_script('cigar-ai-script', plugin_dir_url(__FILE__) . 'js/cigar-ai-script.js', array('jquery'), '1.0', true);
        wp_localize_script('cigar-ai-script', 'cigar_ai_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cigar_ai_nonce')
        ));
    }

    public function register_shortcodes() {
        error_log('Cigar_King_AI_Recommender register_shortcodes method called');
        add_shortcode('cigar_ai_chatbox', array($this, 'render_chatbox'));
    }

    public function render_chatbox() {
        ob_start();
        ?>
        <div id="cigar-ai-chatbox">
            <div id="cigar-ai-messages"></div>
            <div id="cigar-ai-input">
                <input type="text" id="cigar-ai-user-input" placeholder="Ask about cigars...">
                <button id="cigar-ai-send">Send</button>
            </div>
        </div>
        <div id="cigar-ai-recommendations"></div>
        <?php
        return ob_get_clean();
    }

    public function send_ai_message() {
        check_ajax_referer('cigar_ai_nonce', 'nonce');

        $user_message = sanitize_text_field($_POST['message']);

        $response = $this->call_ai_api($user_message);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        $ai_response = json_decode($response['body'], true);

        if (isset($ai_response['data']['is_new_recommendation']) && $ai_response['data']['is_new_recommendation']) {
            $recommendations = $this->get_product_recommendations($ai_response['data']['recommendations']);
            wp_send_json_success(array(
                'message' => $ai_response['message'],
                'recommendations' => $recommendations
            ));
        } else {
            wp_send_json_success(array('message' => $ai_response['message']));
        }
    }

    private function call_ai_api($message) {
        if (empty($this->api_url) || empty($this->api_key)) {
            return new WP_Error('api_error', 'API URL or Key is not set. Please configure the plugin settings.');
        }

        $body = json_encode(array(
            'subscription_external_id' => 'cigar-king',
            'timeZone' => 'America/Los_Angeles',
            'caller' => array(),
            'caller_domain' => '',
            'message' => $message
        ));

        return wp_remote_post($this->api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'body' => $body,
            'timeout' => 15
        ));
    }

    private function get_product_recommendations($product_ids) {
        $recommendations = array();

        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);

            if (!$product) {
                continue;
            }

            $image_id = $product->get_image_id();
            $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');

            $recommendations[] = array(
                'id' => $product_id,
                'name' => $product->get_name(),
                'image' => $image_url,
                'description' => $product->get_short_description(),
                'url' => get_permalink($product_id)
            );
        }

        return array_slice($recommendations, 0, 8);
    }
}

// Initialize the plugin
function cigar_king_ai_recommender_init() {
    new Cigar_King_AI_Recommender();
}
add_action('plugins_loaded', 'cigar_king_ai_recommender_init');

// Add a "Settings" link to the plugin action links
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'cigar_king_ai_plugin_action_links');

function cigar_king_ai_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=cigar_king_ai_recommender') . '">' . __('Settings', 'cigar-king-ai-recommender') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
