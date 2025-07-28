<?php
/*
Plugin Name: OpenAI Assistant
Description: Embed OpenAI Assistants via shortcode.
Version: 3
Author: Tangible Data
Text Domain: oa-assistant
*/

if (!defined('ABSPATH')) exit;

class OA_Assistant_Plugin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('wp_ajax_oa_assistant_chat', [$this, 'ajax_chat']);
        add_action('wp_ajax_nopriv_oa_assistant_chat', [$this, 'ajax_chat']);
        add_action('wp_ajax_oa_assistant_send_key', [$this, 'ajax_send_key']);
        add_filter('query_vars', [$this, 'register_query_vars']);
        add_action('template_redirect', [$this, 'maybe_render_embed']);
    }

    public function add_admin_menu() {
        add_menu_page('OpenAI Assistant', 'OpenAI Assistant', 'manage_options', 'oa-assistant', [$this, 'settings_page'], 'dashicons-format-chat');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('OpenAI Assistant', 'oa-assistant'); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('oa-assistant-general');
                do_settings_sections('oa-assistant-general');
                submit_button();
                ?>
            </form>

            <h2><?php esc_html_e('Assistants', 'oa-assistant'); ?></h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('oa-assistant-configs');
                $configs = get_option('oa_assistant_configs', []);
                ?>
                <div class="oa-table-wrap">
                    <table class="widefat oa-assistants-table">
                        <thead>
                            <tr>
                                <th class="oa-old-field"><?php esc_html_e('Nombre', 'oa-assistant'); ?></th>
                                <th class="oa-old-field"><?php esc_html_e('Slug', 'oa-assistant'); ?></th>
                                <th class="oa-old-field"><?php esc_html_e('Assistant ID', 'oa-assistant'); ?></th>
                                <th class="oa-old-field"><?php esc_html_e('Instrucciones', 'oa-assistant'); ?></th>
                                <th class="oa-old-field"><?php esc_html_e('Vector Store ID', 'oa-assistant'); ?></th>
                                <th><?php esc_html_e('Modelo', 'oa-assistant'); ?></th>
                                <th><?php esc_html_e('Descripción', 'oa-assistant'); ?></th>
                                <th class="oa-old-field"><?php esc_html_e('Creado', 'oa-assistant'); ?></th>
                                <th class="oa-old-field"><?php esc_html_e('Debug', 'oa-assistant'); ?></th>
                                <th><?php esc_html_e('Acciones', 'oa-assistant'); ?></th>
                            </tr>
                        </thead>
                    <tbody>
                        <?php if (empty($configs)) : ?>
                            <tr>
                                <td colspan="10"><?php esc_html_e('Sin asistentes', 'oa-assistant'); ?></td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($configs as $i => $cfg) : ?>
                                <tr data-index="<?php echo $i; ?>">
                                    <td class="oa-old-field"><input type="text" name="oa_assistant_configs[<?php echo $i; ?>][nombre]" value="<?php echo esc_attr($cfg['nombre']); ?>" class="regular-text" /></td>
                                    <td class="oa-old-field"><input type="text" name="oa_assistant_configs[<?php echo $i; ?>][slug]" value="<?php echo esc_attr($cfg['slug']); ?>" class="regular-text" /></td>
                                    <td class="oa-old-field"><input type="text" name="oa_assistant_configs[<?php echo $i; ?>][assistant_id]" value="<?php echo esc_attr($cfg['assistant_id']); ?>" class="regular-text" /></td>
                                    <td class="oa-old-field"><textarea name="oa_assistant_configs[<?php echo $i; ?>][developer_instructions]" rows="2" class="regular-text"><?php echo esc_textarea($cfg['developer_instructions']); ?></textarea></td>
                                    <td class="oa-old-field"><input type="text" name="oa_assistant_configs[<?php echo $i; ?>][vector_store_id]" value="<?php echo esc_attr($cfg['vector_store_id']); ?>" class="regular-text" /></td>
                                    <td><input type="text" name="oa_assistant_configs[<?php echo $i; ?>][model]" value="<?php echo esc_attr($cfg['model'] ?? ''); ?>" class="regular-text" /></td>
                                    <td><textarea name="oa_assistant_configs[<?php echo $i; ?>][description]" rows="2" class="regular-text"><?php echo esc_textarea($cfg['description'] ?? ''); ?></textarea></td>
                                    <td class="oa-old-field">
                                        <?php echo esc_html($cfg['created_at'] ?? ''); ?>
                                        <input type="hidden" name="oa_assistant_configs[<?php echo $i; ?>][created_at]" value="<?php echo esc_attr($cfg['created_at'] ?? ''); ?>" class="created-at-field" />
                                    </td>
                                    <td class="oa-old-field"><input type="checkbox" name="oa_assistant_configs[<?php echo $i; ?>][debug]" <?php checked(!empty($cfg['debug'])); ?> /></td>
                                    <td><button type="button" class="button-link-delete oa-remove-assistant"><?php esc_html_e('Eliminar asistente', 'oa-assistant'); ?></button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
                <script type="text/html" id="oa-row-template">
                    <tr data-index="__i__">
                        <td class="oa-old-field"><input type="text" name="oa_assistant_configs[__i__][nombre]" class="regular-text" /></td>
                        <td class="oa-old-field"><input type="text" name="oa_assistant_configs[__i__][slug]" class="regular-text" /></td>
                        <td class="oa-old-field"><input type="text" name="oa_assistant_configs[__i__][assistant_id]" class="regular-text" /></td>
                        <td class="oa-old-field"><textarea name="oa_assistant_configs[__i__][developer_instructions]" rows="2" class="regular-text"></textarea></td>
                        <td class="oa-old-field"><input type="text" name="oa_assistant_configs[__i__][vector_store_id]" class="regular-text" /></td>
                        <td><input type="text" name="oa_assistant_configs[__i__][model]" class="regular-text" /></td>
                        <td><textarea name="oa_assistant_configs[__i__][description]" rows="2" class="regular-text"></textarea></td>
                        <td class="oa-old-field"><span class="creation-date"></span><input type="hidden" name="oa_assistant_configs[__i__][created_at]" class="created-at-field" value="" /></td>
                        <td class="oa-old-field"><input type="checkbox" name="oa_assistant_configs[__i__][debug]" /></td>
                        <td><button type="button" class="button-link-delete oa-remove-assistant"><?php esc_html_e('Eliminar asistente', 'oa-assistant'); ?></button></td>
                    </tr>
                </script>
                <p>
                    <button type="button" class="button oa-add-assistant"><?php esc_html_e('Añadir asistente', 'oa-assistant'); ?></button>
                </p>
                <?php submit_button(); ?>
            </form>

            <?php
            $existing = $this->list_assistants();
            if (!is_wp_error($existing) && !empty($existing)) {
                echo '<h2>' . esc_html__('Existing Assistants', 'oa-assistant') . '</h2>';
                echo '<ul class="oa-existing-assistants">';
                foreach ($existing as $asst) {
                    $name = $asst['name'] ?? $asst['id'];
                    echo '<li>' . esc_html($name) . ' (' . esc_html($asst['id']) . ')</li>';
                }
                echo '</ul>';
            } elseif (is_wp_error($existing)) {
                echo '<p style="color:red;">' . esc_html($existing->get_error_message()) . '</p>';
            }
            ?>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting('oa-assistant-general', 'oa_assistant_api_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ]);
        add_settings_section('oa-assistant-api-section', 'Ajustes generales', function(){
            echo '<p>Tu clave secreta de OpenAI.</p>';
        }, 'oa-assistant-general');
        add_settings_field('oa_assistant_api_key', 'OpenAI API Key', function(){
            $val = esc_attr(get_option('oa_assistant_api_key', ''));
            echo '<input type="password" id="oa_assistant_api_key" name="oa_assistant_api_key" value="'.$val.'" class="regular-text" /> ';
            echo '<button type="button" class="button oa-recover-key">'.esc_html__('Recuperar', 'oa-assistant').'</button>';
        }, 'oa-assistant-general', 'oa-assistant-api-section');

        register_setting('oa-assistant-configs', 'oa_assistant_configs', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_configs'],
            'default' => [],
        ]);
    }

    public function sanitize_configs($configs) {
        if (!is_array($configs)) return [];
        $sanitized = [];
        foreach ($configs as $cfg) {
            if (empty($cfg['slug'])) continue;
            $sanitized[] = [
                'nombre' => sanitize_text_field($cfg['nombre'] ?? ''),
                'slug' => sanitize_title($cfg['slug'] ?? ''),
                'assistant_id' => sanitize_text_field($cfg['assistant_id'] ?? ''),
                'developer_instructions' => sanitize_textarea_field($cfg['developer_instructions'] ?? ''),
                'vector_store_id' => sanitize_text_field($cfg['vector_store_id'] ?? ''),
                'model' => sanitize_text_field($cfg['model'] ?? ''),
                'description' => sanitize_textarea_field($cfg['description'] ?? ''),
                'created_at' => sanitize_text_field($cfg['created_at'] ?? current_time('mysql')),
                'debug' => empty($cfg['debug']) ? 0 : 1,
            ];
        }
        return $sanitized;
    }

    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_oa-assistant') return;
        wp_enqueue_style('oa-admin-css', plugin_dir_url(__FILE__).'css/assistant.css', [], '3');
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
        if (empty($atts['slug'])) {
            return '<p style="color:red;">Error: falta atributo slug.</p>';
        }
        $configs = get_option('oa_assistant_configs', []);
        $cfgs = array_filter($configs, function($c) use ($atts) {
            return $c['slug'] === $atts['slug'];
        });
        if (!$cfgs) {
            return '<p style="color:red;">Assistant “'.esc_html($atts['slug']).'” no encontrado.</p>';
        }
        $c = array_pop($cfgs);


        $ajax_url = esc_attr(admin_url('admin-ajax.php'));
        $nonce    = esc_attr(wp_create_nonce('oa_assistant_chat'));

        ob_start(); ?>
        <div class="oa-assistant-chat"
             data-slug="<?php echo esc_attr($c['slug']); ?>"
             data-ajax="<?php echo $ajax_url; ?>"
             data-nonce="<?php echo $nonce; ?>"
             data-debug="<?php echo !empty($c['debug']) ? '1' : '0'; ?>">
          <div class="oa-messages"></div>
          <?php if (!empty($c['debug'])) : ?>
          <pre class="oa-debug-log"></pre>
          <?php endif; ?>
          <form class="oa-form">
            <input type="text" name="user_message" placeholder="Escribe tu mensaje…" required />
            <button type="submit">Enviar</button>
          </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_chat() {
        check_ajax_referer('oa_assistant_chat','nonce');
        $slug = sanitize_text_field($_POST['slug'] ?? '');
        $msg  = sanitize_text_field($_POST['message'] ?? '');
        if (!$slug || !$msg) {
            wp_send_json_error('Faltan parámetros');
        }
        $configs = get_option('oa_assistant_configs', []);
        $cfgs = array_filter($configs, function($c) use ($slug) {
            return $c['slug'] === $slug;
        });
        if (!$cfgs) {
            wp_send_json_error('Assistant no encontrado');
        }
        $c = array_pop($cfgs);
        $debug_lines = [];
        if (!empty($c['debug'])) {
            $debug_lines[] = 'Slug: ' . $slug;
            $debug_lines[] = 'Mensaje usuario: ' . $msg;
        }

        // Retrieve context from vector store (implement your function)
        $context_chunks = $this->get_vector_context($c['vector_store_id'], $msg);
        if (!empty($c['debug'])) {
            $debug_lines[] = 'Contexto: ' . implode(" | ", $context_chunks);
        }

        $api_key = get_option('oa_assistant_api_key');
        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
            'OpenAI-Beta'   => 'assistants=v1',
        ];

        $cookie_name = 'oa_asst_thread_' . $slug;
        $thread_id = sanitize_text_field($_COOKIE[$cookie_name] ?? '');
        if (!$thread_id) {
            $res = wp_remote_post('https://api.openai.com/v1/threads', [
                'headers' => $headers,
                'body'    => wp_json_encode([]),
            ]);
            if (is_wp_error($res)) {
                wp_send_json_error($res->get_error_message(), 500);
            }
            $body = json_decode(wp_remote_retrieve_body($res), true);
            if (isset($body['error'])) {
                wp_send_json_error($body['error']['message'] ?? 'API error', 500);
            }
            $thread_id = $body['id'] ?? '';
            if (!$thread_id) {
                wp_send_json_error('No se pudo crear thread', 500);
            }
            setcookie($cookie_name, $thread_id, time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            if (!empty($c['debug'])) {
                $debug_lines[] = 'Thread creado: ' . $thread_id;
            }
        } elseif (!empty($c['debug'])) {
            $debug_lines[] = 'Thread existente: ' . $thread_id;
        }

        $full_msg = $msg;
        if (!empty($context_chunks)) {
            $full_msg = "Contexto relevante:\n" . implode("\n", $context_chunks) . "\n\n" . $msg;
        }

        $send = wp_remote_post("https://api.openai.com/v1/threads/{$thread_id}/messages", [
            'headers' => $headers,
            'body'    => wp_json_encode([
                'role'    => 'user',
                'content' => $full_msg,
            ]),
        ]);
        if (is_wp_error($send)) {
            wp_send_json_error($send->get_error_message(), 500);
        }
        $send_body = json_decode(wp_remote_retrieve_body($send), true);
        if (isset($send_body['error'])) {
            wp_send_json_error($send_body['error']['message'] ?? 'API error', 500);
        }

        $run_payload = ['assistant_id' => $c['assistant_id']];
        if (!empty($c['developer_instructions'])) {
            $run_payload['instructions'] = $c['developer_instructions'];
        }

        $run = wp_remote_post("https://api.openai.com/v1/threads/{$thread_id}/runs", [
            'headers' => $headers,
            'body'    => wp_json_encode($run_payload),
        ]);
        if (is_wp_error($run)) {
            wp_send_json_error($run->get_error_message(), 500);
        }
        $run_body = json_decode(wp_remote_retrieve_body($run), true);
        if (isset($run_body['error'])) {
            wp_send_json_error($run_body['error']['message'] ?? 'API error', 500);
        }
        $run_id = $run_body['id'] ?? '';
        $status = $run_body['status'] ?? '';
        $tries = 0;
        while ($status && $status !== 'completed' && $tries < 30) {
            sleep(1);
            $tries++;
            $chk = wp_remote_get("https://api.openai.com/v1/threads/{$thread_id}/runs/{$run_id}", ['headers' => $headers]);
            if (is_wp_error($chk)) {
                wp_send_json_error($chk->get_error_message(), 500);
            }
            $chk_body = json_decode(wp_remote_retrieve_body($chk), true);
            if (isset($chk_body['error'])) {
                wp_send_json_error($chk_body['error']['message'] ?? 'API error', 500);
            }
            $status = $chk_body['status'] ?? '';
        }
        if ($status !== 'completed') {
            wp_send_json_error('Run no completado');
        }

        $msgs = wp_remote_get("https://api.openai.com/v1/threads/{$thread_id}/messages?order=desc&limit=1", ['headers' => $headers]);
        if (is_wp_error($msgs)) {
            wp_send_json_error($msgs->get_error_message(), 500);
        }
        $msgs_body = json_decode(wp_remote_retrieve_body($msgs), true);
        if (isset($msgs_body['error'])) {
            wp_send_json_error($msgs_body['error']['message'] ?? 'API error', 500);
        }

        $reply = '';
        if (!empty($msgs_body['data'][0]['content'][0]['text']['value'])) {
            $reply = $msgs_body['data'][0]['content'][0]['text']['value'];
        }
        if (!empty($c['debug'])) {
            $debug_lines[] = 'Respuesta: ' . $reply;
        }
        if (!$reply) {
            wp_send_json_error('No llegó respuesta del assistant');
        }

        $result = ['reply' => $reply];
        if (!empty($c['debug'])) {
            $result['debug'] = implode("\n", $debug_lines);
        }
        wp_send_json_success($result);
    }

    public function ajax_send_key() {
        check_ajax_referer('oa_assistant_send_key', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }
        $key = get_option('oa_assistant_api_key', '');
        if (!$key) {
            wp_send_json_error('No API key');
        }
        $sent = wp_mail(get_option('admin_email'), 'OpenAI Assistant API Key', 'Tu API Key: ' . $key);
        if ($sent) {
            wp_send_json_success('Email enviado');
        } else {
            wp_send_json_error('No se pudo enviar el email', 500);
        }
    }

    private function list_assistants() {
        $key = get_option('oa_assistant_api_key', '');
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

    // Placeholder: implement your vector DB retrieval logic
    private function get_vector_context($vector_store_id, $query) {
        // TODO: conectarse a tu vector store usando $vector_store_id y retornar array de fragmentos relevantes
        return [];
    }
}

new OA_Assistant_Plugin();
