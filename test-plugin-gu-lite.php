<?php
/**
 * Plugin Name: Test Plugin Git Updater Lite
 * Plugin URI: https://github.com/afragen/test-plugin-gu-lite/
 * Description: This plugin is used for testing functionality of Github updating of plugins.
 * Version: 0.2.6
 * Author: Andy Fragen
 * License: MIT
 * Requires WP: 6.6
 * Requires PHP: 7.4
 * Update URI: https://git-updater.com
 */

 /**
 * Exit if called directly.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_filter( 'git_updater_lite_transient_timeout', fn() => 12 * HOUR_IN_SECONDS );
require_once __DIR__ . '/vendor/afragen/git-updater-lite/Lite.php';
( new \Fragen\Git_Updater\Lite( __FILE__ ) )->run();
