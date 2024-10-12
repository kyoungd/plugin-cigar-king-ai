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
require_once plugin_dir_path(__FILE__) . 'includes/class-cigar-king-ai-chatbox-renderer.php';

class Cigar_King_AI_Recommender {

    public function __construct() {
        $this->settings = new Cigar_King_AI_Settings();
        add_action('init', array($this, 'init'));

        // AJAX action hooks
        add_action('wp_ajax_send_ai_message', array($this, 'send_ai_message'));
        add_action('wp_ajax_nopriv_send_ai_message', array($this, 'send_ai_message'));
        add_action('wp_ajax_cigar_ai_initial_call', array($this, 'handle_initial_call'));
        add_action('wp_ajax_nopriv_cigar_ai_initial_call', array($this, 'handle_initial_call'));
    }

    public function init() {
        $options = get_option('cigar_king_ai_options');
        $this->api_url = isset($options['api_url']) ? $options['api_url'] : '';
        $this->api_key = isset($options['api_key']) ? $options['api_key'] : '';

        $this->register_shortcodes();

        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('cigar-ai-style', plugin_dir_url(__FILE__) . 'css/cigar-ai-style.css', array(), '1.0');
        wp_enqueue_script('cigar-ai-script', plugin_dir_url(__FILE__) . 'js/cigar-ai-script.js', array('jquery'), '1.0', true);
        wp_localize_script('cigar-ai-script', 'cigar_ai_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cigar_ai_nonce'),
            'api_key' => $this->api_key
        ));
    }

    public function register_shortcodes() {
        add_shortcode('cigar_ai_chatbox', array($this, 'render_chatbox'));
    }

    public function render_chatbox() {
        return Cigar_King_AI_Chatbox_Renderer::render();
    }

    public function handle_initial_call() {
        check_ajax_referer('cigar_ai_nonce', 'nonce');

        $initial_data = array(
            'subscription_external_id' => sanitize_text_field($_POST['subscription_external_id']),
            'timeZone' => sanitize_text_field($_POST['timeZone']),
            'caller' => new stdClass(),  // Empty object instead of an empty array
            'caller_domain' => sanitize_text_field($_POST['caller_domain'])
        );

        $response = $this->call_ai_api_initial($initial_data);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        $ai_response = json_decode($response['body'], true);

        if (isset($ai_response['error'])) {
            wp_send_json_error($ai_response['error']);
        }

        wp_send_json_success($ai_response);
    }

    public function send_ai_message() {
        check_ajax_referer('cigar_ai_nonce', 'nonce');

        $user_message = sanitize_text_field($_POST['message']);
        $initial_data = isset($_POST['data']) ? $_POST['data'] : array();

        $response = $this->call_ai_api($user_message, $initial_data);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        $ai_response = json_decode($response['body'], true);

        if (isset($ai_response['error'])) {
            wp_send_json_error($ai_response['error']);
        }

        wp_send_json_success($ai_response);
    }

    private function call_ai_api_initial($initial_data) {
        if (empty($this->api_url) || empty($this->api_key)) {
            return new WP_Error('api_error', 'API URL or Key is not set. Please configure the plugin settings.');
        }

        $body = json_encode($initial_data);

        return wp_remote_post($this->api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'body' => $body,
            'timeout' => 15
        ));
    }

    private function call_ai_api($message, $initial_data) {
        if (empty($this->api_url) || empty($this->api_key)) {
            return new WP_Error('api_error', 'API URL or Key is not set. Please configure the plugin settings.');
        }

        $body = json_encode(array(
            'data' => $initial_data,
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
