<?php
class ScriptDetective_Core {
    public function run() {
        // Inicializa hooks y dependencias
        if (is_admin()) {
            require_once SCRIPTDETECTIVE_PATH . 'includes/class-admin.php';
            new ScriptDetective_Admin();
        }

        // Incluye funcionalidad de escaneo
        require_once SCRIPTDETECTIVE_PATH . 'includes/class-scanner.php';
        // new ScriptDetective_Scanner();

        require_once SCRIPTDETECTIVE_PATH . 'includes/class-blocker.php';
        new ScriptDetective_Blocker();

        // Cargar configuración sin instanciar múltiples veces
        require_once SCRIPTDETECTIVE_PATH . 'includes/class-settings.php';
        ScriptDetective_Settings::get_instance();
    }
}
