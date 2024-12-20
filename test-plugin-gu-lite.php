<?php
/**
 * Plugin Name: Test Plugin Git Updater Lite
 * Plugin URI: https://github.com/afragen/test-plugin-gu-lite/
 * Description: This plugin is used for testing functionality of Github updating of plugins.
 * Version: 0.1.3
 * Author: Andy Fragen
 * License: MIT
 * GitHub Plugin URI: https://github.com/afragen/test-plugin-gu-lite/
 * Primary Branch: main
 * Requires WP: 6.6
 * Requires PHP: 7.4
 */

 /**
 * Exit if called directly.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_filter( 'gul_update_server', function () {
    return 'https://thefragens.net';
});
require_once __DIR__ . '/vendor/afragen/git-updater-lite/Lite.php';
( new \Fragen\Git_Updater\Lite( __FILE__ ) )->run();
