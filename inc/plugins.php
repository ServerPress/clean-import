<?php

/**
 * Operations specific to handling Plugins during the Clean Import process.
 */

class DS_Clean_Import_Plugins extends DS_Clean_Import_Base
{
	// non-optional: these plugins will always be disabled due to issues caused by them
	private $_non_optional = array(
		'advanced-cache',									// WP Caching
		'all-in-one-wp-security-and-firewall',
		'broken-link-checker',								// WPEngine
		'cdn-enabler',
		'dynamic-related-posts',							// WPEngine
		'gd-system-plugin',									// WPEngine
		'gd-system-plugin.php',								// WPEngine
		'ithemes-security-pro',
		'iwp-client',										// Infinite WP
		'managewp',
		'portable-phpmyadmin',								// WPEngine
		'quick-cache',										// WPEngine
		'quick-cache-pro',									// WPEngine
		'sg-cachepress',									// SiteGround's CachePress
		'stream',
		'sucuri-scanner',
		'versionpress',										// WPEngine
		'w3-total-cache',									// WPEngine
		'wordfence',										// WPEngine
		'wp-cache',											// WPEngine
		'wp-engine-snapshot',								// WPEngine
		'wp-fastest-cache',
		'wp-file-cache',									// WPEngine
		'wp-phpmyadmin',									// WPEngine
		'wp-rocket',
		'wp-super-cache',									// WPEngine
		'litespeed-cache',									// LiteSPeed Cache
	);
	// optional: these plugins can be configured by the user to be disabled or not
	private $_optional = array(
		// WP File Cache
		'adminer',											// WPEngine
		'async-google-analytics',							// WPEngine
		'backup',											// WPEngine
		'backupbuddy',
		'backup-scheduler',									// WPEngine
		'backupwordpress',									// WPEngine
		'backwpup',											// WPEngine
		'bad-behavior',										// WPEngine
		'content-molecules',								// WPEngine
		'contextual-related-posts',							// WPEngine
		'duplicator',										// WPEngine
		'ewww-image-optimizer',								// WPEngine
		'ezpz-one-click-backup',							// WPEngine
		'file-commander',									// WPEngine
		'fuzzy-seo-booster',								// WPEngine
		'google-xml-sitemaps-with-multisite-support',		// WPEngine
		'hc-custom-wp-admin-url',							// WPEngine
		'hcs.php',											// WPEngine
		'hello.php',										// WPEngine
		'jetpack',
		'jr-referrer',										// WPEngine
		'jumpple',											// WPEngine
		'missed-schedule',									// WPEngine
		'no-revisions',										// WPEngine
		'nrelate-related-content',
		'ozh-who-sees-ads',									// WPEngine
		'recommend-a-friend',								// WPEngine
		'seo-alrp',											// WPEngine
		'si-captcha-for-wordpress',							// WPEngine
		'similar-posts',									// WPEngine
		'spamreferrerblock',								// WPEngine
		'ssclassic',										// WPEngine
		'sspro',											// WPEngine
		'super-post',										// WPEngine
		'superslider',										// WPEngine
		'sweetcaptcha-revolutionary-free-captcha-service',	// WPEngine
		'text-passwords',									// WPEngine
		'the-codetree-backup',								// WPEngine
		'toolspack',										// WPEngine
		'ToolsPack',										// WPEngine
		'tweet-blender',									// WPEngine
		'wordpress-gzip-compression',						// WPEngine
		'wp-database-optimizer',							// WPEngine
		'wp-db-backup',										// WPEngine
		'wp-dbmanager',										// WPEngine
		'wp-mailinglist',									// WPEngine
		'wp-postviews',										// WPEngine
		'wp-slimstat',										// WPEngine
		'wp-symposium-alerts',								// WPEngine
		'wpengine-migrate',									// WPEngine
		'wpengine-migrate.tar.gz',							// WPEngine
		'wpengine-migrate.zip',								// WPEngine
		'wpengine-snapshot',								// WPEngine
		'wpengine-snapshot.tar.gz',							// WPEngine
		'wponlinebackup',									// WPEngine
		'yet-another-featured-posts-plugin',				// WPEngine
		'yet-another-related-posts-plugin',					// WPEngine
	);

	// the following are files that are dependent upon or related to items in the above lists
	private $_dependents = array(
		'backupbuddy' => 'wp-content/backup-db',
		'iwp-client' => 'wp-content/infinitewp',
		'managewp' => 'wp-content/mu-plugins/0-worker.php',
		'advanced-cache' => 'wp-content/advanced-cache.php',
		'advanced-cache' => 'wp-content/object-cache.php',
		'litespeed-cache' => 'wp-content/advanced-cache.php',
		'wp-fastest-cache' => 'wp-content/cache',
		'wp-super-cache' => 'wp-content/wp-cache-config.php',
		'w3-total-cache' => 'wp-content/w3tc-config',
		'wp-rocket' => 'wp-content/wp-rocket-config',
	);

	/**
	 * returns a list of files that need to be renamed during the clean import process
	 * @return array The list of files, with the install path prefixed on all of them
	 */
	private function _get_files()
	{
		$files = array();

		foreach ( $this->_non_optional as $plugin_slug ) {
			$files[] = $this->install_path . 'wp-content/plugins/' . $plugin_slug;
			if ( isset( $this->_dependents[$plugin_slug] ) ) {
				// use foreach() to allow for multiple entries in the $_dependents list
				foreach ( $this->_dependents as $plugin => $file ) {
					if ( $plugin === $plugin_slug )
						$files[] = $this->install_path . $file;
				}
			}
		}

		// load options from config file
		require_once( dirname( __FILE__ ) . '/options.php' );
		$options = new DS_Clean_Import_Options();
		$disable = $options->get( 'disable_plugins', array() );

		foreach ( $this->_optional as $plugin_slug ) {
			// check to see if the plugin is disabled....

			// if the plugin slug exists and is 'checked' (a '1' value), include it in the list
			// or, if the plugin slug doesn't exist - indicating that the configuration settings have not be saved so use the default behavior
			if ( ( isset( $disable[$plugin_slug] ) && '1' === $disable[$plugin_slug] ) ||
				! isset( $disable[$plugin_slug] ) ) {
				$files[] = $this->install_path . 'wp-content/plugins/' . $plugin_slug;
				if ( isset( $this->_dependents[$plugin_slug] ) ) {
					// use foreach() to allow for multiple entries in the $_dependents list
					foreach ( $this->_dependents as $plugin => $file ) {
						if ( $plugin === $plugin_slug )
							$files[] = $this->install_path . $file;
					}
				}
			}
		}
		return $files;
	}

	/**
	 * Gets the list of optional plugins that the user can selectively rename/disable
	 * @return array And array containing the names of the configurable plugins
	 */
	public function get_optional_plugins()
	{
		return $this->_optional;
	}

	/**
	 * Gets the list of non-optional plugins that are always rename/disable
	 * @return array And array containing the names of the non-optional plugins
	 */
	public function get_non_optional_plugins()
	{
		return $this->_non_optional;
	}

	/**
	 * Perform Clean Import process to clean up / disable plugins
	 */
	public function clean_import()
	{
DS_Clean_Import::debug(__METHOD__.'()');
		// rename cache, security files and folders
		$files_to_rename = $this->_get_files();

		$this->rename_files( $files_to_rename );
	}
}
