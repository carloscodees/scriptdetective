<?php
/**
 * Plugin Name: ScriptDetective
 * Description: Scans pages for loaded scripts and allows selective disabling/enabling. Improve performance and control.
 * Plugin URI: https://tusitio.com/scriptdetective
 * Author: CarlosCodees
 * Version: 1.0.0
 * Author URI: https://tusitio.com
 * Text Domain: scriptdetective
 * License: GPL-3.0+
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 */

if (!defined('ABSPATH')) {
    exit; 
}

define('SCRIPTDETECTIVE_VERSION', '1.0.0');
define('SCRIPTDETECTIVE__FILE__', __FILE__);
define('SCRIPTDETECTIVE_PATH', plugin_dir_path(SCRIPTDETECTIVE__FILE__));
define('SCRIPTDETECTIVE_URL', plugins_url('/', SCRIPTDETECTIVE__FILE__));
define('SCRIPTDETECTIVE_ASSETS_URL', SCRIPTDETECTIVE_URL . 'assets/');

if (!version_compare(PHP_VERSION, '7.4', '>=')) {
    add_action('admin_notices', 'scriptdetective_fail_php_version');
} elseif (!version_compare(get_bloginfo('version'), '6.0', '>=')) {
    add_action('admin_notices', 'scriptdetective_fail_wp_version');
} else {
    require_once SCRIPTDETECTIVE_PATH . 'includes/class-scriptdetective.php';
}

/**
 * Incompatible PHP version notification
 */
function scriptdetective_fail_php_version() {
    $message = sprintf(
        esc_html__('ScriptDetective requiere PHP %1$s o superior. Tu versión actual es %2$s.', 'scriptdetective'),
        '7.4',
        PHP_VERSION
    );
    printf('<div class="error"><p>%s</p></div>', $message);
}

/**
 * Notification de version incompatible de WordPress
 */
function scriptdetective_fail_wp_version() {
    $message = sprintf(
        esc_html__('ScriptDetective requiere WordPress %1$s o superior. Tu versión actual es %2$s.', 'scriptdetective'),
        '6.0',
        get_bloginfo('version')
    );
    printf('<div class="error"><p>%s</p></div>', $message);
}

/**
 * init
 */
function scriptdetective_init() {
    $plugin = new ScriptDetective_Core();
    $plugin->run();
}
add_action('plugins_loaded', 'scriptdetective_init');