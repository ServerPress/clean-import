# Clean Import

Prepares an archive to be imported smoothly into DesktopServer

* Rename wp-config.php to wp-config.php.sav
* Create a standard wp-config.php file
* If mu-plugins folder exists, if does rename to mu-plugins-sav
* If .htaccess exists, if does rename to .htaccess-sav
* If advanced-cache.php exists, if does rename to advanced-cache.php-sav
* If object_cache.php exists, if does rename to object_cache.php-sav
* If wp-cache-config.php exists, if does rename to wp-cache-config.php-sav
* If plugins/wp-super-cache folder exists, if does rename to plugins/wp-super-cache-sav
* If w3tc-config folder exists, if does rename to plugins/w3tc-config-sav
* If plugins/w3-total-cache folder exists, if does rename to plugins/w3-total-cache-sav
* If wp-rocket-config folder exists, if does rename to plugins/wp-rocket-config-sav
* If plugins/wp-rocket folder exists, if does rename to plugins/wp-rocket-sav
* If ithemes-security-pro folder exists, if does rename to plugins/ithemes-security-pro-sav
* If cache folder exists, if does delete
* If cdn-enabler folder exists, if does rename to plugins/cdn-enabler-sav
* If jetpack folder exists, add define( 'JETPACK_DEV_DEBUG', true);
* If all-in-one-wp-security-and-firewall folder exists, if does rename to plugins/all-in-one-wp-security-and-firewall-sav
