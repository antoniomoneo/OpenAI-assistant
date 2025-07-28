<?php
/*
Plugin Name: OpenAI Assistant
Description: Embed OpenAI Assistants via shortcode.
Version: 5
Author: Tangible Data
Text Domain: oa-assistant
*/

if (!defined('ABSPATH')) exit;

class OA_Assistant_Plugin {
    private $log_file;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $upload = wp_upload_dir();
        $this->log_file = trailingslashit($upload['basedir']) . 'chatgpt_assistant_debug.log';

        if (get_option('oa_assistant_enable_logs')) {
            if (!file_exists($upload['basedir'])) {
                wp_mkdir_p($upload['basedir']);
            }
            set_error_handler([$this, 'handle_php_error']);
        }
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_post_oa_add_assistant', [$this, 'handle_add_assistant']);
        add_action('admin_post_oa_delete_assistant', [$this, 'handle_delete_assistant']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('wp_ajax_oa_assistant_chat', [$this, 'ajax_chat']);
        add_action('wp_ajax_nopriv_oa_assistant_chat', [$this, 'ajax_chat']);
        add_action('wp_ajax_oa_assistant_send_key', [$this, 'ajax_send_key']);
        add_filter('query_vars', [$this, 'register_query_vars']);
        add_action('template_redirect', [$this, 'maybe_render_embed']);
        add_filter('amp_allowed_tags', [$this, 'allow_amp_script']);
    }

    public function add_admin_menu() {
        add_menu_page('OpenAI Assistant', 'OpenAI Assistant', 'manage_options', 'oa-assistant', [$this, 'settings_page'], 'dashicons-format-chat');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('OpenAI Assistant', 'oa-assistant'); ?></h1>

            <h2><?php esc_html_e('API Key', 'oa-assistant'); ?></h2>
            <p><?php esc_html_e('Clave secreta de OpenAI para autenticar las llamadas a la API.', 'oa-assistant'); ?></p>
            <form method="post" action="options.php">
                <?php
                settings_fields('oa-assistant-general');
                do_settings_sections('oa-assistant-general');
                submit_button();
                ?>
            </form>

            <h2><?php esc_html_e('Asistentes configurados', 'oa-assistant'); ?></h2>
            <div class="oa-table-wrap">
                <table class="widefat oa-assistants-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Nombre', 'oa-assistant'); ?></th>
                            <th><?php esc_html_e('Slug', 'oa-assistant'); ?></th>
                            <th><?php esc_html_e('Assistant ID', 'oa-assistant'); ?></th>
                            <th><?php esc_html_e('Acciones', 'oa-assistant'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $list = get_option('openai_assistants_list', []);
                        if (empty($list)) :
                        ?>
                        <tr><td colspan="4"><?php esc_html_e('Sin asistentes', 'oa-assistant'); ?></td></tr>
                        <?php else :
                            foreach ($list as $slug => $a) : ?>
                        <tr>
                            <td><?php echo esc_html($a['name']); ?></td>
                            <td>
                                <input type="text" class="oa-slug-field" readonly value="[openai_assistant slug=&quot;<?php echo esc_attr($slug); ?>&quot;]" />
                                <button type="button" class="button oa-copy-slug" data-slug="[openai_assistant slug=&quot;<?php echo esc_attr($slug); ?>&quot;]"><span class="dashicons dashicons-clipboard"></span></button>
                            </td>
                            <td><?php echo esc_html($a['assistant_id']); ?></td>
                            <td>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                                    <?php wp_nonce_field('oa_delete_assistant'); ?>
                                    <input type="hidden" name="action" value="oa_delete_assistant" />
                                    <input type="hidden" name="slug" value="<?php echo esc_attr($slug); ?>" />
                                    <?php submit_button(__('Eliminar', 'oa-assistant'), 'delete', 'submit', false); ?>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <h3><?php esc_html_e('Añadir nuevo asistente', 'oa-assistant'); ?></h3>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('oa_add_assistant'); ?>
                <input type="hidden" name="action" value="oa_add_assistant" />
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="oa_name"><?php esc_html_e('Nombre', 'oa-assistant'); ?></label></th>
                        <td><input name="name" id="oa_name" type="text" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="oa_slug"><?php esc_html_e('Slug', 'oa-assistant'); ?></label></th>
                        <td><input name="slug" id="oa_slug" type="text" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="oa_assistant_id"><?php esc_html_e('Assistant ID', 'oa-assistant'); ?></label></th>
                        <td><input name="assistant_id" id="oa_assistant_id" type="text" class="regular-text" required></td>
                    </tr>
                </table>
                <?php submit_button(__('Añadir asistente', 'oa-assistant')); ?>
            </form>

            <h2><?php esc_html_e('Logs de depuración', 'oa-assistant'); ?></h2>
            <?php
            $enabled = get_option('oa_assistant_enable_logs', false);
            if ($enabled) {
                $content = file_exists($this->log_file) ? file_get_contents($this->log_file) : '';
                echo '<textarea readonly rows="10" style="width:100%;">'.esc_textarea($content).'</textarea>';
            } else {
                echo '<p>'.esc_html__('Los logs están desactivados.', 'oa-assistant').'</p>';
            }
            ?>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting('oa-assistant-general', 'openai_api_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ]);
        register_setting('oa-assistant-general', 'oa_assistant_enable_logs', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false,
        ]);
        add_settings_section('oa-assistant-api-section', 'Ajustes generales', function(){
            echo '<p>' . esc_html__('Clave secreta de OpenAI para autenticar las llamadas a la API.', 'oa-assistant') . '</p>';
        }, 'oa-assistant-general');
        add_settings_field('openai_api_key', 'OpenAI API Key', function(){
            $val = esc_attr(get_option('openai_api_key', ''));
            echo '<input type="password" id="openai_api_key" name="openai_api_key" value="'.$val.'" class="regular-text" /> ';
            echo '<button type="button" class="button oa-recover-key">'.esc_html__('Recuperar', 'oa-assistant').'</button>';
        }, 'oa-assistant-general', 'oa-assistant-api-section');
        add_settings_field('oa_assistant_enable_logs', __('Guardar logs', 'oa-assistant'), function(){
            $val = get_option('oa_assistant_enable_logs', false);
            echo '<input type="checkbox" name="oa_assistant_enable_logs" value="1" '.checked(1, $val, false).' />';
        }, 'oa-assistant-general', 'oa-assistant-api-section');
    }


    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_oa-assistant') return;
        wp_enqueue_style('oa-admin-css', plugin_dir_url(__FILE__).'css/assistant.css', [], '3');
        wp_enqueue_style('dashicons');
        wp_enqueue_script('oa-admin-js', plugin_dir_url(__FILE__).'js/assistant.js', ['jquery'], '3', true);
        wp_localize_script('oa-admin-js', 'oaAssistant', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('oa_assistant_send_key'),
        ]);
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style('oa-frontend-css', plugin_dir_url(__FILE__).'css/assistant.css', [], '3');
        wp_enqueue_script('oa-frontend-js', plugin_dir_url(__FILE__).'js/assistant-frontend.js', ['jquery'], '3', true);
    }

    public function register_shortcodes() {
        add_shortcode('openai_assistant', [$this, 'render_assistant_shortcode']);
    }

    public function register_query_vars($vars) {
        $vars[] = 'oa_assistant_embed';
        $vars[] = 'oa_assistant_slug';
        return $vars;
    }

    public function maybe_render_embed() {
        if (!get_query_var('oa_assistant_embed')) {
            return;
        }

        $slug = get_query_var('oa_assistant_slug');
        if (!$slug) {
            status_header(400);
            echo 'Missing assistant slug';
            exit;
        }

        echo '<!DOCTYPE html><html><head>';
        wp_head();
        echo '</head><body>';
        echo do_shortcode('[openai_assistant slug="'.esc_attr($slug).'"]');
        wp_footer();
        echo '</body></html>';
        exit;
    }

    public function render_assistant_shortcode($atts) {
        $atts = shortcode_atts(['slug' => ''], $atts, 'openai_assistant');
        if (function_exists('amp_is_request') && amp_is_request()) {
            if (empty($atts['slug'])) {
                return '<p style="color:red;">Error: falta atributo slug.</p>';
            }
            $ajax_url = esc_attr(admin_url('admin-ajax.php'));
            $nonce    = esc_attr(wp_create_nonce('oa_assistant_chat'));
            ob_start(); ?>
            <amp-script layout="container" src="<?php echo esc_url(plugins_url('js/assistant-amp.js', __FILE__)); ?>" data-ampdevmode>
              <div class="oa-assistant-chat"
                   data-slug="<?php echo esc_attr($atts['slug']); ?>"
                   data-ajax="<?php echo $ajax_url; ?>"
                   data-nonce="<?php echo $nonce; ?>">
                <div class="oa-messages"></div>
                <form class="oa-form">
                  <input type="text" name="user_message" placeholder="Escribe tu mensaje…" required />
                  <button type="submit">Enviar</button>
                </form>
              </div>
            </amp-script>
            <?php
            return ob_get_clean();
        }
        if (empty($atts['slug'])) {
            return '<p style="color:red;">Error: falta atributo slug.</p>';
        }
        $configs = get_option('openai_assistants_list', []);
        $c = $configs[$atts['slug']] ?? null;
        if (!$c) {
            return '<p style="color:red;">Assistant “'.esc_html($atts['slug']).'” no encontrado.</p>';
        }


        $ajax_url = esc_attr(admin_url('admin-ajax.php'));
        $nonce    = esc_attr(wp_create_nonce('oa_assistant_chat'));

        ob_start(); ?>
        <div class="oa-assistant-chat"
             data-slug="<?php echo esc_attr($atts['slug']); ?>"
             data-ajax="<?php echo $ajax_url; ?>"
             data-nonce="<?php echo $nonce; ?>">
          <div class="oa-messages"></div>
          <form class="oa-form">
            <input type="text" name="user_message" placeholder="Escribe tu mensaje…" required />
            <button type="submit">Enviar</button>
          </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function allow_amp_script($tags) {
        $tags['amp-script'] = [
            'layout' => true,
            'src'    => true,
            'data-ampdevmode' => true,
        ];
        return $tags;
    }

    public function ajax_chat() {
        check_ajax_referer('oa_assistant_chat','nonce');
        $start = microtime(true);
        $msg  = sanitize_text_field($_POST['message'] ?? '');
        if (!$msg) {
            $this->json_error('Falta mensaje');
        }

        $slug = sanitize_title($_POST['slug'] ?? '');
        $list = get_option('openai_assistants_list', []);
        $assistant_id = $list[$slug]['assistant_id'] ?? '';
        $api_key      = get_option('openai_api_key');
        if (!$assistant_id || !$api_key) {
            $this->json_error('Configuración incompleta', 500);
        }

        $headers = [
            'Authorization' => 'Bearer ' . $api_key,
            'OpenAI-Beta'   => 'assistants=v1',
            'Content-Type'  => 'application/json',
        ];

        if (empty($_SESSION['openai_thread_id'])) {
            $thread = wp_remote_post('https://api.openai.com/v1/threads', [
                'headers' => $headers,
                'body'    => wp_json_encode([]),
            ]);
            if (is_wp_error($thread)) {
                $this->json_error($thread->get_error_message(), 500);
            }
            $t_body = json_decode(wp_remote_retrieve_body($thread), true);
            if (isset($t_body['error'])) {
                $this->json_error($t_body['error']['message'] ?? 'API error', 500);
            }
            $_SESSION['openai_thread_id'] = $t_body['id'] ?? '';
            if (!$_SESSION['openai_thread_id']) {
                $this->json_error('No se pudo crear el thread', 500);
            }
        }
        $thread_id = $_SESSION['openai_thread_id'];

        $send = wp_remote_post("https://api.openai.com/v1/threads/{$thread_id}/messages", [
            'headers' => $headers,
            'body'    => wp_json_encode([
                'role'    => 'user',
                'content' => $msg,
            ]),
        ]);
        if (is_wp_error($send)) {
            $this->json_error($send->get_error_message(), 500);
        }
        $send_body = json_decode(wp_remote_retrieve_body($send), true);
        if (isset($send_body['error'])) {
            $this->json_error($send_body['error']['message'] ?? 'API error', 500);
        }

        $run = wp_remote_post("https://api.openai.com/v1/threads/{$thread_id}/runs", [
            'headers' => $headers,
            'body'    => wp_json_encode(['assistant_id' => $assistant_id]),
        ]);
        if (is_wp_error($run)) {
            $this->json_error($run->get_error_message(), 500);
        }
        $run_body = json_decode(wp_remote_retrieve_body($run), true);
        if (isset($run_body['error'])) {
            $this->json_error($run_body['error']['message'] ?? 'API error', 500);
        }

        $run_id = $run_body['id'] ?? '';
        $status = $run_body['status'] ?? '';
        $tries  = 0;
        while ($status !== 'completed' && $tries < 30) {
            sleep(1);
            $tries++;
            $chk = wp_remote_get("https://api.openai.com/v1/threads/{$thread_id}/runs/{$run_id}", [
                'headers' => $headers,
            ]);
            if (is_wp_error($chk)) {
                $this->json_error($chk->get_error_message(), 500);
            }
            $chk_body = json_decode(wp_remote_retrieve_body($chk), true);
            if (isset($chk_body['error'])) {
                $this->json_error($chk_body['error']['message'] ?? 'API error', 500);
            }
            $status = $chk_body['status'] ?? '';
        }
        $duration = microtime(true) - $start;
        if ($status !== 'completed') {
            $this->log_debug('Run duration: '.round($duration,2).'s tries: '.$tries.' (max reached)');
            $this->json_error('Run no completado');
        }
        $this->log_debug('Run duration: '.round($duration,2).'s tries: '.$tries);

        $msgs = wp_remote_get("https://api.openai.com/v1/threads/{$thread_id}/messages", [
            'headers' => $headers,
        ]);
        if (is_wp_error($msgs)) {
            $this->json_error($msgs->get_error_message(), 500);
        }
        $msgs_body = json_decode(wp_remote_retrieve_body($msgs), true);
        if (isset($msgs_body['error'])) {
            $this->json_error($msgs_body['error']['message'] ?? 'API error', 500);
        }

        $reply = '';
        if (!empty($msgs_body['data'])) {
            foreach (array_reverse($msgs_body['data']) as $m) {
                if (($m['role'] ?? '') === 'assistant') {
                    $reply = $m['content'][0]['text']['value'] ?? '';
                    break;
                }
            }
        }

        if (!$reply) {
            $this->json_error('Sin respuesta del assistant');
        }

        $this->json_success(['reply' => $reply]);
    }

    public function ajax_send_key() {
        check_ajax_referer('oa_assistant_send_key', 'nonce');
        if (!current_user_can('manage_options')) {
            $this->json_error('Unauthorized', 403);
        }
        $key = get_option('openai_api_key', '');
        if (!$key) {
            $this->json_error('No API key');
        }
        $sent = wp_mail(get_option('admin_email'), 'OpenAI Assistant API Key', 'Tu API Key: ' . $key);
        if ($sent) {
            $this->json_success('Email enviado');
        } else {
            $this->json_error('No se pudo enviar el email', 500);
        }
    }

    public function handle_add_assistant() {
        check_admin_referer('oa_add_assistant');
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $name = sanitize_text_field($_POST['name'] ?? '');
        $slug = sanitize_title($_POST['slug'] ?? '');
        $assistant_id = sanitize_text_field($_POST['assistant_id'] ?? '');
        if (!$name || !$slug || !$assistant_id) {
            wp_redirect(menu_page_url('oa-assistant', false));
            exit;
        }
        $list = get_option('openai_assistants_list', []);
        if (isset($list[$slug])) {
            wp_redirect(menu_page_url('oa-assistant', false));
            exit;
        }
        $list[$slug] = ['name' => $name, 'assistant_id' => $assistant_id];
        update_option('openai_assistants_list', $list);
        wp_redirect(menu_page_url('oa-assistant', false));
        exit;
    }

    public function handle_delete_assistant() {
        check_admin_referer('oa_delete_assistant');
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $slug = sanitize_title($_POST['slug'] ?? '');
        $list = get_option('openai_assistants_list', []);
        if (isset($list[$slug])) {
            unset($list[$slug]);
            update_option('openai_assistants_list', $list);
        }
        wp_redirect(menu_page_url('oa-assistant', false));
        exit;
    }

    private function list_assistants() {
        $key = get_option('openai_api_key', '');
        if (!$key) {
            return new WP_Error('no_key', 'No API key');
        }
        $response = wp_remote_get('https://api.openai.com/v1/assistants', [
            'headers' => [
                'Authorization' => 'Bearer ' . $key,
                'Content-Type'  => 'application/json',
            ],
        ]);
        if (is_wp_error($response)) {
            return $response;
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['data'] ?? [];
    }

    private function log_debug($msg) {
        if (!get_option('oa_assistant_enable_logs')) {
            return;
        }
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
        file_put_contents($this->log_file, $line, FILE_APPEND);
    }

    public function handle_php_error($errno, $errstr, $errfile, $errline) {
        $msg = "PHP error [$errno] $errstr in $errfile:$errline";
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $msg .= "\n" . wp_debug_backtrace_summary(null, 0, false);
        }
        $this->log_debug($msg);
        return false;
    }

    private function log_ajax_result($code, $data = null) {
        $msg = 'HTTP ' . $code;
        if ($code != 200 && $data !== null) {
            if (!is_string($data)) {
                $data = wp_json_encode($data);
            }
            $msg .= ' Response: ' . $data;
        }
        $this->log_debug($msg);
    }

    private function json_error($data, $code = 200) {
        $this->log_ajax_result($code, $data);
        wp_send_json_error($data, $code);
    }

    private function json_success($data) {
        $this->log_ajax_result(200);
        wp_send_json_success($data);
    }

    // Placeholder: implement your vector DB retrieval logic
    private function get_vector_context($vector_store_id, $query) {
        // TODO: conectarse a tu vector store usando $vector_store_id y retornar array de fragmentos relevantes
        return [];
    }
}

new OA_Assistant_Plugin();
