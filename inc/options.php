<?php

# Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sif_admin_head() {
	if( isset( $_GET['page'] ) && $_GET['page'] == SIF_PLUGIN_SLUG) {

		$sif_connect = get_option( 'sif_connect' );

		if( $sif_connect  !== false && ! empty( $sif_connect['client_id'] ) && ! empty( $sif_connect['client_secret'] ) ) {
			if( isset( $_GET['code'] ) ) {
				$body                  = array();
				$body['client_id']     = $sif_connect['client_id'];
				$body['client_secret'] = $sif_connect['client_secret'];
				$body['grant_type']    = 'authorization_code';
				$body['code']          = $_GET['code'];
				$body['redirect_uri']  = SIF_ADMIN_URL;

				$response = wp_remote_post( SIF_API_INSTAGRAM_URL . 'oauth/access_token/', array( 'body' => $body ) );

				if ( ! is_wp_error( $response ) ) {
					$response = json_decode( $response['body'] );

					if( $response->code == NULL ) {
						$sif_general  = get_option( 'sif_general' );

						$access_token = $response->access_token;
						$username     = $response->user->username;

						if( $sif_general === false ) {
							$sif_general                 = array();
							$sif_general['access_token'] = $access_token;
							$sif_general['username']     = $username;

							add_option( 'sif_general', $sif_general );
						} else {
							$sif_general['access_token'] = $access_token;
							$sif_general['username']     = $username;

							update_option( 'sif_general', $sif_general );
						}

						delete_option( 'sif_connect' );

						wp_redirect( SIF_ADMIN_URL );
						exit();
					} else {
						add_action( 'admin_notices', 'sif_error_notice' );
					}
				} else {
					add_action( 'admin_notices', 'sif_error_notice' );
				}
			} else {
				$url  = SIF_API_INSTAGRAM_URL . 'oauth/authorize/?scope=basic&response_type=code';
				$url .= '&client_id=' . urlencode( $sif_connect['client_id'] );
				$url .= '&redirect_uri=' . urlencode( SIF_ADMIN_URL );

				wp_redirect( $url );
				exit();
			}
		} else if( isset( $_GET['signout'] ) ) {
			delete_option( 'sif_general' );

			delete_transient( 'sif_instagram_feed' );
			delete_transient( 'sif_instagram_feed_backup' );

			add_action( 'admin_notices', 'sif_sign_out_notice' );
		}
	}
}
add_action( 'admin_head', 'sif_admin_head' );

function sif_sign_in_notice() {
	echo '<div class="updated notice is-dismissible"><p><strong>' . __( 'You are connected!', 'simple-instagram-feed' ) . '</strong></p></div>';
}

function sif_sign_out_notice() {
    echo '<div class="error notice is-dismissible"><p><strong>' . __( 'You are disconnected!', 'simple-instagram-feed' ) . '</strong></p></div>';
}

function sif_error_notice() {
    echo '<div class="error notice is-dismissible"><p><strong>' . __( 'Problem with Instagram authorization, retry again.', 'simple-instagram-feed' ) . '</strong></p></div>';
}

function sif_admin_menu() {
	add_options_page(
		__( 'Simple Instagram Feed', 'simple-instagram-feed' ), 
		__( 'Simple Instagram Feed', 'simple-instagram-feed' ), 
		'manage_options', 
		SIF_PLUGIN_SLUG, 
		'sif_options_page'
	);
}
add_action( 'admin_menu', 'sif_admin_menu' );

function sif_options_page() {
	$tab = 'general';

	if( isset( $_GET['tab'] ) ) {
		$tab = $_GET['tab'];
	}

	if( $tab == 'general' ) {
		$sif_general = get_option( 'sif_general' );

		if( $sif_general === false ) {
			$tab = 'connect';
		}
	}
	?>
	<div class="wrap">
		<h1><?php _e( 'Simple Instagram Feed', 'simple-instagram-feed' ); ?></h1>
		
		<h2 class="nav-tab-wrapper" id="sif-tabs">
			<a id="general-tab" class="nav-tab<?php echo ( $tab == 'general' || $tab == 'connect' ) ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'options-general.php?page=' . SIF_PLUGIN_SLUG ) ); ?>"><?php _e( 'General', 'simple-instagram-feed' ); ?></a>
			<a id="feed_settings-tab" class="nav-tab<?php echo ( $tab == 'feed_settings' ) ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'options-general.php?page=' . SIF_PLUGIN_SLUG . '&tab=feed_settings' ) ); ?>"><?php _e( 'Feed settings', 'simple-instagram-feed' ); ?></a>
			<a id="customize-tab" class="nav-tab<?php echo ( $tab == 'customize' ) ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'options-general.php?page=' . SIF_PLUGIN_SLUG . '&tab=customize' ) ); ?>"><?php _e( 'Customize', 'simple-instagram-feed' ); ?></a>
			<a id="advanced-tab" class="nav-tab<?php echo ( $tab == 'advanced' ) ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'options-general.php?page=' . SIF_PLUGIN_SLUG . '&tab=advanced' ) ); ?>"><?php _e( 'Advanced', 'simple-instagram-feed' ); ?></a>
		</h2>

		<form action="options.php" method="post">
			<?php
			wp_nonce_field( 'update-options' );

			switch( $tab ) {
				case 'general':
					settings_fields( 'sif_' . $tab . '_group' );
					do_settings_sections( 'sif_' . $tab . '_group' );
					sif_general_button();
				break;
				case 'connect':
				case 'feed_settings':
				case 'customize':
				case 'advanced':
					settings_fields( 'sif_' . $tab . '_group' );
					do_settings_sections( 'sif_' . $tab . '_group' );
					submit_button();
				break;
			}
			?>
		</form>
	</div>
	<?php
}

function sif_admin_init() {
	if( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.', 'simple-instagram-feed' ) );
	}

	wp_enqueue_script( 'sif_admin', plugins_url( '/js/admin.js', SIF_JS_PATH ), array( 'jquery' ), SIF_VERSION, true );

	sif_register_setting_connect();
	sif_register_setting_general();
	sif_register_setting_feed_settings();
	sif_register_setting_customize();
	sif_register_setting_advanced();
}
add_action( 'admin_init', 'sif_admin_init' );

function sif_register_setting_connect() {
	register_setting( 'sif_connect_group', 'sif_connect' );

	add_settings_section(
		'sif_connect_section', 
		__( 'Connection', 'simple-instagram-feed' ), 
		'sif_connect_section_callback', 
		'sif_connect_group'
	);

	add_settings_field( 
		'sif_connect_client_id', 
		__( 'Client ID', 'simple-instagram-feed' ), 
		'sif_settings_field', 
		'sif_connect_group', 
		'sif_connect_section', 
		array( 
			'option'    => 'sif_connect', 
			'label_for' => 'sif_client_id', 
			'key'       => 'client_id', 
			'default'   => '',
		)
	);

	add_settings_field( 
		'sif_connect_client_secret', 
		__( 'Client Secret', 'simple-instagram-feed' ), 
		'sif_settings_field', 
		'sif_connect_group', 
		'sif_connect_section', 
		array( 
			'option'    => 'sif_connect', 
			'label_for' => 'sif_client_secret', 
			'key'       => 'client_secret', 
			'default'   => '',
		)
	);
}

function sif_connect_section_callback() {
	printf('<h3>%s</h3>', __( 'Step 1', 'simple-instagram-feed' ));
	printf(__( '<p>Goto Instagram developper account > Manage Clients > <a href="%s" target="_blank">Register a New Client</a></p>', 'simple-instagram-feed' ), 'https://www.instagram.com/developer/clients/register/' );
	
	printf('<h3>%s</h3>', __( 'Step 2', 'simple-instagram-feed' ));
	printf(__( '<p>Register new <strong>client ID</strong> with valid redirect URIs : %s</p>', 'simple-instagram-feed' ), SIF_ADMIN_URL );

	printf('<h3>%s</h3>', __( 'Step 3', 'simple-instagram-feed' ));
	_e( '<p>Enter your <strong>client ID</strong> and <strong>client secret</strong>. These are only used for Instagram authorization and not saved in database</p>', 'simple-instagram-feed' );
}

function sif_register_setting_general() {
	register_setting( 'sif_general_group', 'sif_general' );

	add_settings_section(
		'sif_general_section', 
		__( 'General', 'simple-instagram-feed' ), 
		'sif_general_section_callback', 
		'sif_general_group'
	);

	add_settings_field( 
		'sif_general_access_token', 
		__( 'Access token', 'simple-instagram-feed' ), 
		'sif_settings_field', 
		'sif_general_group', 
		'sif_general_section', 
		array( 
			'option'    => 'sif_general', 
			'label_for' => 'sif_access_token', 
			'key'       => 'access_token', 
			'default'   => '', 
			'attr'      => array(
				'readonly' => true,
			),
		)
	);

	add_settings_field( 
		'sif_general_username', 
		__( 'Username', 'simple-instagram-feed' ), 
		'sif_settings_field', 
		'sif_general_group', 
		'sif_general_section', 
		array( 
			'option'    => 'sif_general', 
			'label_for' => 'sif_username', 
			'key'       => 'username', 
			'default'   => '', 
			'attr'      => array(
				'readonly' => true,
			),
		)
	);
}

function sif_general_section_callback() {

}

function sif_general_button() {
	$sif_general = get_option( 'sif_general' );

	if( $sif_general !== false && ! empty( $sif_general['access_token'] ) && ! empty( $sif_general['username'] ) ) {
		$label = __( 'Sign out with Instagram', 'simple-instagram-feed' );
		$url   = esc_url( SIF_ADMIN_URL . '&signout' );

		echo '<p><a href="' . $url . '" class="button button-secondary">' . $label . '</a><p>';
	}
}

function sif_register_setting_customize() {
	register_setting( 'sif_customize_group', 'sif_customize' );

	add_settings_section(
		'sif_customize_section', 
		__( 'Customize', 'simple-instagram-feed' ), 
		'sif_customize_section_callback', 
		'sif_customize_group'
	);

	add_settings_field( 
		'sif_customize_theme', 
		__( 'Theme', 'simple-instagram-feed' ), 
		'sif_settings_select', 
		'sif_customize_group', 
		'sif_customize_section', 
		array( 
			'option'    => 'sif_customize', 
			'label_for' => 'sif_theme', 
			'key'       => 'theme',
			'values'    => array(
				''        => __( 'None', 'simple-instagram-feed' ),
				'default' => __( 'Default', 'simple-instagram-feed' ),
			),
		)
	);

	add_settings_field( 
		'sif_customize_css', 
		__( 'Custom CSS', 'simple-instagram-feed' ), 
		'sif_settings_textarea', 
		'sif_customize_group', 
		'sif_customize_section', 
		array( 
			'option'    => 'sif_customize', 
			'label_for' => 'sif_css', 
			'key'       => 'css', 
			'default'   => '',
			'attr'      => array(
				'rows' => 15,
				'cols' => 100,
			),
		)
	);
}

function sif_customize_section_callback() {

}

function sif_register_setting_feed_settings() {
	register_setting( 'sif_feed_settings_group', 'sif_feed_settings' );

	add_settings_section(
		'sif_feed_settings_section', 
		__( 'Feed settings', 'simple-instagram-feed' ), 
		'sif_feed_settings_section_callback', 
		'sif_feed_settings_group'
	);

	add_settings_field( 
		'sif_feed_settings_hide_title', 
		__( 'Hide title', 'simple-instagram-feed' ), 
		'sif_settings_checkbox', 
		'sif_feed_settings_group', 
		'sif_feed_settings_section', 
		array( 
			'option'    => 'sif_feed_settings', 
			'label_for' => 'sif_hide_title', 
			'key'       => 'hide_title',
			'default'   => false,
		)
	);

	add_settings_field( 
		'sif_feed_settings_title', 
		__( 'Title', 'simple-instagram-feed' ), 
		'sif_settings_field', 
		'sif_feed_settings_group', 
		'sif_feed_settings_section', 
		array( 
			'option'    => 'sif_feed_settings', 
			'label_for' => 'sif_title', 
			'key'       => 'title',
			'default'   => __( 'Instagram Feed', 'simple-instagram-feed' ),
		)
	);

	add_settings_field( 
		'sif_feed_settings_media_count', 
		__( 'Media count', 'simple-instagram-feed' ), 
		'sif_settings_field', 
		'sif_feed_settings_group', 
		'sif_feed_settings_section', 
		array( 
			'option'    => 'sif_feed_settings', 
			'label_for' => 'sif_media_count', 
			'key'       => 'media_count',
			'type'      => 'number', 
			'default'   => SIF_DEFAULT_MEDIA_COUNT,  
			'attr'      => array(
				'min' => 1,
			),
		)
	);

	add_settings_field( 
		'sif_feed_settings_show_likes', 
		__( 'Show likes', 'simple-instagram-feed' ), 
		'sif_settings_checkbox', 
		'sif_feed_settings_group', 
		'sif_feed_settings_section', 
		array( 
			'option'    => 'sif_feed_settings', 
			'label_for' => 'sif_show_likes', 
			'key'       => 'show_likes',
			'default'   => false,
		)
	);

	add_settings_field( 
		'sif_feed_settings_show_comments', 
		__( 'Show comments', 'simple-instagram-feed' ), 
		'sif_settings_checkbox', 
		'sif_feed_settings_group', 
		'sif_feed_settings_section', 
		array( 
			'option'    => 'sif_feed_settings', 
			'label_for' => 'sif_show_comments', 
			'key'       => 'show_comments',
			'default'   => false,
		)
	);

	add_settings_field( 
		'sif_feed_settings_show_description', 
		__( 'Show description', 'simple-instagram-feed' ), 
		'sif_settings_checkbox', 
		'sif_feed_settings_group', 
		'sif_feed_settings_section', 
		array( 
			'option'    => 'sif_feed_settings', 
			'label_for' => 'sif_show_description', 
			'key'       => 'show_description',
			'default'   => false,
		)
	);

	add_settings_field( 
		'sif_feed_settings_show_date', 
		__( 'Show datetime', 'simple-instagram-feed' ), 
		'sif_settings_checkbox', 
		'sif_feed_settings_group', 
		'sif_feed_settings_section', 
		array( 
			'option'    => 'sif_feed_settings', 
			'label_for' => 'sif_show_date', 
			'key'       => 'show_datetime',
			'default'   => false,
		)
	);

	add_settings_field( 
		'sif_feed_settings_date_format', 
		__( 'Date format', 'simple-instagram-feed' ), 
		'sif_settings_field', 
		'sif_feed_settings_group', 
		'sif_feed_settings_section', 
		array( 
			'option'    => 'sif_feed_settings', 
			'label_for' => 'sif_date_format', 
			'key'       => 'date_format',
			'default'   => get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
		)
	);

	add_settings_field( 
		'sif_feed_settings_video_player', 
		__( 'Video player', 'simple-instagram-feed' ), 
		'sif_settings_checkbox', 
		'sif_feed_settings_group', 
		'sif_feed_settings_section', 
		array( 
			'option'    => 'sif_feed_settings', 
			'label_for' => 'sif_video_player', 
			'key'       => 'video_player',
			'default'   => false,
		)
	);

	add_settings_field( 
		'sif_feed_settings_video_player_loop', 
		__( 'Video player - loop', 'simple-instagram-feed' ), 
		'sif_settings_checkbox', 
		'sif_feed_settings_group', 
		'sif_feed_settings_section', 
		array( 
			'option'    => 'sif_feed_settings', 
			'label_for' => 'sif_video_player_loop', 
			'key'       => 'video_player_loop',
			'default'   => false,
		)
	);

	add_settings_field( 
		'sif_feed_settings_links', 
		__( 'Links', 'simple-instagram-feed' ), 
		'sif_settings_select', 
		'sif_feed_settings_group', 
		'sif_feed_settings_section', 
		array( 
			'option'    => 'sif_feed_settings', 
			'label_for' => 'sif_links', 
			'key'       => 'links',
			'values'    => array(
				''       => __( 'No', 'simple-instagram-feed' ),
				'_self'  => __( 'Open the link in the same browser window', 'simple-instagram-feed' ),
				'_blank' => __( 'Open the link in a new browser window', 'simple-instagram-feed' ),
			),
		)
	);
}

function sif_feed_settings_section_callback() {

}

function sif_register_setting_advanced() {
	register_setting( 'sif_advanced_group', 'sif_advanced' );

	add_settings_section(
		'sif_advanced_section', 
		__( 'Advanced', 'simple-instagram-feed' ), 
		'sif_advanced_section_callback', 
		'sif_advanced_group'
	);

	add_settings_field( 
		'sif_advanced_cache_expiration', 
		__( 'Cache expiration', 'simple-instagram-feed' ), 
		'sif_settings_field', 
		'sif_advanced_group', 
		'sif_advanced_section', 
		array( 
			'option'      => 'sif_advanced', 
			'label_for'   => 'sif_cache_expiration', 
			'key'         => 'cache_expiration',
			'type'        => 'number', 
			'default'     => SIF_DEFAULT_CACHE_EXPIRATION, 
			'description' => __( 'in seconds. 0 for never expires.', 'simple-instagram-feed' ), 
			'attr'        => array(
				'min' => 0,
			),
		)
	);
}

function sif_advanced_section_callback() {
	
}

function sif_settings_field( $arg ) {
	if( isset( $arg['label_for'] ) && isset( $arg['option'] ) && isset( $arg['key'] ) ) {
		$attr   = 'id="' . $arg['label_for'] . '" ';
		$option = get_option( $arg['option'] );
		$value  = '';

		if( isset( $arg['type'] ) ) {
			$attr .= 'type="' . $arg['type'] . '" ';
		} else {
			$attr .= 'type="text" ';
		}

		if( isset( $arg['attr'] ) ) {
			foreach( $arg['attr'] as $attr_key => $attr_value ) {
				$attr .= $attr_key . '="' . $attr_value . '" ';
			}
		}

		if( $option !== false && isset( $option[ $arg['key'] ] ) ) {
			$value = $option[ $arg['key'] ];
		} else if( isset( $arg['default'] ) ) {
			$value = $arg['default'];
		}

		echo '<input ' . $attr . 'name="'. $arg['option'] . '[' . $arg['key'] . ']" value="'. $value . '">';

		if( isset( $arg['description'] ) ) {
			echo '<p class="description">' . $arg['description'] . '</p>';
		}
	}
}

function sif_settings_select( $arg ) {
	if( isset( $arg['label_for'] ) && isset( $arg['option'] ) && isset( $arg['key'] ) ) {
		$attr   = 'id="' . $arg['label_for'] . '" ';
		$option = get_option( $arg['option'] );
		$value  = '';

		if( isset( $arg['attr'] ) ) {
			foreach( $arg['attr'] as $attr_key => $attr_value ) {
				$attr .= $attr_key . '="' . $attr_value . '" ';
			}
		}

		if( $option !== false && isset( $option[ $arg['key'] ] ) ) {
			$value = $option[ $arg['key'] ];
		} else if( isset( $arg['default'] ) ) {
			$value = $arg['default'];
		}

		echo '<select ' . $attr . 'name="'. $arg['option'] . '[' . $arg['key'] . ']">';
			if( isset( $arg['values'] ) ) {
				foreach( $arg['values'] as $option_value => $option_label ) {
					echo '<option value="'. $option_value . '"' . ( ($value == $option_value) ? ' selected' : '' ) . '>' . $option_label . '</option>';
				}
			}
		echo '</select>';

		if( isset( $arg['description'] ) ) {
			echo '<p class="description">' . $arg['description'] . '</p>';
		}
	}
}

function sif_settings_checkbox( $arg ) {
	if( isset( $arg['label_for'] ) && isset( $arg['option'] ) && isset( $arg['key'] ) ) {
		$attr    = 'id="' . $arg['label_for'] . '" type="checkbox" ';
		$option  = get_option( $arg['option'] );
		$checked = false;

		if( isset( $arg['attr'] ) ) {
			foreach( $arg['attr'] as $attr_key => $attr_value ) {
				$attr .= $attr_key . '="' . $attr_value . '" ';
			}
		}

		if( $option !== false && isset( $option[ $arg['key'] ] ) ) {
			$checked = $option[ $arg['key'] ];
		} else if( isset( $arg['default'] ) ) {
			$checked = $arg['default'];
		}

		echo '<input ' . $attr . 'name="'. $arg['option'] . '[' . $arg['key'] . ']" value="1" ' . ($checked ? ' checked' : '') . '>';

		if( isset( $arg['description'] ) ) {
			echo '<p class="description">' . $arg['description'] . '</p>';
		}
	}
}

function sif_settings_textarea( $arg ) {
	if( isset( $arg['label_for'] ) && isset( $arg['option'] ) && isset( $arg['key'] ) ) {
		$attr   = 'id="' . $arg['label_for'] . '" type="checkbox" ';
		$option = get_option( $arg['option'] );
		$value  = '';

		if( isset( $arg['attr'] ) ) {
			foreach( $arg['attr'] as $attr_key => $attr_value ) {
				$attr .= $attr_key . '="' . $attr_value . '" ';
			}
		}

		if( $option !== false && isset( $option[ $arg['key'] ] ) ) {
			$value = $option[ $arg['key'] ];
		} else if( isset( $arg['default'] ) ) {
			$value = $arg['default'];
		}

		echo '<textarea ' . $attr . 'name="'. $arg['option'] . '[' . $arg['key'] . ']">' . $value . '</textarea>';

		if( isset( $arg['description'] ) ) {
			echo '<p class="description">' . $arg['description'] . '</p>';
		}
	}
}