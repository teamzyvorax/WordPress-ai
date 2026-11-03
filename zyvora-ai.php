<?php
/**
 * Plugin Name: ZYVORA AI
 * Description: An extensible AI assistant for WordPress with a secure admin chat and configurable OpenAI-compatible provider.
 * Version: 1.0.0
 * Author: ZYVORA
 * License: GPL-2.0-or-later
 * Text Domain: zyvora-ai
 */
if (!defined('ABSPATH')) { exit; }

define('ZYVORA_AI_VERSION', '1.0.0');
define('ZYVORA_AI_FILE', __FILE__);
define('ZYVORA_AI_URL', plugin_dir_url(__FILE__));

final class Zyvora_AI {
    const OPTION = 'zyvora_ai_settings';

    public function __construct() {
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'assets'));
        add_action('wp_ajax_zyvora_ai_chat', array($this, 'chat'));
    }

    public function menu() {
        add_menu_page(
            __('ZYVORA AI', 'zyvora-ai'),
            __('ZYVORA AI', 'zyvora-ai'),
            'manage_options',
            'zyvora-ai',
            array($this, 'render_page'),
            'dashicons-superhero',
            58
        );
    }

    public function register_settings() {
        register_setting('zyvora_ai_group', self::OPTION, array($this, 'sanitize_settings'));
    }

    public function sanitize_settings($input) {
        $old = get_option(self::OPTION, array());
        $input = is_array($input) ? $input : array();
        $key = isset($input['api_key']) ? trim((string) $input['api_key']) : '';
        return array(
            'endpoint' => isset($input['endpoint']) ? esc_url_raw(trim($input['endpoint'])) : 'https://api.openai.com/v1/chat/completions',
            'model' => isset($input['model']) ? sanitize_text_field($input['model']) : 'gpt-4o-mini',
            // An empty field keeps the previously saved secret.
            'api_key' => $key !== '' ? sanitize_text_field($key) : (isset($old['api_key']) ? $old['api_key'] : ''),
        );
    }

    public function assets($hook) {
        if ($hook !== 'toplevel_page_zyvora-ai') { return; }
        wp_enqueue_style('zyvora-ai-admin', ZYVORA_AI_URL . 'assets/admin.css', array(), ZYVORA_AI_VERSION);
        wp_enqueue_script('zyvora-ai-admin', ZYVORA_AI_URL . 'assets/admin.js', array(), ZYVORA_AI_VERSION, true);
        wp_localize_script('zyvora-ai-admin', 'ZyvoraAI', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('zyvora_ai_chat'),
        ));
    }

    public function render_page() {
        if (!current_user_can('manage_options')) { return; }
        $settings = get_option(self::OPTION, array());
        $endpoint = isset($settings['endpoint']) ? $settings['endpoint'] : 'https://api.openai.com/v1/chat/completions';
        $model = isset($settings['model']) ? $settings['model'] : 'gpt-4o-mini';
        ?>
        <div class="wrap zyvora-wrap">
            <header class="zyvora-header">
                <div><span class="zyvora-eyebrow">WORDPRESS AI WORKSPACE</span><h1>ZYVORA <span>AI</span></h1><p>Your AI control center for WordPress.</p></div>
                <span class="zyvora-status"><i></i> ADMIN WORKSPACE</span>
            </header>
            <div class="zyvora-grid">
                <section class="zyvora-panel">
                    <h2>AI Assistant</h2>
                    <p class="zyvora-muted">Ask a question or generate content. Your API key stays on the server.</p>
                    <div id="zyvora-chat-log" class="zyvora-chat-log" aria-live="polite">
                        <div class="zyvora-message zyvora-assistant"><strong>ZYVORA AI</strong><p>Hello! Configure your provider, then ask me to draft, brainstorm, summarize, or explain.</p></div>
                    </div>
                    <form id="zyvora-chat-form">
                        <label class="screen-reader-text" for="zyvora-prompt">Your message</label>
                        <textarea id="zyvora-prompt" rows="3" placeholder="What would you like to create today?" required></textarea>
                        <div class="zyvora-chat-actions"><span class="zyvora-muted">AI responses may need review before publishing.</span><button class="button button-primary" type="submit">Send message ↗</button></div>
                    </form>
                </section>
                <aside class="zyvora-panel zyvora-settings">
                    <h2>Provider settings</h2>
                    <p class="zyvora-muted">Connect an OpenAI-compatible Chat Completions endpoint.</p>
                    <form method="post" action="options.php">
                        <?php settings_fields('zyvora_ai_group'); ?>
                        <label for="zyvora-endpoint">API endpoint</label>
                        <input id="zyvora-endpoint" class="regular-text" type="url" name="<?php echo esc_attr(self::OPTION); ?>[endpoint]" value="<?php echo esc_attr($endpoint); ?>" required>
                        <label for="zyvora-model">Model</label>
                        <input id="zyvora-model" class="regular-text" type="text" name="<?php echo esc_attr(self::OPTION); ?>[model]" value="<?php echo esc_attr($model); ?>" required>
                        <label for="zyvora-key">API key</label>
                        <input id="zyvora-key" class="regular-text" type="password" name="<?php echo esc_attr(self::OPTION); ?>[api_key]" value="" autocomplete="new-password" placeholder="<?php echo !empty($settings['api_key']) ? 'Saved — leave blank to keep' : 'Paste provider API key'; ?>">
                        <p class="zyvora-muted">Use HTTPS. Never share your API key or place it in frontend code.</p>
                        <?php submit_button(__('Save settings', 'zyvora-ai')); ?>
                    </form>
                    <div class="zyvora-note"><strong>Privacy note</strong><p>Your prompt is sent to the provider endpoint you configure. Do not submit sensitive personal or customer data unless your provider and policies permit it.</p></div>
                </aside>
            </div>
            <p class="zyvora-footer">ZYVORA AI · Version <?php echo esc_html(ZYVORA_AI_VERSION); ?> · Built for WordPress</p>
        </div>
        <?php
    }

    public function chat() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You are not allowed to use this feature.'), 403);
        }
        check_ajax_referer('zyvora_ai_chat', 'nonce');
        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field(wp_unslash($_POST['prompt'])) : '';
        if ($prompt === '' || strlen($prompt) > 12000) {
            wp_send_json_error(array('message' => 'Enter a message under 12,000 characters.'), 400);
        }
        $settings = get_option(self::OPTION, array());
        $key = isset($settings['api_key']) ? trim($settings['api_key']) : '';
        $endpoint = isset($settings['endpoint']) ? $settings['endpoint'] : '';
        $model = isset($settings['model']) ? $settings['model'] : '';
        if ($key === '' || $endpoint === '' || $model === '') {
            wp_send_json_error(array('message' => 'Save your API endpoint, model, and API key in Provider settings first.'), 400);
        }
        if (strpos($endpoint, 'https://') !== 0) {
            wp_send_json_error(array('message' => 'For security, the API endpoint must use HTTPS.'), 400);
        }

        $response = wp_remote_post($endpoint, array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Bearer ' . $key,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'model' => $model,
                'messages' => array(
                    array('role' => 'system', 'content' => 'You are ZYVORA AI, a helpful assistant for WordPress site administrators. Be accurate, practical, and concise.'),
                    array('role' => 'user', 'content' => $prompt),
                ),
            )),
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()), 502);
        }
        $status = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if ($status < 200 || $status >= 300) {
            $message = isset($body['error']['message']) ? sanitize_text_field($body['error']['message']) : 'The AI provider returned an error (HTTP ' . intval($status) . ').';
            wp_send_json_error(array('message' => $message), 502);
        }
        $answer = isset($body['choices'][0]['message']['content']) ? $body['choices'][0]['message']['content'] : '';
        if (!is_string($answer) || trim($answer) === '') {
            wp_send_json_error(array('message' => 'The provider response did not contain a chat message.'), 502);
        }
        wp_send_json_success(array('answer' => $answer));
    }
}
new Zyvora_AI();
