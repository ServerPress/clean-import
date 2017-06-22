<?php

/**
 * Implements behaviors specific to importing MultiSite sites.
 */

class DS_Clean_Import_MultiSite extends DS_Clean_Import_Base
{
	public function clean_import()
	{
DS_Clean_Import::debug(__METHOD__.'()');
		// collect Multisite configuration data
		$wp_allow_multisite = $this->config_file->get_key( 'WP_ALLOW_MULTISITE' );
		$subdomain_install = $this->config_file->get_key( 'SUBDOMAIN_INSTALL' );
		$domain_current_site = $this->config_file->get_key( 'DOMAIN_CURRENT_SITE' );
		$path_current_site = $this->config_file->get_key( 'PATH_CURRENT_SITE' );
		$site_id_current_site = $this->config_file->get_key( 'SITE_ID_CURRENT_SITE' );
		$blog_id_current_site = $this->config_file->get_key( 'BLOG_ID_CURRENT_SITE' );

		// write values to new config file
self::debug(__METHOD__.'() multisite- rewriting constants');
		$this->new_config->isolate_block('/**#@-*/', '/* That\'s all, stop editing! Happy blogging. */' );

		$this->new_config->set_key( 'WP_ALLOW_MULTISITE', $wp_allow_multisite );
		$this->new_config->set_key( 'MULTISITE', 'TRUE' );
		$this->new_config->set_key( 'SUBDOMAIN_INSTALL', $subdomain_install );
		$this->new_config->set_key( 'DOMAIN_CURRENT_SITE', $domain_current_site );
		$this->new_config->set_key( 'PATH_CURRENT_SITE', $path_current_site );
		$this->new_config->set_key( 'SITE_ID_CURRENT_SITE', $site_id_current_site );
		$this->new_config->set_key( 'BLOG_ID_CURRENT_SITE', $blog_id_current_site );
	}
}
