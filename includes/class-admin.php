<?php
if (!defined('ABSPATH')) {
    exit;
}

class ScriptDetective_Admin {
    
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_scriptdetective_scan', [$this, 'ajax_scan_page']);
        add_action('wp_ajax_scriptdetective_toggle_script', [$this, 'ajax_toggle_script']);
    }

    public function add_meta_box() {
        $post_types = get_post_types(['public' => true]);
        
        add_meta_box(
            'scriptdetective_metabox',
            'Script Detective - Script Analyzer',
            [$this, 'render_metabox'],
            $post_types,
            'normal',
            'high'
        );
    }
    public function ajax_toggle_script() {
        check_ajax_referer('scriptdetective_nonce', 'security');
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $script = isset($_POST['script']) ? sanitize_text_field($_POST['script']) : '';
        $action = isset($_POST['action_type']) ? $_POST['action_type'] : 'disable';
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'Not type';
        $version = isset($_POST['version']) ? sanitize_text_field($_POST['version']) : 'version';
    
        if (!$post_id || !current_user_can('edit_post', $post_id) || empty($script)) {
            wp_send_json_error('Invalid request');
        }
    
        $disabled_scripts = get_post_meta($post_id, '_disabled_scripts', true);
        $disabled_scripts = is_array($disabled_scripts) ? $disabled_scripts : [];
    
        if ($action === 'disable') {
            $disabled_scripts[$script] = [
                'type' => $type,
                'version' => $version
            ];
        } else {
            unset($disabled_scripts[$script]);
        }
    
        update_post_meta($post_id, '_disabled_scripts', $disabled_scripts);
    
        wp_send_json_success([
            'new_state' => $action === 'disable' ? 'disabled' : 'enabled',
            'disabled_count' => count($disabled_scripts)
        ]);
    }
    


    public function render_metabox($post) {
        ?>
        <div id="scriptdetective-metabox">
            <div class="scriptdetective-controls">
                <button id="scriptdetective-scan-btn" class="button button-primary">
                    <?php esc_html_e('Full Page Scan', 'scriptdetective'); ?>
                </button>
                <span class="description" style="margin-left: 10px;">
                    Scans both WordPress-registered and external scripts
                </span>
            </div>
            
            <div id="scriptdetective-results" style="margin-top: 20px;"></div>
            
            <div id="scriptdetective-loading" style="display: none;">
                <span class="spinner is-active"></span>
                <?php esc_html_e('Deep scanning page...', 'scriptdetective'); ?>
            </div>
        </div>
        <?php
    }

    public function ajax_scan_page() {
        check_ajax_referer('scriptdetective_nonce', 'security');
        
        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Invalid permissions');
        }
        
        $scripts = $this->get_page_scripts($post_id);
        wp_send_json_success($scripts);
    }

    private function get_page_scripts($post_id) {
        $disabled_scripts = get_post_meta($post_id, '_disabled_scripts', true) ?: [];
        $current_scripts = $this->get_current_scripts($post_id, $disabled_scripts);
        
        $all_scripts = $this->merge_disabled_scripts($current_scripts, $disabled_scripts);
    
        return $all_scripts;
       
    }
private function get_current_scripts($post_id, $disabled_scripts) {

    $scripts = [];
    $post_url = get_permalink($post_id);
    
    global $wp_scripts;
    foreach ($wp_scripts->registered as $handle => $script) {
        $size = $this->get_script_size($script->src);
        $normalized_src = $this->normalize_src($script->src);
        
        $is_disabled = isset($disabled_scripts[$handle]) || isset($disabled_scripts[$normalized_src]);
        $script_data = $disabled_scripts[$handle] ?? $disabled_scripts[$normalized_src] ?? [];

        $scripts[] = [
            'type' => 'wordpress',
            'handle' => $handle,
            'src' => $normalized_src,
            'deps' => $script->deps,
            'version' => $script->ver,
            'in_footer' => $script->args,
            'size' => $size,
            'disabled' => $is_disabled,
            'disabled_type' => $script_data['type'] ?? 'N/A',
            'disabled_version' => $script_data['version'] ?? 'N/A',
        ];
    }

    $html = $this->fetch_page_html($post_url);
    $dom_scripts = $this->parse_html_scripts($html);
    
    foreach ($dom_scripts as $script_data) {
        $normalized_src = $this->normalize_src($script_data['src']);
        $size = $this->get_script_size($script_data['src']);
        $existing = false;

        foreach ($scripts as $script) {
            if ($script['src'] === $normalized_src) {
                $existing = true;
                break;
            }
        }
        
        if (!$existing) {
            // Verificar si el script está deshabilitado
            $is_disabled = isset($disabled_scripts[$normalized_src]);
            $script_disabled_data = $disabled_scripts[$normalized_src] ?? [];

            $scripts[] = [
                'type' => 'external',
                'handle' => '',
                'src' => $normalized_src,
                'version' => $script_data['version'],
                'deps' => [],
                'in_footer' => false,
                'size' => $size,
                'disabled' => $is_disabled,
                'disabled_type' => $script_disabled_data['type'] ?? 'N/A',
                'disabled_version' => $script_disabled_data['version'] ?? 'N/A',
            ];
        }
    }

    return $scripts;
}

    private function merge_disabled_scripts($current_scripts, $disabled_scripts) {

        $current_scripts_map = [];
        foreach ($current_scripts as $script) {
            $key = $script['handle'] ?: $script['src'];
            $current_scripts_map[$key] = $script;
        }
    
        foreach ($disabled_scripts as $script_name => $script_data) {
            if (!isset($current_scripts_map[$script_name])) {
                $current_scripts[] = [
                    'type' => isset($script_data['type']) ? $script_data['type'] : 'external',
                    'handle' => strpos($script_name, 'http') === 0 ? '' : $script_name,
                    'src' => strpos($script_name, 'http') === 0 ? $script_name : '',
                    'version' => isset($script_data['version']) ? $script_data['version'] : 'N/A',
                    'size' => 0,
                    'disabled' => false,
                    'missing' => false
                ];
            }
        }
    
        return $current_scripts;
    }
    
    private function normalize_src($src) {
        $src = preg_replace('/\?.*/', '', $src); // Eliminar todos los parámetros
        $src = trim(str_replace(site_url(), '', $src), '/');
        return $src;
    }


    private function script_exists($scripts, $src) {
        foreach ($scripts as $script) {
            if ($script['src'] === $src) {
                return true;
            }
        }
        return false;
    }

    private function fetch_page_html($url) {
        $args = [
            'sslverify' => false,
            'timeout' => 30,
            'cookies' => $_COOKIE,
            'redirection' => 0 // Evitar redirecciones
        ];
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            error_log('ScriptDetective fetch error: ' . $response->get_error_message());
            return false;
        }
        
        return wp_remote_retrieve_body($response);
    }

    private function parse_html_scripts($html) {
        $scripts = [];
        
        if (empty($html)) return $scripts;
    
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        
        $script_tags = $dom->getElementsByTagName('script');
        
        foreach ($script_tags as $tag) {
            if ($tag->hasAttribute('src')) {
                $src = $tag->getAttribute('src');
                
                if (!empty($src) && false === strpos($src, 'data:')) {
                    // Extraer versión del parámetro ver=
                    $parsed_url = parse_url($src);
                    $version = '';
                    
                    if (isset($parsed_url['query'])) {
                        parse_str($parsed_url['query'], $query_params);
                        $version = $query_params['ver'] ?? '';
                    }
    
                    $scripts[] = [
                        'src' => $src,
                        'version' => $version
                    ];
                }
            }
        }
        
        return $scripts;
    }

    public function enqueue_admin_assets($hook) {
        global $post;
        
        if (!in_array($hook, ['post.php', 'post-new.php']) || !$post) {
            return;
        }

        wp_enqueue_style(
            'scriptdetective-admin-css',
            SCRIPTDETECTIVE_ASSETS_URL . 'css/admin.css',
            [],
            SCRIPTDETECTIVE_VERSION
        );

        wp_enqueue_script(
            'scriptdetective-admin-js',
            SCRIPTDETECTIVE_ASSETS_URL . 'js/admin.js',
            ['jquery', 'wp-util'],
            SCRIPTDETECTIVE_VERSION,
            true
        );

        // Dentro de enqueue_admin_assets()
        wp_localize_script('scriptdetective-admin-js', 'scriptdetective', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scriptdetective_nonce'),
            'post_id' => $post->ID,
            'formatSize' => function($size) {
                return size_format($size);
            },
            'labels' => [
                'detected_scripts' => __('Detected Scripts', 'scriptdetective'),
                'wordpress' => __('WordPress', 'scriptdetective'),
                'external' => __('External', 'scriptdetective'),
                'anonymous' => __('Anonymous', 'scriptdetective'),
                'dependencies' => __('Dependencies', 'scriptdetective'),
                'version' => __('Version', 'scriptdetective'),
                'in_footer' => __('In Footer', 'scriptdetective'),
                'no_scripts' => __('No scripts found', 'scriptdetective'),
                'error' => __('Scan failed', 'scriptdetective'),
                'size' => __('Size', 'scriptdetective'),
                'missing_warning' => __('Script no longer exists on this page', 'scriptdetective'),
            ]
        ]);
    }

    private function get_script_size($src) {

        if (strpos($src, 'http') === 0 && strpos($src, site_url()) === false) {
            $response = wp_remote_head($src);
            $size = wp_remote_retrieve_header($response, 'content-length');
            return $size ? (int)$size : 0;
        }
        
        $file_path = ABSPATH . ltrim(str_replace(site_url(), '', $src), '/');
        
        if (file_exists($file_path)) {
            return filesize($file_path);
        }
        
        return 0;
    }
    
    private function format_size($bytes) {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        return round($bytes / 1024, 2) . ' KB';
    }
}