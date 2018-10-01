<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Canto_Settings {

	/**
	 * The single instance of Canto_Settings.
	 * @var    object
	 * @access  private
	 * @since    1.0.0
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 * @var    object
	 * @access  public
	 * @since    1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for Canto.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	public function __construct( $parent ) {
		$this->parent = $parent;

		$this->base = 'fbc_';

		// Initialise settings
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		// Register Canto
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->parent->file ),
			array( $this, 'add_settings_link' ) );
	}

	/**
	 * Initialise settings
	 * @return void
	 */
	public function init_settings() {
		$this->settings = $this->settings_fields();
		add_action( 'wp_ajax_fbc_updateOptions', array( $this, 'fbc_updateOptions' ) );
		//add_action( 'wp_ajax_fbc_getToken', array( $this, 'fbc_getToken' ) );
		//add_action( 'wp_ajax_fbc_refreshToken', array( $this, 'fbc_refreshToken' ) );
	}

	/**
	 * Add settings page to admin menu
	 * @return void
	 */
	public function add_menu_item() {
		$page = add_options_page( __( 'Canto', 'canto' ),
			__( 'Canto', 'canto' ), 'manage_options', $this->parent->_token . '_settings',
			array( $this, 'settings_page' ) );
		add_action( 'admin_print_styles-' . $page, array( $this, 'settings_assets' ) );
	}

	/**
	 * Load settings JS & CSS
	 * @return void
	 */
	public function settings_assets() {

		// We're including the WP media scripts here because they're needed for the image upload field
		// If you're not including an image upload then you can leave this function call out
		wp_enqueue_media();

		wp_register_script( $this->parent->_token . '-settings-js',
			$this->parent->assets_url . 'js/settings' . $this->parent->script_suffix . '.js',
			array( 'farbtastic', 'jquery' ), '1.0.0' );
		wp_enqueue_script( $this->parent->_token . '-settings-js' );
	}

	/**
	 * Add settings link to plugin list table
	 *
	 * @param  array $links Existing links
	 *
	 * @return array        Modified links
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' . __( 'Settings',
				'canto' ) . '</a>';
		array_push( $links, $settings_link );

		return $links;
	}

	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields() {

		$settings['standard'] = array(
			'title'       => "Canto Settings",
			'description' => __( '','canto' ),
			'fields'      => array(
				array(
					'id'          => 'flight_domain',
					'label'       => __( 'Flight Domain', 'canto' ),
					'description' => __( '.canto.com', 'canto' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'Canto Domain', 'canto' )
				),
			)
		);

		$settings = apply_filters( $this->parent->_token . '_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register Canto
	 * @return void
	 */
	public function register_settings() {
		if ( is_array( $this->settings ) ) {

			// Check posted/selected tab
			$current_section = '';
			if ( isset( $_POST['tab'] ) && $_POST['tab'] ) {
				$current_section = $_POST['tab'];
			} else {
				if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
					$current_section = $_GET['tab'];
				}
			}

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section != $section ) {
					continue;
				}

				// Add section to page
				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ),
					$this->parent->_token . '_settings' );

				foreach ( $data['fields'] as $field ) {

					// Validation callback for field
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field
					$option_name = $this->base . $field['id'];
					register_setting( $this->parent->_token . '_settings', $option_name, $validation );

					// Add field to page
					@add_settings_field( $field['id'], $field['label'], array( $this->parent->admin, 'display_field' ),
						$this->parent->_token . '_settings', $section,
						array( 'field' => $field, 'prefix' => $this->base ) );
				}

				if ( ! $current_section ) {
					break;
				}
			}
		}
	}

	public function settings_section( $section ) {
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html;
	}

	/**
	 * Load settings page content
	 * @return void
	 */
	public function settings_page() {

		// Build page HTML
		$html = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
		$html .= '<h2>' . __( 'Canto', 'canto' ) . '</h2>' . "\n";

		$tab = '';
		if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
			$tab .= $_GET['tab'];
		}

		// Show page tabs
		if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

			$html .= '<h2 class="nav-tab-wrapper">' . "\n";

			$c = 0;
			foreach ( $this->settings as $section => $data ) {

				// Set tab class
				$class = 'nav-tab';
				if ( ! isset( $_GET['tab'] ) ) {
					if ( 0 == $c ) {
						$class .= ' nav-tab-active';
					}
				} else {
					if ( isset( $_GET['tab'] ) && $section == $_GET['tab'] ) {
						$class .= ' nav-tab-active';
					}
				}

				// Set tab link
				$tab_link = add_query_arg( array( 'tab' => $section ) );
				if ( isset( $_GET['settings-updated'] ) ) {
					$tab_link = remove_query_arg( 'settings-updated', $tab_link );
				}

				// Output tab
				$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

				++ $c;
			}

			$html .= '</h2>' . "\n";
		}

		/*
		 * Canto oAuth Config and connection
		 */
		$api_domains = array('canto.com' => 'e3a2d379335d48e7afef348dda917fd9',
												'canto.global' => '0fac4b924b404106a6de4a6e53dc0de2',
												'cantoflight.com' => '2883b274ab9740d8bfb96366a0adead2');
		$oAuth = "https://oauth.canto.com:8443/oauth/api/oauth2/authorize?response_type=code";
		$appID = "e3a2d379335d48e7afef348dda917fd9";
		$callback = urlencode("https://wordpress.canto.com/callback.php?app_api=canto.com");
		$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
		$state = urlencode($scheme.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
		$oAuthURL = $oAuth.'&app_id='.$appID.'&redirect_uri='.$callback.'&state='.$state;

		$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";
		$html .= '<div id="fbc_settings_form">' . "\n";

		if ( get_option( 'fbc_flight_domain' ) == '' && get_option( 'fbc_app_token' ) == '') :

			$html .= "<i class='icon-icn_close_circle_x_01'></i>";
			$html .= '<strong>Status:</strong> You are not connected to Canto<br><br>';
			$html .= 'Select Your API endpoint: <select name="app_api" id="app_api">' . "\n";
			foreach($api_domains as $k => $v)
				$html .= '<option value="'.$k.'" data-appid="'.$v.'">company.'.$k.'</option>';
			$html .= '</select><br><br>';
			$html .= '<a class="button-primary" id="oAuthURL" href="'.$oAuthURL.'">Login to Canto</a>';

		elseif ( get_option( 'fbc_flight_domain' ) != '' && get_option( 'fbc_app_token' ) != '') :

			if( get_option( 'fbc_app_expire_token' ) < time() ) {

					$html .= "<i class='icon-icn_close_circle_x_01'></i>";
					$html .= '<strong>Status:</strong> Your security token has expired. You are not connected to Canto<br><br>';
					$html .= '<em>Last login: <strong>' . date("F d Y, g:i A", get_option( 'fbc_app_timestamp' ) ) . '</strong></em><br>';
					$html .= '<em>For security purposes you will need to login again after <strong>' . date("F d Y, g:i a", get_option( 'fbc_app_expire_token' ) ) . '</strong> </em><br><br>';
					$html .= '<a class="button-primary" href="'.$oAuthURL.'">Login to Canto</a>';

			} else {

					$app_api = (get_option('fbc_app_api')) ? get_option('fbc_app_api') : 'canto.com';

					$html .= "<i class='icon-icn_checkmark_circle_01'></i>";
					$html .= '<strong>Status:</strong> You are connected to Canto -  <strong>'.get_option('fbc_flight_domain').'.'.$app_api.'</strong><br><br>';
					$html .= '<em>Last login: <strong>' . date("F d Y, g:i A", get_option( 'fbc_app_timestamp' ) ) . '</strong></em><br>';
					$html .= '<em>For security purposes you will need to login again after <strong>' . date("F d Y, g:i a", get_option( 'fbc_app_expire_token' ) ) . '</strong> </em><br><br>';
					$html .= '<a class="button-primary" href="'.$scheme.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'&disconnect">Disconnect</a>';

			}


		else :

			$html .= 'There was a problem installing the plugin. Please contact support' . "\n";

		endif;


		$duplicates = (get_option('fbc_duplicates') === "true") ? "checked" : "";
		$cron = (get_option('fbc_cron') === "true") ? "checked" : "";

		$html .= "\n\n";

		//Only show options if connected to Flight
		if( get_option( 'fbc_flight_domain' ) != '' && get_option( 'fbc_app_token' ) != '') :

		$html .= '<p><hr /></p><h3>Options</h3>';
		$html .= '<div class="checkbox"><div style="display: table-cell;padding: 5px 0;"><input type="checkbox" name="duplicates" id="duplicates" '.$duplicates.'></div>';
		$html .= '<label style="display: table-cell;padding: 0 10px;"><strong>Duplicate Check</strong> - Updates Wordpress Media Library with latest version from Canto if image is added again</label></div>' . "\n";
		$html .= '<div style="clear:both"><br /></div>';

		$html .= '<div class="checkbox"><div style="display: table-cell;padding: 5px 0;"><input type="checkbox" name="cron" id="cron" '.$cron.'></div>';
		$html .= '<label style="display: table-cell;padding: 0 10px;"><strong>Automatic Update</strong> - Check for new versions of files added from Canto and update Wordpress Media Library with latest version'. "\n";


		//Cron schedule options
		$html .= '<div id="cron_schedule_options" style="padding: 10px; '.((get_option('fbc_cron') === "true") ? "" : "display:none").'">';
		$html .= '<select name="schedule" id="schedule">';
		$html .= '<option value="every_day" '.((get_option('fbc_schedule') === "every_day") ? "selected" : "").'>Every Day</option>';
		$html .= '<option value="every_week" '.((get_option('fbc_schedule') === "every_week") ? "selected" : "").'>Once a Week</option>';
		$html .= '<option value="every_month" '.((get_option('fbc_schedule') === "every_month") ? "selected" : "").'>Once a Month</option>';
		$html .= '</select>' . "\n";

		$days = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
		$html .= '<select style="'.((get_option('fbc_schedule') === "every_week" || get_option('fbc_schedule') === "every_month") ? "" : "display:none").'" class="cron_times" name="cron_time_day" id="cron_time_day">' . "\n";
		foreach($days as $d)
			$html .= '<option value="'.$d.'" '.((get_option('fbc_cron_time_day') == $d) ? "selected" : "").'>'.$d.'</option>';
		$html .= '</select>';

		$html .= '<select class="cron_times" name="cron_time_hour" id="cron_time_hour">' . "\n";
		for($i=0;$i<24;$i++)
			$html .= '<option value="'.$i.'" '.((get_option('fbc_cron_time_hour') == $i) ? "selected" : "").'>'.$i.':00</option>';
		$html .= '</select>';

		$html .= '<p style="'.((get_option('fbc_schedule') != "every_month") ? "display:none;" : "").' margin:0;" class="cron_times" id="cron_time_month"><em>Will run each month on the first occurrence for the selected day of the week</em></p>';

		$html .= '</div>';

		$html .= '</label></div>' . "\n";


		$html .= '<p class="submit">' . "\n";
		$html .= '<button id="updateOptions" class="button-primary">Save Options</button>' . "\n";
		$html .= '</p>' . "\n";

		$html .= '</div>' . "\n";

		endif;
		//End options


		$html .= '<img src="'.FBC_URL.'/assets/loader_white.gif" id="loader" style="display:none">';
		$html .= '</form>' . "\n";
		$html .= '</div>' . "\n";

		echo $html;





		//Generate OAuth Token -- Unused until API {Redirect URI} is fixed
		if ( isset($_REQUEST['disconnect']) ) {

			delete_option('fbc_flight_domain');
			delete_option('fbc_app_id');
			delete_option('fbc_app_api');
			delete_option('fbc_app_secret');
			delete_option('fbc_app_token');
			delete_option('fbc_app_refresh_token');
			delete_option('fbc_token_expire');
			delete_option('fbc_flight_username');
			delete_option('fbc_flight_password');
			delete_option('fbc_refresh_token_expire');


			$arr = explode("&disconnect",$_SERVER['REQUEST_URI']);
			$rURI = $arr[0];

			echo '<script type="text/javascript">';

			$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
			echo "window.location.href = '" . $scheme . "://" . $_SERVER['HTTP_HOST'] . $rURI . "';";
			echo '</script>';
		}


		//Generate OAuth Token -- Unused until API {Redirect URI} is fixed
		if ( isset($_REQUEST['token']) && isset($_REQUEST['domain']) ) :
			update_option( 'fbc_app_token', $_REQUEST['token'] );
			update_option( 'fbc_flight_domain', $_REQUEST['domain'] );
			update_option( 'fbc_app_timestamp', time() );
			update_option( 'fbc_app_refresh_token', $_REQUEST['refreshToken'] );
			update_option( 'fbc_app_expire_token', time() + $_REQUEST['expiresIn'] );

			$app_api = isset($_REQUEST['app_api']) ? $_REQUEST['app_api'] : 'canto.com';
			update_option( 'fbc_app_api', $app_api );


			$arr = explode("&token=",$_SERVER['REQUEST_URI']);
			$rURI = $arr[0];

			$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
			echo '<script type="text/javascript">';
			echo "window.location.href = '" . $scheme . "://" . $_SERVER['HTTP_HOST'] . $rURI . "';";
			echo '</script>';

		endif;

		?>
				<script type="text/javascript">
					jQuery('#app_api').change(function(e){
						var app_api = jQuery(this).val();
						var app_id = jQuery(this).find(':selected').data('appid');
						var oAuthURL = jQuery('#oAuthURL').attr('href');
						var endpoint = oAuthURL.replace(/https\:\/\/(.+?):8443/,'https://oauth.'+app_api+':8443');
						endpoint = endpoint.replace(/app_api\%3D(.+?)&/,'app_api%3D'+app_api+'&');
						endpoint = endpoint.replace(/app_id=(.+?)&/,'app_id='+app_id+'&');
						jQuery('#oAuthURL').attr('href',endpoint);
					});
					jQuery('#updateOptions').click(function (e) {
						e.preventDefault();
						var data = {
							'action': 'fbc_updateOptions',
							'duplicates': jQuery("#duplicates").prop('checked'),
							'cron': jQuery("#cron").prop('checked'),
							'schedule': jQuery("#schedule").val(),
							'cron_time_day': jQuery("#cron_time_day").val(),
							'cron_time_hour': jQuery("#cron_time_hour").val()
						};
						jQuery.post(ajaxurl, data, function (response) {
							response = jQuery.parseJSON(response);
							if (typeof response.error === "undefined") {
								location.reload();
							}
						});
					});

					jQuery('#cron').on('change',function(){
						if(jQuery(this).is(':checked')) {
							jQuery('#cron_schedule_options').show();
						} else {
							jQuery('#cron_schedule_options').hide();
						}
					});

					jQuery('#schedule').on('change',function(){
						jQuery('.cron_times').hide();
						var schedule = jQuery(this).val();
						if(schedule == 'every_week' || schedule == 'every_month') {
							jQuery('#cron_time_day').show();
						}
						jQuery('#cron_time_hour').show();
						if(schedule == 'every_month') {
							jQuery('#cron_time_month').show();
						}
					});
				</script>
			<?php


	}


	public function fbc_updateOptions() {
		$instance = Canto::instance();
		//var_dump($instance); wp_die();
		return $instance->updateOptions();
	}

	public function fbc_getToken() {
		$instance = Canto::instance();

		//var_dump($instance); wp_die();
		return $instance->getToken();
	}

	public function fbc_refreshToken() {
		$instance = Canto::instance();

		//var_dump($instance); wp_die();
		return $instance->refreshToken();
	}

	/**
	 * Main Canto_Settings Instance
	 *
	 * Ensures only one instance of Canto_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Canto()
	 * @return Main Canto_Settings instance
	 */
	public static function instance( $parent ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}

		return self::$_instance;
	} // End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __wakeup()

}
