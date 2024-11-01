<?php

function sif_instagram_feed_template( $data ) {
	$settings = get_option( 'sif_feed_settings' );

	$settings_keys = array( 'hide_title', 
				   'show_likes', 
				   'show_comments', 
				   'show_description', 
				   'show_datetime', 
				   'video_player', 
				   'video_player_loop' );

	foreach( $settings_keys as $key ) {
		if( ! isset( $settings[ $key ] ) ) {
			$settings[ $key ] = false;
		}
	}

	if( file_exists( SIF_TEMPLATE_PATH . 'simple-instagram-feed/default.php' ) ) {
		require_once SIF_TEMPLATE_PATH . 'simple-instagram-feed/default.php';
	} else {
		require_once SIF_TEMPLATE_PATH . 'default.php';
	}
}

function sif_get_instagram_feed() {
	$sif_general = get_option( 'sif_general' );

	if( isset( $sif_general['access_token'] ) ) {
		$access_token   = $sif_general['access_token'];
		$transient_feed = get_transient( 'sif_instagram_feed' );

		$sif_advanced = get_option( 'sif_advanced' );
		if( isset( $sif_advanced['cache_expiration'] ) ) {
			$cache_expiration = $sif_advanced['cache_expiration'];
		} else {
			$cache_expiration = SIF_DEFAULT_CACHE_EXPIRATION;
		}

		$sif_feed_settings = get_option( 'sif_feed_settings' );
		if( isset( $sif_feed_settings['media_count'] ) ) {
			$media_count = $sif_feed_settings['media_count'];
		} else {
			$media_count = SIF_DEFAULT_MEDIA_COUNT;
		}

		if( $transient_feed !== false ) {
			return $transient_feed;
		} else {
			$response = wp_remote_get( SIF_API_INSTAGRAM_URL . 'v1/users/self/media/recent/?access_token=' . $access_token . '&count=' . $media_count );

			if( is_array( $response ) ) {
				$response = json_decode( $response['body'] );

				set_transient( 'sif_instagram_feed', $response, $cache_expiration );
				set_transient( 'sif_instagram_feed_backup', $response );

				return $response;
			} else {
				$transient_feed_backup = get_transient( 'sif_instagram_feed_backup' );

				if( $transient_feed_backup === false ) {
					return $response;
				} else {
					set_transient( 'sif_instagram_feed', $transient_feed_backup, $cache_expiration );
					return $transient_feed_backup;
				}
			}
		}
	}

	return false;
}

function sif_shortcode_instagram_feed() {
	sif_instagram_feed();
}
add_shortcode( 'sif_instagram_feed', 'sif_shortcode_instagram_feed' );

function sif_instagram_feed() {
	$feed = sif_get_instagram_feed();

	if( $feed !== false ) {
		sif_instagram_feed_template( $feed );
	} else {
		_e( 'Error: no access token', 'simple-instagram-feed' );
	}
}

function sif_add_theme( ) {
	$sif_customize = get_option( 'sif_customize' );

	if( ! empty( $sif_customize['theme'] ) ) {
		wp_register_style( 'sif-theme', SIF_URL . 'css/' . $sif_customize['theme'] . '.css', false, SIF_VERSION );
		wp_enqueue_style( 'sif-theme' );
	}
}
add_action( 'wp_enqueue_scripts', 'sif_add_theme' );

function sif_add_inline_css( ) {
	$sif_customize = get_option( 'sif_customize' );

	if( ! empty( $sif_customize['css'] ) ) {
		echo '<style type="text/css">' . esc_html( $sif_customize['css'] ) . '</style>';
	}
}
add_action( 'wp_head', 'sif_add_inline_css' );
