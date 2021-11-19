<?php
/**
 * Plugin Name: RFS ACF JSON Sync Notice
 * Plugin URI: 
 * Description: Displays notice if there is acf json sync available for acf field groups
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Version: v1.1.0
 * Author: Rafal Puczel
 * Author URI: https://www.rfscreations.pl/
 * License: GPL v2 or later
 * Copyright: Rafal Puczel
 * Text Domain: rfswp
 * Domain Path: /languages
*/

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define( 'RFS_ACF_SYNC_NOTICE_NAMESPACE', 'RFS_ACF_SYNC_NOTICE\\' );

spl_autoload_register( function ( $class ) {
	$baseDirectory = __DIR__ . '/classes/';

	$namespacePrefixLength = strlen( RFS_ACF_SYNC_NOTICE_NAMESPACE );

	if ( strncmp( RFS_ACF_SYNC_NOTICE_NAMESPACE, $class, $namespacePrefixLength ) !== 0 ) {
		return;
	}
	$relativeClassName = substr( $class, $namespacePrefixLength );

	$classFilename = $baseDirectory . str_replace( '\\', '/', $relativeClassName ) . '.php';

	if ( file_exists( $classFilename ) ) {
		require $classFilename;
	}
} );

if ( !defined('RFS_ACF_SYNC_NOTICE_TEXTDOMAIN') ) {
  define('RFS_ACF_SYNC_NOTICE_TEXTDOMAIN', 'rfswp');
}

if ( !defined('RFS_ACF_SYNC_NOTICE_SLUG') ) {
  define('RFS_ACF_SYNC_NOTICE_SLUG', 'rfs-acf-sync-notice');
}

if ( !defined('RFS_ACF_SYNC_NOTICE_DIR') ) {
  define('RFS_ACF_SYNC_NOTICE_DIR', WP_PLUGIN_DIR . '/rfs-acf-sync-notice');
}

if ( !defined('RFS_ACF_SYNC_NOTICE_URL') ) {
  define('RFS_ACF_SYNC_NOTICE_URL', plugins_url() . '/rfs-acf-sync-notice');
}

if ( !defined('RFS_ACF_SYNC_NOTICE_FILE') ) {
  define('RFS_ACF_SYNC_NOTICE_FILE', __FILE__);
}

new \RFS_ACF_SYNC_NOTICE\Plugin();
?>