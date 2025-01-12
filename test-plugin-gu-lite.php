<?php
/**
 * Plugin Name: Test Plugin Git Updater Lite
 * Plugin URI: https://github.com/afragen/test-plugin-gu-lite/
 * Description: This plugin is used for testing functionality of Github updating of plugins.
 * Version: 0.1.9
 * Author: Andy Fragen
 * License: MIT
 * Requires WP: 6.6
 * Requires PHP: 8.0
 * Update URI: afragen/test-plugin-gu-lite
 */

 /**
 * Exit if called directly.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once __DIR__ . '/vendor/afragen/git-updater-lite/Lite.php';
add_filter( 'gul_update_server', function () {
    return 'https://git-updater.com';
});
( new \Fragen\Git_Updater\Lite( __FILE__ ) )->run();
