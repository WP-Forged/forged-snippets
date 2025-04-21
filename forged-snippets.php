<?php
/*
Plugin Name: Forged Snippets
Plugin URI:  https://wpforged.com/updates/check-update.php
Update URI:  https://wpforged.com/updates/check-update.php
Description: Manage code snippets with individual settings, bulk actions, a code editor preview and version rollback.
Version:     1.3
Author:      WPForged
Text Domain: forged-snippets
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SF_OPTION',               'sf_snippets' );
define( 'FORGED_SNIPPETS_VERSION', '1.2.1' );

add_action( 'init', function() {
    load_plugin_textdomain(
        'forged-snippets',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );
} );

$updater = __DIR__ . '/plugin-update-checker/plugin-update-checker.php';
if ( file_exists( $updater ) ) {
    require_once $updater;
}

foreach ( [
    'includes/updater.php',
    'includes/scripts.php',
    'includes/admin.php',
    'includes/ajax.php',
    'includes/output.php',
] as $file ) {
    $path = __DIR__ . '/' . $file;
    if ( file_exists( $path ) ) {
        require_once $path;
    }
}

add_action( 'admin_enqueue_scripts', function( $hook ) {
    if ( false === strpos( $hook, 'wpforged_snippets' ) ) {
        return;
    }
    wp_enqueue_style(
        'sf-admin',
        plugins_url( 'assets/admin.css', __FILE__ ),
        [],
        FORGED_SNIPPETS_VERSION
    );
    wp_enqueue_script(
        'sf-admin',
        plugins_url( 'assets/admin.js',  __FILE__ ),
        [ 'jquery', 'wp-code-editor' ],
        FORGED_SNIPPETS_VERSION,
        true
    );
} );

register_activation_hook( __FILE__, function() {
    if ( false === get_option( SF_OPTION ) ) {
        update_option( SF_OPTION, [] );
    }
} );
