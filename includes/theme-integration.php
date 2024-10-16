<?php
class Cigar_King_Product_Insight_Theme_Integration {
    public function __construct() {
        add_action('wp_head', array($this, 'output_custom_styles'));
        add_action('admin_init', array($this, 'register_style_settings'));
        add_action('switch_theme', array($this, 'clear_theme_color_cache'));
    }

    public function register_style_settings() {
        register_setting('cigar_king_product_insight_options', 'ck_custom_css');
        add_settings_field(
            'ck_custom_css',
            __('Custom CSS', 'cigar-king-product-insight'),
            array($this, 'custom_css_callback'),
            'cigar_king_product_insight',
            'cigar_king_product_insight_section'
        );
    }

    public function custom_css_callback() {
        $custom_css = get_option('ck_custom_css', '');
        echo '<textarea name="ck_custom_css" rows="10" cols="50">' . esc_textarea($custom_css) . '</textarea>';
    }

    public function output_custom_styles() {
        $primary_color = $this->get_theme_primary_color();
        $secondary_color = $this->adjust_brightness($primary_color, 30);
        $custom_css = get_option('ck_custom_css', '');
        
        echo '<style type="text/css">';
        echo ':root {';
        echo '--ck-primary-color: ' . esc_attr($primary_color) . ';';
        echo '--ck-secondary-color: ' . esc_attr($secondary_color) . ';';
        echo '--ck-text-color: ' . esc_attr($this->get_contrasting_color($primary_color)) . ';';
        echo '}';
        echo esc_html($custom_css);
        echo '</style>';
    }

    public function clear_theme_color_cache() {
        delete_transient('ck_theme_primary_color');
    }

    private function get_theme_primary_color() {
        $cached_color = get_transient('ck_theme_primary_color');
        if ($cached_color !== false) {
            return $cached_color;
        }

        $primary_color = '#333333'; // Default fallback color

        // Check if the current theme supports custom colors
        if (current_theme_supports('custom-colors')) {
            $theme_color = get_theme_mod('primary_color');
            if ($theme_color) {
                $primary_color = $theme_color;
            }
        }

        // If no custom color is set, try to extract color from the theme's stylesheet
        if ($primary_color === '#333333') {
            $theme = wp_get_theme();
            $stylesheet_path = $theme->get_stylesheet_directory() . '/style.css';
            
            if (file_exists($stylesheet_path)) {
                $theme_css = file_get_contents($stylesheet_path);
                if ($theme_css !== false) {
                    $color_patterns = array(
                        'primary-color',
                        'primary_color',
                        'main-color',
                        'main_color',
                        'theme-color',
                        'theme_color'
                    );
                    
                    foreach ($color_patterns as $pattern) {
                        if (preg_match('/' . $pattern . ':\s*(#[a-fA-F0-9]{6})/', $theme_css, $matches)) {
                            $primary_color = $matches[1];
                            break;
                        }
                    }
                    
                    // If still not found, look for any color
                    if ($primary_color === '#333333') {
                        preg_match('/#([a-fA-F0-9]{6})/', $theme_css, $matches);
                        if (!empty($matches)) {
                            $primary_color = $matches[0];
                        }
                    }
                }
            }
        }

        set_transient('ck_theme_primary_color', $primary_color, DAY_IN_SECONDS);
        return $primary_color;
    }
    
    private function adjust_brightness($hex, $steps) {
        // Convert hex to rgb
        $rgb = array_map('hexdec', str_split(ltrim($hex, '#'), 2));
        
        // Adjust brightness
        foreach ($rgb as &$color) {
            $color = max(0, min(255, $color + $steps));
        }
        
        // Convert rgb back to hex
        return '#' . implode('', array_map(function($n) {
            return str_pad(dechex($n), 2, '0', STR_PAD_LEFT);
        }, $rgb));
    }

    private function get_contrasting_color($hex) {
        // Convert hex to rgb
        $rgb = array_map('hexdec', str_split(ltrim($hex, '#'), 2));
        
        // Calculate brightness
        $brightness = ($rgb[0] * 299 + $rgb[1] * 587 + $rgb[2] * 114) / 1000;
        
        return $brightness > 128 ? '#000000' : '#FFFFFF';
    }
}

new Cigar_King_Product_Insight_Theme_Integration();