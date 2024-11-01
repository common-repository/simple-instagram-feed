<?php
/*
Plugin Name: Simple Instagram Feed
Plugin URI: 
Description: 
Author: Yann Kozon
Version: 1.0.0
Author URI: https://www.yannkozon.com/
Text Domain: simple-instagram-feed
Domain Path: /languages
License: GPLv2
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SIF_VERSION',                  '1.0.0' );
define( 'SIF_PATH',                     WP_PLUGIN_DIR . '/' . dirname( plugin_basename(  __FILE__ ) ) );
define( 'SIF_INC_PATH',                 WP_PLUGIN_DIR . '/' . dirname( plugin_basename(  __FILE__ ) ) . '/inc/' );
define( 'SIF_JS_PATH',                  WP_PLUGIN_DIR . '/' . dirname( plugin_basename(  __FILE__ ) ) . '/js/' );
define( 'SIF_TEMPLATE_PATH',            WP_PLUGIN_DIR . '/' . dirname( plugin_basename(  __FILE__ ) ) . '/templates/' );
define( 'SIF_URL',                      plugins_url( plugin_basename( dirname( __FILE__ ) ) ) );
define( 'SIF_PLUGIN_SLUG',              'simple-instagram-feed' );
define( 'SIF_ADMIN_URL',                admin_url( 'options-general.php?page=' . SIF_PLUGIN_SLUG ) );
define( 'SIF_API_INSTAGRAM_URL',        'https ://api.instagram.com/' );
define( 'SIF_DEFAULT_MEDIA_COUNT',      10 );
define( 'SIF_DEFAULT_CACHE_EXPIRATION', 86400 );


add_action( 'plugins_loaded', 'sif_init' );

function sif_init() {
	load_plugin_textdomain( 'simple-instagram-feed', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	if( is_admin() ) {
		require SIF_INC_PATH . 'options.php';
	} else {
		require SIF_INC_PATH . 'frontend.php';
	}
}

function sif_activated_plugin() {
	$sif_feed_settings                = array();
	$sif_feed_settings['title']       = __( 'Instagram Feed', 'simple-instagram-feed' );
	$sif_feed_settings['media_count'] = SIF_DEFAULT_MEDIA_COUNT;
	$sif_feed_settings['date_format'] = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	$sif_feed_settings['links']       = '_self';

	add_option( 'sif_feed_settings', $sif_feed_settings );
}
register_activation_hook( __FILE__, 'sif_activated_plugin' );

function sif_deactivated_plugin() {
	delete_option( 'sif_general' );
	delete_option( 'sif_customize' );
	delete_option( 'sif_feed_settings' );
	delete_option( 'sif_advanced' );

	delete_transient( 'sif_instagram_feed' );
	delete_transient( 'sif_instagram_feed_backup' );
}
register_deactivation_hook( __FILE__, 'sif_deactivated_plugin' );

