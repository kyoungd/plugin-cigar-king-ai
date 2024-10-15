<?php
// File: includes/product-insight-settings.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Cigar_King_Product_Insight_Settings {
    private $options;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
    }

    public function add_admin_menu() {
        add_options_page(
            'Cigar King Product Insight Settings',
            'Cigar King AI',
            'manage_options',
            'cigar_king_product_insight',
            array($this, 'render_settings_page')
        );
    }

    public function init_settings() {
        register_setting('cigar_king_product_insight_settings', 'cigar_king_product_insight_options');

        add_settings_section(
            'cigar_king_product_insight_general_section',
            'General Settings',
            array($this, 'render_general_section'),
            'cigar_king_product_insight_settings'
        );

        add_settings_field(
            'api_url',
            'API URL',
            array($this, 'render_api_url_field'),
            'cigar_king_product_insight_settings',
            'cigar_king_product_insight_general_section'
        );

        add_settings_field(
            'api_key',
            'API Key',
            array($this, 'render_api_key_field'),
            'cigar_king_product_insight_settings',
            'cigar_king_product_insight_general_section'
        );

        // Add new field for chatbox placement
        add_settings_field(
            'chatbox_placement',
            'Chatbox Placement',
            array($this, 'render_chatbox_placement_field'),
            'cigar_king_product_insight_settings',
            'cigar_king_product_insight_general_section'
        );
    }

    public function render_settings_page() {
        $this->options = get_option('cigar_king_product_insight_options');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
            <?php
                settings_fields('cigar_king_product_insight_settings');
                do_settings_sections('cigar_king_product_insight_settings');
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    public function render_general_section() {
        echo '<p>Configure the settings for the Cigar King Product Insight plugin.</p>';
    }

    public function render_api_url_field() {
        $value = isset($this->options['api_url']) ? $this->options['api_url'] : '';
        echo '<input type="text" id="api_url" name="cigar_king_product_insight_options[api_url]" value="' . esc_attr($value) . '" class="regular-text">';
    }

    public function render_api_key_field() {
        $value = isset($this->options['api_key']) ? $this->options['api_key'] : '';
        echo '<input type="password" id="api_key" name="cigar_king_product_insight_options[api_key]" value="' . esc_attr($value) . '" class="regular-text">';
    }

    public function render_chatbox_placement_field() {
        $value = isset($this->options['chatbox_placement']) ? $this->options['chatbox_placement'] : 'after_add_to_cart';
        $options = array(
            'after_add_to_cart' => 'After Add to Cart Button',
            'before_add_to_cart' => 'Before Add to Cart Button',
            'after_product_summary' => 'After Product Summary',
            'after_product_meta' => 'After Product Meta',
            'after_single_product' => 'After Single Product',
            'in_product_tabs' => 'In Product Tabs'
        );
        echo '<select id="chatbox_placement" name="cigar_king_product_insight_options[chatbox_placement]">';
        foreach ($options as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }
}