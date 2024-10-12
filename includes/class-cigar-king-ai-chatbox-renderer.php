<?php
// File: includes/class-cigar-king-ai-chatbox-renderer.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Cigar_King_AI_Chatbox_Renderer {
    public static function render() {
        ob_start();
        ?>
        <div id="cigar-ai-chatbox">
            <div id="cigar-ai-messages"></div>
            <div id="cigar-ai-input">
                <input type="text" id="cigar-ai-user-input" placeholder="Ask about cigars...">
                <div id="cigar-ai-loading" style="display: none;">Initializing...</div>
            </div>
        </div>
        <div id="cigar-ai-recommendations"></div>
        <?php
        return ob_get_clean();
    }
}
