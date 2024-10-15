<?php
// File: includes/product-insight-renderer.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Cigar_King_Product_Insight_Renderer {
    public static function render() {
        ob_start();
        ?>
        <div id="cigar-ai-chatbox">
            <div id="cigar-ai-input">
                <input type="text" id="cigar-ai-user-input" placeholder="I am Edward, your Cigar AI. Ask me anything...">
                <div id="cigar-ai-loading" style="display: none;">Initializing...</div>
            </div>
            <div id="cigar-ai-last-reply-container" style="display: none;">
            </div>
            <div id="cigar-ai-messages" style="display: none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}