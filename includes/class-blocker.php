<?php
class ScriptDetective_Blocker {
    private $disabled_scripts = [];

    public function __construct() {
        add_action('wp', [$this, 'init_blocker']);
    }

    public function init_blocker() {
        if (is_singular()) {
            global $post;
            $this->disabled_scripts = get_post_meta($post->ID, '_disabled_scripts', true) ?: [];
            
            if (!empty($this->disabled_scripts)) {
                add_action('wp_enqueue_scripts', [$this, 'block_registered_scripts'], 9999);
                
                ob_start([$this, 'filter_output_buffer']);
            }
        }
    }


    public function block_registered_scripts() {
        foreach ($this->disabled_scripts as $script) {
            wp_dequeue_script($script);
            wp_deregister_script($script);
        }
    }

    public function filter_output_buffer($html) {
        $dom = new DOMDocument();
        @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        $scripts = $dom->getElementsByTagName('script');
        $nodes_to_remove = [];

        foreach ($scripts as $script) {
            $src = $script->getAttribute('src');
            if ($this->should_block_script($src)) {
                $nodes_to_remove[] = $script;
            }
        }

        foreach ($nodes_to_remove as $node) {
            $node->parentNode->removeChild($node);
        }

        return $dom->saveHTML();
    }

    private function should_block_script($src) {
        if (empty($src)) return false;

        $clean_src = $this->normalize_src($src);
        
        foreach ($this->disabled_scripts as $disabled) {
            if (false !== strpos($clean_src, $this->normalize_src($disabled))) {
                return true;
            }
        }
        
        return false;
    }

    private function normalize_src($src) {
        $src = preg_replace('/\?.*/', '', $src); // Eliminar query strings
        $src = trim($src, '/'); // Normalizar slashes
        $src = str_replace(site_url(), '', $src); // Eliminar dominio
        
        return $src;
    }
}