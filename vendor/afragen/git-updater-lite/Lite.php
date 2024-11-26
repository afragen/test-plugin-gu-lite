<?php
/*
 * @author   Andy Fragen
 * @license  MIT
 * @link     https://github.com/afragen/git-updater-lite
 * @package  git-updater-lite
 */

namespace Fragen\Git_Updater;

/**
 * Exit if called directly.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Fragen\\Git_Updater\\Lite' ) ) {
	/**
	 * Class Lite
	 */
	class Lite {

		/** @var string */
		public $file;

		/** @var string */
		public $local_version;

		/** @var \stdClass */
		public $api_data;

		/**
		 * Constructor.
		 */
		public function __construct( string $file_path ) {
			$this->file          = basename( dirname( $file_path ) ) . '/' . basename( $file_path );
			$this->local_version = get_file_data( $file_path, array( 'Version' => 'Version' ) )['Version'];
		}

		/**
		 * Get API data and load hooks.
		 *
		 * @param string $url URI to JSON response of API data.
		 *
		 * @return void
		 */
		public function run( string $url ) {
			$response = wp_remote_get( $url );
			if ( is_wp_error( $response ) ) {
				return;
			}
			$this->api_data       = json_decode( wp_remote_retrieve_body( $response ) );
			$this->api_data->file = $this->file;
			$type                 = $this->api_data->type;

			add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection' ), 10, 2 );
			add_filter( "{$type}s_api", array( $this, 'repo_api_details' ), 99, 2 );
			add_filter( "site_transient_update_{$type}s", array( $this, 'update_site_transient' ), 15, 1 );
		}

		/**
		 * Correctly rename dependency for activation.
		 *
		 * @param string $source        Path fo $source.
		 * @param string $remote_source Path of $remote_source.
		 *
		 * @return string $new_source
		 */
		public function upgrader_source_selection( string $source, string $remote_source ) {
			global $wp_filesystem;

			$new_source = trailingslashit( $remote_source ) . $this->api_data->slug;
			$wp_filesystem->move( $source, $new_source, true );

			return trailingslashit( $new_source );
		}

		/**
		 * Put changelog in plugins_api, return WP.org data as appropriate
		 *
		 * @param bool   $result   Default false.
		 * @param string $action   The type of information being requested from the Plugin Installation API.
		 *
		 * @return \stdClass
		 */
		public function repo_api_details( $result, $action ) {
			if ( ! ( 'plugin_information' === $action ) ) {
				return $result;
			}
			$this->api_data->sections = (array) $this->api_data->sections;

			return $this->api_data;
		}

		/**
		 * Hook into site_transient_update_plugins to update from GitHub.
		 *
		 * @param \stdClass $transient Plugin update transient.
		 *
		 * @return \stdClass
		 */
		public function update_site_transient( $transient ) {
			// needed to fix PHP 7.4 warning.
			if ( ! \is_object( $transient ) ) {
				$transient = new \stdClass();
			}

			$response = array(
				'slug'                => $this->api_data->slug,
				$this->api_data->type => $this->api_data->file,
				'icons'               => (array) $this->api_data->icons,
				'banners'             => $this->api_data->banners,
				'branch'              => $this->api_data->branch,
				'type'                => "{$this->api_data->git}-{$this->api_data->type}",
				'update-supported'    => true,
				'requires'            => $this->api_data->requires,
				'requires_php'        => $this->api_data->requires_php,
			);

			if ( version_compare( $this->api_data->version, $this->local_version, '>' ) ) {
				$response_api_checked                         = array(
					'new_version'  => $this->api_data->version,
					'package'      => $this->api_data->download_link,
					'tested'       => $this->api_data->tested,
					'requires'     => $this->api_data->requires,
					'requires_php' => $this->api_data->requires_php,
				);
				$transient->response[ $this->api_data->file ] = (object) array_merge( $response, $response_api_checked );
			} else {
				// Add repo without update to $transient->no_update for 'View details' link.
				$transient->no_update[ $this->api_data->file ] = (object) $response;
			}

			return $transient;
		}
	}
}
