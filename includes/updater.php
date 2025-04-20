<?php
$path = dirname( __FILE__, 2 ) . '/plugin-update-checker/plugin-update-checker.php';
if ( file_exists( $path ) ) {
    require_once $path;
    if ( class_exists( '\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
        \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            'https://wpforged.com/updates/check-update.php?plugin=forged-snippets',
            dirname( __FILE__, 2 ) . '/forged-snippets.php',
            'wp-forged-snippets'
        );
    } else {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>Forged Snippets:</strong> Update checker class not found.</p></div>';
        } );
    }
} else {
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>Forged Snippets:</strong> Update checker missing.</p></div>';
    } );
}
