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
		protected $file;

		/** @var string */
		protected $local_version;

		/** @var \stdClass */
		protected $api_data;

		/**
		 * Constructor.
		 */
		public function __construct( string $file_path ) {
			if ( \str_contains( $file_path, 'functions.php' ) ) {
				$file_path  = dirname( $file_path ) . '/style.css';
				$this->file = \basename( dirname( $file_path ) );
			} else {
				$this->file = basename( dirname( $file_path ) ) . '/' . basename( $file_path );
			}

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
			$response = get_site_transient( "git-updater-lite_{$this->file}" );
			if ( ! $response ) {
				$response = wp_remote_get( $url );
				if ( is_wp_error( $response ) ) {
					return;
				}
				set_site_transient( "git-updater-lite_{$this->file}", $response, 6 * \HOUR_IN_SECONDS );
			}

			$this->api_data       = json_decode( wp_remote_retrieve_body( $response ) );
			$this->api_data->file = $this->file;
			$type                 = $this->api_data->type;

			add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection' ), 10, 4 );
			add_filter( "{$type}s_api", array( $this, 'repo_api_details' ), 99, 3 );
			add_filter( "site_transient_update_{$type}s", array( $this, 'update_site_transient' ), 15, 1 );
		}

		/**
		 * Correctly rename dependency for activation.
		 *
		 * @param string                           $source        Path fo $source.
		 * @param string                           $remote_source Path of $remote_source.
		 * @param \Plugin_Upgrader|\Theme_Upgrader $upgrader      An Upgrader object.
		 * @param array                            $hook_extra    Array of hook data.
		 *
		 * @return string $new_source
		 */
		public function upgrader_source_selection( string $source, string $remote_source, \Plugin_Upgrader|\Theme_Upgrader $upgrader, $hook_extra = null ) {
			global $wp_filesystem;

			// Exit if installing.
			if ( isset( $hook_extra['action'] ) && 'install' === $hook_extra['action'] ) {
				return $source;
			}

			// Rename plugins.
			if ( $upgrader instanceof \Plugin_Upgrader ) {
				if ( isset( $hook_extra['plugin'] ) ) {
					$slug       = dirname( $hook_extra['plugin'] );
					$new_source = trailingslashit( $remote_source ) . $slug;
				}
			}

			// Rename themes.
			if ( $upgrader instanceof \Theme_Upgrader ) {
				if ( isset( $hook_extra['theme'] ) ) {
					$slug       = $hook_extra['theme'];
					$new_source = trailingslashit( $remote_source ) . $slug;
				}
			}

			if ( trailingslashit( strtolower( $source ) ) !== trailingslashit( strtolower( $new_source ) ) ) {
				$wp_filesystem->move( $source, $new_source, true );
			}

			return trailingslashit( $new_source );
		}

		/**
		 * Put changelog in plugins_api, return WP.org data as appropriate
		 *
		 * @param bool      $result   Default false.
		 * @param string    $action   The type of information being requested from the Plugin Installation API.
		 * @param \stdClass $response Repo API arguments.
		 *
		 * @return \stdClass|bool
		 */
		public function repo_api_details( $result, string $action, \stdClass $response ) {
			if ( "{$this->api_data->type}_information" !== $action ) {
				return $result;
			}

			// Exit if not our repo.
			if ( $response->slug !== $this->api_data->slug ) {
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
				$response                                     = array_merge( $response, $response_api_checked );
				$response                                     = 'plugin' === $this->api_data->type ? (object) $response : $response;
				$transient->response[ $this->api_data->file ] = $response;
			} else {
				$response = 'plugin' === $this->api_data->type ? (object) $response : $response;

				// Add repo without update to $transient->no_update for 'View details' link.
				$transient->no_update[ $this->api_data->file ] = $response;
			}

			return $transient;
		}
	}
}
