<?php
// File: includes/class-cigar-king-ai-settings.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Cigar_King_AI_Settings {
    private $options;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
    }

    public function add_admin_menu() {
        add_options_page(
            'Cigar King AI Recommender Settings',
            'Cigar King AI',
            'manage_options',
            'cigar_king_ai_recommender',
            array($this, 'render_settings_page')
        );
    }

    public function init_settings() {
        register_setting('cigar_king_ai_settings', 'cigar_king_ai_options');

        add_settings_section(
            'cigar_king_ai_general_section',
            'General Settings',
            array($this, 'render_general_section'),
            'cigar_king_ai_settings'
        );

        add_settings_field(
            'api_url',
            'API URL',
            array($this, 'render_api_url_field'),
            'cigar_king_ai_settings',
            'cigar_king_ai_general_section'
        );

        add_settings_field(
            'api_key',
            'API Key',
            array($this, 'render_api_key_field'),
            'cigar_king_ai_settings',
            'cigar_king_ai_general_section'
        );
    }

    public function render_settings_page() {
        $this->options = get_option('cigar_king_ai_options');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
            <?php
                settings_fields('cigar_king_ai_settings');
                do_settings_sections('cigar_king_ai_settings');
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    public function render_general_section() {
        echo '<p>Configure the settings for the Cigar King AI Recommender plugin.</p>';
    }

    public function render_api_url_field() {
        $value = isset($this->options['api_url']) ? $this->options['api_url'] : '';
        echo '<input type="text" id="api_url" name="cigar_king_ai_options[api_url]" value="' . esc_attr($value) . '" class="regular-text">';
    }

    public function render_api_key_field() {
        $value = isset($this->options['api_key']) ? $this->options['api_key'] : '';
        echo '<input type="password" id="api_key" name="cigar_king_ai_options[api_key]" value="' . esc_attr($value) . '" class="regular-text">';
    }
}
