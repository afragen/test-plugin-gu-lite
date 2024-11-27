<?php
/**
 * Plugin Name: Test Plugin Git Updater Lite
 * Plugin URI: https://github.com/afragen/test-plugin-gu-lite/
 * Description: This plugin is used for testing functionality of Github updating of plugins.
 * Version: 0.1.0
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

 require_once __DIR__ . '/vendor/autoload.php';
( new \Fragen\Git_Updater\Lite( __FILE__ ) )
    ->run( 'https://thefragens.net/wp-json/git-updater/v1/plugins-api/?slug=test-plugin-gu-lite' );
