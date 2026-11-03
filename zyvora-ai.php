<?php
/**
 * Plugin Name: ZYVORA AI
 * Description: AI content studio and administrator assistant for WordPress.
 * Version: 1.1.0
 * Author: ZYVORA
 * License: GPL-2.0-or-later
 * Text Domain: zyvora-ai
 */
if (!defined('ABSPATH')) { exit; }
define('ZYVORA_AI_VERSION', '1.1.0');
define('ZYVORA_AI_URL', plugin_dir_url(__FILE__));

final class Zyvora_AI {
    const OPTION = 'zyvora_ai_settings';
    public function __construct() {
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'assets'));
        add_action('wp_ajax_zyvora_ai_chat', array($this, 'chat'));
        add_action('wp_ajax_zyvora_ai_generate', array($this, 'generate'));
        add_action('wp_ajax_zyvora_ai_save_draft', array($this, 'save_draft'));
    }
    public function menu() {
        add_menu_page(__('ZYVORA AI','zyvora-ai'), __('ZYVORA AI','zyvora-ai'), 'manage_options', 'zyvora-ai', array($this,'render_page'), 'dashicons-superhero', 58);
    }
    public function register_settings() { register_setting('zyvora_ai_group', self::OPTION, array($this,'sanitize_settings')); }
    public function sanitize_settings($input) {
        $old = get_option(self::OPTION, array()); $input = is_array($input) ? $input : array();
        $key = isset($input['api_key']) ? trim((string)$input['api_key']) : '';
        return array(
            'endpoint' => isset($input['endpoint']) ? esc_url_raw(trim($input['endpoint'])) : 'https://api.openai.com/v1/chat/completions',
            'model' => isset($input['model']) ? sanitize_text_field($input['model']) : 'gpt-4o-mini',
            'api_key' => $key !== '' ? sanitize_text_field($key) : (isset($old['api_key']) ? $old['api_key'] : ''),
        );
    }
    public function assets($hook) {
        if ($hook !== 'toplevel_page_zyvora-ai') return;
        wp_enqueue_style('zyvora-ai-admin', ZYVORA_AI_URL.'assets/admin.css', array(), ZYVORA_AI_VERSION);
        wp_enqueue_script('zyvora-ai-admin', ZYVORA_AI_URL.'assets/admin.js', array(), ZYVORA_AI_VERSION, true);
        wp_localize_script('zyvora-ai-admin','ZyvoraAI',array('ajaxUrl'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('zyvora_ai_chat')));
    }
    private function page_header() { ?>
        <header class="zyvora-header"><div><span class="zyvora-eyebrow">WORDPRESS AI WORKSPACE · VERSION <?php echo esc_html(ZYVORA_AI_VERSION); ?></span><h1>ZYVORA <span>AI</span></h1><p>Your intelligent content workspace for WordPress.</p></div><span class="zyvora-status"><i></i> ADMIN WORKSPACE</span></header>
    <?php }
    public function render_page() {
        if (!current_user_can('manage_options')) return;
        $s=get_option(self::OPTION,array());
        $endpoint=isset($s['endpoint'])?$s['endpoint']:'https://api.openai.com/v1/chat/completions';
        $model=isset($s['model'])?$s['model']:'gpt-4o-mini'; ?>
        <div class="wrap zyvora-wrap"><?php $this->page_header(); ?>
          <nav class="zyvora-tabs" aria-label="ZYVORA AI tools"><button class="active" data-tab="studio">Content Studio</button><button data-tab="assistant">AI Assistant</button><button data-tab="settings">Settings</button></nav>
          <section class="zyvora-tab-panel active" data-panel="studio">
            <div class="zyvora-panel">
              <div class="zyvora-section-heading"><div><h2>AI Content Studio</h2><p class="zyvora-muted">Create drafts, SEO ideas, rewrites and translations from one workspace.</p></div><span class="zyvora-chip">CONTENT TOOLS</span></div>
              <div class="zyvora-form-grid">
                <div><label for="zyvora-task">What do you want to create?</label><select id="zyvora-task"><option value="blog">Blog article</option><option value="seo">SEO title and meta description</option><option value="rewrite">Rewrite and improve text</option><option value="translate">Translate text</option><option value="social">Social media post</option><option value="outline">Article outline</option><option value="product">Product description</option></select></div>
                <div><label for="zyvora-language">Output language</label><select id="zyvora-language"><option>English</option><option>Hindi</option><option>Telugu</option><option>Tamil</option><option>Gujarati</option><option>Bengali</option><option>Marathi</option><option>Kannada</option><option>Malayalam</option><option>Punjabi</option></select></div>
              </div>
              <label for="zyvora-topic">Topic or source text</label><textarea id="zyvora-topic" rows="5" placeholder="Example: Write a beginner-friendly guide to WordPress security..."></textarea>
              <div class="zyvora-chat-actions"><span class="zyvora-muted">Generated content is not published automatically.</span><button id="zyvora-generate" class="button button-primary">Generate content ↗</button></div>
              <div id="zyvora-output-wrap" class="zyvora-output-wrap" hidden><div class="zyvora-output-head"><strong>Generated result</strong><div><button id="zyvora-copy" class="button">Copy</button><button id="zyvora-save-draft" class="button button-primary">Save as WordPress draft</button></div></div><textarea id="zyvora-output" rows="14" aria-label="Generated content"></textarea><p id="zyvora-studio-status" class="zyvora-muted" aria-live="polite"></p></div>
            </div>
          </section>
          <section class="zyvora-tab-panel" data-panel="assistant">
            <div class="zyvora-panel"><h2>AI Assistant</h2><p class="zyvora-muted">Ask questions, brainstorm, summarize or plan your WordPress work.</p>
              <div id="zyvora-chat-log" class="zyvora-chat-log" aria-live="polite"><div class="zyvora-message zyvora-assistant"><strong>ZYVORA AI</strong><p>Hello! I am ready. Configure your provider in Settings and ask me anything.</p></div></div>
              <form id="zyvora-chat-form"><label class="screen-reader-text" for="zyvora-prompt">Your message</label><textarea id="zyvora-prompt" rows="3" placeholder="Ask ZYVORA AI..." required></textarea><div class="zyvora-chat-actions"><span class="zyvora-muted">Review AI responses before using them.</span><button class="button button-primary" type="submit">Send message ↗</button></div></form>
            </div>
          </section>
          <section class="zyvora-tab-panel" data-panel="settings">
            <div class="zyvora-panel zyvora-settings"><h2>AI Provider Settings</h2><p class="zyvora-muted">Connect an OpenAI-compatible Chat Completions API. The API key is used server-side only.</p>
              <form method="post" action="options.php"><?php settings_fields('zyvora_ai_group'); ?>
                <label for="zyvora-endpoint">API endpoint</label><input id="zyvora-endpoint" class="regular-text" type="url" name="<?php echo esc_attr(self::OPTION); ?>[endpoint]" value="<?php echo esc_attr($endpoint); ?>" required>
                <label for="zyvora-model">Model</label><input id="zyvora-model" class="regular-text" type="text" name="<?php echo esc_attr(self::OPTION); ?>[model]" value="<?php echo esc_attr($model); ?>" required>
                <label for="zyvora-key">API key</label><input id="zyvora-key" class="regular-text" type="password" name="<?php echo esc_attr(self::OPTION); ?>[api_key]" value="" autocomplete="new-password" placeholder="<?php echo !empty($s['api_key'])?'Saved — leave blank to keep':'Paste provider API key'; ?>">
                <p class="zyvora-muted">Use HTTPS. A blank key field preserves the currently saved key.</p><?php submit_button(__('Save provider settings','zyvora-ai')); ?>
              </form>
              <div class="zyvora-note"><strong>Privacy and security</strong><p>Prompts are sent to the configured provider. API keys are stored in the WordPress options table. Restrict administrator access and review your provider's data policies.</p></div>
            </div>
          </section>
          <p class="zyvora-footer">ZYVORA AI · Built for WordPress · AI output requires human review</p>
        </div><?php
    }
    private function request_ai($prompt) {
        $s=get_option(self::OPTION,array()); $key=isset($s['api_key'])?trim($s['api_key']):'';
        $url=isset($s['endpoint'])?$s['endpoint']:''; $model=isset($s['model'])?$s['model']:'';
        if (!$key || !$url || !$model) return new WP_Error('zyvora_setup','Save your API endpoint, model and API key in Settings first.');
        if (strpos($url,'https://')!==0) return new WP_Error('zyvora_https','For security, the API endpoint must use HTTPS.');
        $res=wp_remote_post($url,array('timeout'=>90,'headers'=>array('Authorization'=>'Bearer '.$key,'Content-Type'=>'application/json'),'body'=>wp_json_encode(array('model'=>$model,'messages'=>array(array('role'=>'system','content'=>'You are ZYVORA AI, a careful and helpful WordPress content assistant. Follow the requested output language. Do not claim to have performed actions you did not perform.'),array('role'=>'user','content'=>$prompt))))));
        if (is_wp_error($res)) return $res;
        $status=wp_remote_retrieve_response_code($res); $body=json_decode(wp_remote_retrieve_body($res),true);
        if ($status<200 || $status>=300) return new WP_Error('zyvora_provider',isset($body['error']['message'])?sanitize_text_field($body['error']['message']):'AI provider error (HTTP '.intval($status).').');
        $answer=isset($body['choices'][0]['message']['content'])?$body['choices'][0]['message']['content']:'';
        if (!is_string($answer)||trim($answer)==='') return new WP_Error('zyvora_empty','The provider returned an empty response.');
        return $answer;
    }
    private function authorize_ajax() {
        if (!current_user_can('manage_options')) wp_send_json_error(array('message'=>'Administrator permission required.'),403);
        check_ajax_referer('zyvora_ai_chat','nonce');
    }
    public function chat() {
        $this->authorize_ajax(); $p=isset($_POST['prompt'])?sanitize_textarea_field(wp_unslash($_POST['prompt'])):'';
        if (!$p||strlen($p)>12000) wp_send_json_error(array('message'=>'Enter a message under 12,000 characters.'),400);
        $answer=$this->request_ai($p); if (is_wp_error($answer)) wp_send_json_error(array('message'=>$answer->get_error_message()),502);
        wp_send_json_success(array('answer'=>$answer));
    }
    public function generate() {
        $this->authorize_ajax();
        $topic=isset($_POST['topic'])?sanitize_textarea_field(wp_unslash($_POST['topic'])):'';
        $task=isset($_POST['task'])?sanitize_key($_POST['task']):'blog';
        $lang=isset($_POST['language'])?sanitize_text_field(wp_unslash($_POST['language'])):'English';
        $tasks=array(
            'blog'=>'Write a useful, original, well-structured blog article. Include a clear title, introduction, descriptive headings and conclusion.',
            'seo'=>'Create an SEO title, meta description, focus keyphrase and 5 related keyphrases. Keep the meta description concise.',
            'rewrite'=>'Rewrite the supplied text for clarity, grammar, flow and usefulness while preserving its meaning.',
            'translate'=>'Translate the supplied text naturally and accurately. Preserve names, URLs and formatting.',
            'social'=>'Create an engaging social media post with a hook, concise body and relevant non-spammy hashtags.',
            'outline'=>'Create a detailed article outline with H1, H2 and H3 headings plus key points under each.',
            'product'=>'Write a compelling, factual product description with benefits, features and a short summary. Do not invent specifications.'
        );
        if (!$topic||strlen($topic)>12000||!isset($tasks[$task])) wp_send_json_error(array('message'=>'Enter a topic or source text (maximum 12,000 characters).'),400);
        $prompt=$tasks[$task]."\nOutput language: ".$lang."\n\nTopic or source text:\n".$topic;
        $answer=$this->request_ai($prompt); if (is_wp_error($answer)) wp_send_json_error(array('message'=>$answer->get_error_message()),502);
        wp_send_json_success(array('content'=>$answer));
    }
    public function save_draft() {
        $this->authorize_ajax();
        $content=isset($_POST['content'])?wp_kses_post(wp_unslash($_POST['content'])):'';
        if (trim(wp_strip_all_tags($content))===''||strlen($content)>50000) wp_send_json_error(array('message'=>'Generated content is empty or too large.'),400);
        $title=wp_trim_words(wp_strip_all_tags($content),10,'…');
        $post_id=wp_insert_post(array('post_title'=>$title,'post_content'=>$content,'post_status'=>'draft','post_type'=>'post','post_author'=>get_current_user_id()),true);
        if (is_wp_error($post_id)) wp_send_json_error(array('message'=>$post_id->get_error_message()),500);
        wp_send_json_success(array('message'=>'Draft created.','edit_url'=>get_edit_post_link($post_id,'raw')));
    }
}
new Zyvora_AI();
