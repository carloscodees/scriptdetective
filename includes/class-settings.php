<?php

class ScriptDetective_Settings {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_settings_page() {
        add_submenu_page(
            'tools.php',
            __('ScriptDetective Settings', 'scriptdetective'),
            __('ScriptDetective', 'scriptdetective'),
            'manage_options',
            'scriptdetective-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        // Registrar opciones con valores predeterminados
        register_setting('scriptdetective_settings', 'scriptdetective_show_metabox_posts', ['default' => '1']);
        register_setting('scriptdetective_settings', 'scriptdetective_show_metabox_pages', ['default' => '1']);
        register_setting('scriptdetective_settings', 'scriptdetective_blacklist', ['default' => '']);
        register_setting('scriptdetective_settings', 'scriptdetective_blacklist_posts', ['default' => '']);

        // Sección principal
        add_settings_section(
            'scriptdetective_main_section',
            __('General Settings', 'scriptdetective'),
            function () {
                echo '<p>' . esc_html__('Configure the behavior of ScriptDetective', 'scriptdetective') . '</p>';
            },
            'scriptdetective-settings'
        );

        // Opción: Mostrar meta box en posts
        add_settings_field(
            'scriptdetective_show_metabox_posts',
            __('Show Meta Box in Posts', 'scriptdetective'),
            array($this, 'metabox_posts_callback'),
            'scriptdetective-settings',
            'scriptdetective_main_section'
        );

        // Opción: Mostrar meta box en páginas
        add_settings_field(
            'scriptdetective_show_metabox_pages',
            __('Show Meta Box in Pages', 'scriptdetective'),
            array($this, 'metabox_pages_callback'),
            'scriptdetective-settings',
            'scriptdetective_main_section'
        );

        // Opción: Blacklist de scripts
        add_settings_field(
            'scriptdetective_blacklist',
            __('Blacklist Scripts', 'scriptdetective'),
            array($this, 'blacklist_scripts_callback'),
            'scriptdetective-settings',
            'scriptdetective_main_section'
        );

        // Opción: Blacklist por posts/páginas
        add_settings_field(
            'scriptdetective_blacklist_posts',
            __('Blacklist for Specific Posts/Pages', 'scriptdetective'),
            array($this, 'blacklist_posts_callback'),
            'scriptdetective-settings',
            'scriptdetective_main_section'
        );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('ScriptDetective Settings', 'scriptdetective'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('scriptdetective_settings');
                do_settings_sections('scriptdetective-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function metabox_posts_callback() {
        $option = get_option('scriptdetective_show_metabox_posts', '1');
        ?>
        <input type="checkbox" name="scriptdetective_show_metabox_posts" value="1" <?php checked($option, '1'); ?> />
        <label for="scriptdetective_show_metabox_posts"><?php esc_html_e('Enable for Posts', 'scriptdetective'); ?></label>
        <?php
    }

    public function metabox_pages_callback() {
        $option = get_option('scriptdetective_show_metabox_pages', '1');
        ?>
        <input type="checkbox" name="scriptdetective_show_metabox_pages" value="1" <?php checked($option, '1'); ?> />
        <label for="scriptdetective_show_metabox_pages"><?php esc_html_e('Enable for Pages', 'scriptdetective'); ?></label>
        <?php
    }

    public function blacklist_scripts_callback() {
        $option = get_option('scriptdetective_blacklist', '');
        ?>
        <textarea name="scriptdetective_blacklist" rows="5" cols="50"><?php echo esc_textarea($option); ?></textarea>
        <p class="description"><?php esc_html_e('Enter script handles to blacklist, one per line.', 'scriptdetective'); ?></p>
        <?php
    }

    public function blacklist_posts_callback() {
        $option = get_option('scriptdetective_blacklist_posts', '');
        ?>
        <textarea name="scriptdetective_blacklist_posts" rows="5" cols="50"><?php echo esc_textarea($option); ?></textarea>
        <p class="description"><?php esc_html_e('Enter post/page IDs where the blacklist should apply, separated by commas.', 'scriptdetective'); ?></p>
        <?php
    }
}
