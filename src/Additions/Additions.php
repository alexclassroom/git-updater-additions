<?php
/**
 * GitHub Updater Additions
 *
 * @author    Andy Fragen
 * @license   MIT
 * @link      https://github.com/afragen/github-updater-additions
 * @package   github-updater-additions
 */

namespace Fragen\GitHub_Updater\Additions;

use Fragen\Singleton;

/**
 * Class Additions
 *
 * Add repos without required headers to GitHub Updater.
 * Uses JSON config data file and companion plugin.
 *
 * @uses \Fragen\Singleton
 */
class Additions {
	/**
	 * Holds array of plugin/theme headers to add to GitHub Updater.
	 *
	 * @access public
	 * @var array
	 */
	public $add_to_github_updater;

	/**
	 * Register JSON config file.
	 *
	 * @access public
	 *
	 * @param string $config The repo config.
	 * @param array  $repos  The repos to pull from.
	 * @param string $type   The plugin type ('plugin' or 'theme').
	 *
	 * @return bool
	 */
	public function register( $config, $repos, $type ) {
		if ( empty( $config ) ) {
			return false;
		}

		$this->add_headers( $config, $repos, $type );

		return true;
	}

	/**
	 * Add GitHub Updater headers to plugins/themes via a filter hooks.
	 *
	 * @access public
	 * @uses   \Fragen\GitHub_Updater\Additions::add_to_github_updater()
	 *
	 * @param array  $config The repo config.
	 * @param array  $repos  The repos to pull from.
	 * @param string $type   The plugin type ('plugin' or 'theme').
	 *
	 * @return void
	 */
	public function add_headers( $config, $repos, $type ) {
		foreach ( $config as $repo ) {
			$addition  = [];
			$additions = [];

			$type      = explode( '_', $repo['type'] )[1];
			$file_path = 'plugin' === $type ? WP_PLUGIN_DIR . "/{$repo['slug']}" : null;
			$file_path = 'theme' === $type ? get_theme_root() . "/{$repo['slug']}/style.css" : $file_path;

			if ( ! file_exists( $file_path ) ) {
				continue;
			}

			$all_headers = Singleton::get_instance( 'Base', $this )->get_headers( $type );

			$additions[ $repo['slug'] ]['type'] = $type;
			$additions[ $repo['slug'] ]         = get_file_data( $file_path, $all_headers );

			switch ( $repo['type'] ) {
				case 'github_plugin':
				case 'github_theme':
					$addition[ 'GitHub' . ucwords( $type ) . 'URI' ] = $repo['uri'];
					break;
				case 'bitbucket_plugin':
				case 'bitbucket_theme':
					$addition[ 'Bitbucket' . ucwords( $type ) . 'URI' ] = $repo['uri'];
					break;
				case 'gitlab_plugin':
				case 'gitlab_theme':
					$addition[ 'GitLab' . ucwords( $type ) . 'URI' ] = $repo['uri'];
					break;
				case 'gitea_plugin':
				case 'gitea_theme':
					$addition[ 'Gitea' . ucwords( $type ) . 'URI' ] = $repo['uri'];
					break;
			}

			$this->add_to_github_updater[ $repo['slug'] ] = array_merge( $additions[ $repo['slug'] ], $addition );
		}
	}
}