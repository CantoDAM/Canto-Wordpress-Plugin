<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
function fbc_admin_notice__success() {
	?>
	<div class="notice notice-success">
		<p><?php _e( 'Done!', 'canto' ); ?></p>
	</div>
	<?php
}
add_action( 'admin_notices', 'fbc_admin_notice__success' );
*/

class Canto {

	/**
	 * The single instance of Canto.
	 * @var    object
	 */
	private static $_instance = null;

	/**
	 * Settings class object
	 * @var     object
	 */
	public $settings = null;

	/**
	 * The version number.
	 * @var     string
	 */
	public $_version;

	/**
	 * The token.
	 * @var     string
	 */
	public $_token;

	/**
	 * The canto authorization token.
	 * @var     string
	 */
	private $fbc_app_id;

	/**
	 * The canto authorization token.
	 * @var     string
	 */
	private $fbc_app_secret;

	/**
	 * The canto authorization token.
	 * @var     string
	 */
	private $fbc_app_token;

	/**
	 * The canto domain
	 * @var     string
	 */
	public $fbc_flight_domain;

	/**
	 * The authorization refresh token
	 * @var     string
	 */
	private $fbc_refresh_token;

	/**
	 * The canto Password
	 * @var    string
	 */
	private $fbc_flight_password;

	/**
	 * The canto Username
	 * @var    string
	 */
	private $fbc_flight_username;

	/**
	 * The main plugin file.
	 * @var     string
	 */
	public $file;

	/**
	 * The wordpress plugin directory.
	 * @var     string
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 * @var     string
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 */
	public $assets_url;

	/**
	 * Suffix for Javascripts.
	 * @var     string
	 */
	public $script_suffix;

	/**
	 * Constructor function.
	 * @access  public
	 * @return  void
	 */
	public function __construct( $file = '', $version = '1.0.0' ) {
		$this->_version = $version;
		$this->_token   = 'canto';

		// Load plugin environment variables
		$this->file       = $file;
		$this->dir        = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$this->fbc_app_token       = get_option( 'fbc_app_token' );
		$this->fbc_flight_domain   = get_option( 'fbc_flight_domain' );
		$this->fbc_refresh_token   = get_option( 'fbc_refresh_token' );
		$this->fbc_flight_username = get_option( 'fbc_flight_username' );

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load frontend JS & CSS
		//add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		//add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Load admin JS & CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );


		/*
		 * ToDo: separate outside of Constructor
		 */
		function md_modify_jsx_tag( $tag, $handle, $src ) {
		  // Check that this is output of JSX file
		  if( strstr($handle,'react') != FALSE ) {
			  //$tag = str_replace( "<script type='text/javascript'", "<script type='text/jsx'", $tag );
		  }

		  return $tag;
		}
		add_filter( 'script_loader_tag', 'md_modify_jsx_tag', 10, 3 );

		/* end additions to Constructor */


		/**
		 ** FBC WP CRON CUSTOM SCHEDULED TASK
		 **/
		if(get_option('fbc_cron') == "true") {
			add_filter( 'cron_schedules', 'fbc_scheduled_update' );

			function fbc_scheduled_update( $schedules ) {
				// Add custom intervals: See http://codex.wordpress.org/Plugin_API/Filter_Reference/cron_schedules
					$schedules = array(
						'every_day' => array(
								'interval'  => 86400,
								'display'   => __( 'Every Day' )
						),
						'every_week' => array(
								'interval'  => 604800,
								'display'   => __( 'Once a Week' )
						),
						'every_month' => array(
								'interval'  => 2592000,
								'display'   => __( 'Once a Month' )
						)
					);

					return $schedules;
			}

			if(!wp_next_scheduled( 'fbc_scheduled_update' )) {
				wp_schedule_event(get_option('fbc_cron_start'), get_option('fbc_schedule'), 'fbc_scheduled_update' );
			}
			//add_action('fbc_scheduled_update', 'fbc_scheduler');
			add_action( 'fbc_scheduled_update', array($this,'fbc_scheduler') );
		}




		// Load API for generic admin functions
		if ( is_admin() ) {
			$this->admin = new Canto_Admin_API();
		}

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		// Add Ajax functions
		add_action( 'wp_ajax_fbc_get_token', array( $this, 'getToken' ) );
		//add_action( 'wp_ajax_fbc_refresh_token', array( $this, 'refreshToken' ) );
		add_action( 'wp_ajax_fbc_getMetadata', array( $this, 'getMetadata' ) );

		add_action( 'wp_ajax_updateOptions', array( $this, 'updateOptions' ) );

	} // End __construct ()

	/**
	 * CURL function to query Flight API
	 *
	 * @param  string $url Full Flight API query string
	 * @param  string $header Flight API token authorization
	 * @param  string $agent Standard browser agent for CURL requests
	 * @param  int $echo True/False (1/0) for including CURL header in output
	 *
	 * @return object                CURL response output
	 */
	//public function curl_action($url,$header,$agent,$echo) {
	public function curl_action( $url, $echo ) {

		$header = array( 'Authorization: Bearer ' . get_option('fbc_app_token') );
		$ch = curl_init();
		$options = array(
			CURLOPT_URL            => $url,
			CURLOPT_REFERER        => "Wordpress Plugin",
			CURLOPT_USERAGENT      => "WordPress Plugin",
			CURLOPT_HTTPHEADER     => $header,
			//CURLOPT_SSLVERSION     => 3,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_HEADER         => $echo,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_TIMEOUT        => 10,
		);

		curl_setopt_array( $ch, $options );
		$output = curl_exec( $ch );
		curl_close( $ch );

		return $output;
	}


	/**
	 * WP CRON CUSTOM SCHEDULED TASK
	 * @access  public
	 * @since   2.0.0
	 * @return  void
	 */
	public function fbc_scheduler() {
			if(!function_exists('download_url')) {
				require_once(ABSPATH.'wp-admin/includes/file.php');
			}
			if(!function_exists('wp_generate_attachment_metadata')) {
				require_once(ABSPATH.'wp-admin/includes/image.php');
			}

			$args = array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				'meta_query'  => array(
					array(
						'key'     => 'fbc_id'
					)
				)
			);
			$query = new WP_Query($args);
			$posts = $query->posts;
			foreach($posts as $p) {
				$fbc_id = get_post_meta($p->ID,'fbc_id','true');
				$scheme = get_post_meta($p->ID,'fbc_scheme','true');
				$id = $p->ID;


				//Go get the media item from Flight
				$flight['api_url']  = 'https://' . get_option('fbc_flight_domain') . '.canto.com/api/v1/';
				$flight['req']      = $flight['api_url'] . $scheme . '/' . $fbc_id;

				$response = $this->curl_action( $flight['req'], 0 );
				$response = ( json_decode( $response ) );

				//Get the download url
				$detail = $response->url->download;
				$detail = $this->curl_action( $detail, 1 );

				$matches = array();
				preg_match( '/(Location:|URI:)(.*?)[\n\r]/', $detail, $matches );
				$uri = str_replace( array("Location: "), "", $matches[0] );
				$location = trim( $uri );


				$tmp        = download_url( $location );
				$file_array = array(
					'name'     => $response->name,
					'tmp_name' => $tmp
				);

				$guid = explode("/",$p->guid);
				$file = array_pop($guid);
				$meta = wp_get_attachment_metadata( $id );
				$uploads = wp_upload_dir();
				$dir_path = $uploads['path'];
				$file_sub = $uploads['subdir'];
				$file_perms = fileperms($dir_path.'/'.$file) & 0777;


				if (file_exists($dir_path.'/'.$file))
			  	unlink($dir_path.'/'.$file);


				if(is_array($meta)) {
					foreach($meta["sizes"] as $size) {
						$fileName = $size["file"];
						// Create array with all old sizes for replacing in posts later
						$oldfilesAr[] = $thisfile;
						// Look for files and delete them
						if (strlen($fileName)) {
							$fp = $dir_path.'/'.$size["file"];
							if (file_exists($fp)) {
								unlink($fp);
							}
						}
					}
				}

				// Move new file to old location/name; set permissions
				$copy = copy($tmp,$dir_path.'/'.$file);
				@chmod($dir_path.'/'.$file, $file_perms);


				// Make thumb and/or update metadata
				wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $dir_path.'/'.$file ) );
				update_attached_file( $id, $dir_path.'/'.$file);
				if(file_exists($file_array['tmp_name']))
					unlink($file_array['tmp_name']);

				 //file_put_contents(FBC_PATH."/cron_log.txt", "\n".date("Y-m-d h:i:sa",time())." - ".$fbc_id." - ".$file, FILE_APPEND | LOCK_EX);

			}
	}

	public function getMetaData( $fbc_id ) {
		check_ajax_referer( 'canto', 'nonce' );

		if ( empty ( $fbc_id ) ) {
			$fbc_id = stripslashes( htmlspecialchars( $_POST['fbc_id'] ) );
		} else {
			echo "Passsed by value: ";
			$fbc_id = stripslashes( htmlspecialchars( $fbc_id ) );
		}

		$flight['api_url'] = 'https://' . $this->fbc_flight_domain . '.canto.com/api/v1/';


//Get the metadata from the server to send off the the library form.
		$result = $this->curl_action( $flight['api_url'] . 'image/' . $fbc_id, 0 );

		//var_dump($result); wp_die();
		$result = json_decode( $result );


		//Build out the array
		$data = array(
			'id'         => $fbc_id,
			'name'       => $result->name,
			'dimensions' => $result->default->{'Dimensions'},
			'mime'       => $result->default->{'Content Type'},
			'size'       => size_format( $result->size ),
			'uploaded'   => $result->lastUploaded
		);

		echo json_encode( $data );
		wp_die();
	}

	/*
	 * Used to authenticate the Grant access to the app and get the token
	 */

	public function getToken() {
		//authenticate to OATUH -- Need to save the Session Cookie from Set Cookie

		$req = "https://oauth.canto.com:8443/oauth/rest/oauth2/authenticate";
		$postfields = "tenant=" . $this->fbc_flight_domain . '.canto.com&user=' . $this->fbc_flight_username;
		$postfields .= '&password=' . $this->fbc_flight_password;


		if ( ! function_exists( 'curl_init' ) ) {
			die( 'Sorry cURL is not installed!' );
		}

		$ch = curl_init();

		$options = array(
			CURLOPT_URL            => $req,
			CURLOPT_REFERER        => get_bloginfo( 'url' ),
			CURLOPT_USERAGENT      => "Canto Wordpress Plugin",
			CURLOPT_HTTPHEADER     => array(),
			//CURLOPT_SSLVERSION     => 3,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_HEADER         => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_TIMEOUT        => 10,
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => $postfields,
		);

		curl_setopt_array( $ch, $options );
		$response = curl_exec( $ch );

//		var_dump(curl_error($ch));


		list( $httpheader ) = explode( "\r\n\r\n", $response);

		//Check to see if the user supplied proper credentials, return error
		$invalid_credentials = $matches = array();
		preg_match('/(.*?)401/',$httpheader, $invalid_credentials);
			if (count($invalid_credentials) > 0) { echo json_encode( array( 'error' => "Invalid Login Credentials")); wp_die(); }

		//The DAM Credentials are working
		preg_match( '/(Set-Cookie:)(.*?);.*\n/', $httpheader, $matches );
		$cookie = preg_replace( '/Set-Cookie: (.*?);.*/', '\\1', $matches[0], 1 );

		//Now we have the authorization cookie and we can proceed to get the authorization code


		$options[ CURLOPT_URL ] = "https://oauth.canto.com:8443/oauth/rest/oauth2/grant";
		$options[ CURLOPT_URL ] .= "?action=grant&response_type=code&app_id=" . $this->fbc_app_id ;//. "&app_secret=" . $this->fbc_app_secret;
		$options[ CURLOPT_COOKIE ] = $cookie;
		$options[ CURLOPT_POST ]   = false;
		unset( $options[ CURLOPT_POSTFIELDS ] );
		curl_setopt_array( $ch, $options );
		$response = curl_exec( $ch );

		//Check to see if the proper code/cookie are in place.

		$invalid_credentials = $matches = array();
		preg_match('/(.*?)400/',$httpheader, $invalid_credentials);
			if (count($invalid_credentials) > 0) { echo json_encode( array( 'error' => "Invalid AppID")); wp_die(); }

		//Now we have the header again which contains the location (aka the code);

		list( $httpheader ) = explode( "\r\n\r\n", $response, 2 );
		preg_match( '/Location:(.*?)\n/', $httpheader, $matches );
		$code = preg_replace( '/^.*code\=(.*?)&.*/', '\\1', $matches[0], 1 );
		//we have a DAM code! make the final request to get the token


		$options[ CURLOPT_URL ] = "https://oauth.canto.com:8443/oauth/api/oauth2/token";
		$options[ CURLOPT_URL ] .= "?app_id=" . $this->fbc_app_id . "&app_secret=" . $this->fbc_app_secret . "&grant_type=authorization_code&code=" . trim($code);
		$options[ CURLOPT_POST ]   = true;
		$options[ CURLOPT_HEADER ] = 0;
		curl_setopt_array( $ch, $options );
		$response = curl_exec( $ch );

		//no more curl! the json is stored in the response var
		curl_close( $ch );

		//now set the DAM Authentication tokens
		$response = json_decode( $response );
		update_option( 'fbc_app_token', $response->accessToken );
		update_option( 'fbc_app_refresh_token', $response->refreshToken );
		update_option( 'fbc_app_token_expire', time() + $response->expiresIn );
		update_option( 'fbc_app_refresh_token_expire', time() + ( 86400 * 365 ) );

		//var_dump($response);
	}

	/**
	 * Update options for plugin settings
	 * @access  public
	 * @since   2.0.0
	 * @return  void
	 */
	public function updateOptions() {
		update_option( 'fbc_duplicates', $_POST['duplicates'] );
		update_option( 'fbc_cron', $_POST['cron'] );

		if($_POST['cron'] == "true") {
			wp_clear_scheduled_hook('fbc_scheduled_update');
			update_option( 'fbc_schedule', $_POST['schedule'] );
			update_option( 'fbc_cron_time_day', $_POST['cron_time_day'] );
			update_option( 'fbc_cron_time_hour', $_POST['cron_time_hour'] );


			switch(get_option('fbc_schedule')){
				case 'every_day':
					$start = mktime($_POST['cron_time_hour'],0,0);
					break;
				case 'every_week':
				case 'every_month':
					$text = $_POST['cron_time_day'].' '.$_POST['cron_time_hour'].':00';
					$start = strtotime($text);
					break;
				default:
					$start = time();
					break;
			}
			update_option( 'fbc_cron_start', $start );



		} else {
			delete_option('fbc_schedule');
			delete_option('fbc_cron_start');
			wp_clear_scheduled_hook('fbc_scheduled_update');
		}
	}


	/**
	 * Refreshes the current token saved in options via the Refresh Token
	 */
	public function refreshToken() {
//		check_ajax_referer('canto-refresh-token', 'nonce');
		//Need to check if we have the tools needed to refresh the token
//		if ( ! get_option('fbc_app_secret') || ! get_option('fbc_flight_domain') || !get_option('fbc_app_id')){

		$req    = 'https://' . $this->fbc_flight_domain . '.canto.com:8443/oauth/api/oauth2/token';
		$header = 'app_id=' . $this->fbc_app_id . '&app_secret=' . $this->fbc_app_secret
		          . '&grant_type=refresh_token&refresh_token=' . $this->fbc_app_refresh_token;
		$agent  = "Canto Wordpress Plugin";
//var_dump($req.'?'.$header); wp_die();
		$response = $this->curl_action( $req . '?' . $header,
			array( 'Authorization: Bearer ' . $this->fbc_app_refresh_token ), $agent, 1 );
		$response = json_decode( $response );
		update_option( 'fbc_app_token', $response['accessToken'] );
		update_option( 'fbc_app_refresh_token', $response['refreshToken'] );
		update_option( 'fbc_app_expire_token', time() + $response['expiresIn'] );
//		}

//		echo "Fail";
	}


	/**
	 * Load frontend CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function enqueue_styles() {
		wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(),
			$this->_version );
		wp_enqueue_style( $this->_token . '-frontend' );
	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function enqueue_scripts() {
		wp_register_script( $this->_token . '-frontend',
			esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', array( 'jquery' ),
			$this->_version );
		wp_enqueue_script( $this->_token . '-frontend' );
	} // End enqueue_scripts ()

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles( $hook = '' ) {
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(),
			$this->_version );
		wp_enqueue_style( $this->_token . '-admin' );

		wp_enqueue_script( 'fbc_media_js', plugins_url() . '/canto/assets/js/admin.js' );

		$translation_array = array(
			'FBC_URL' 	=> FBC_URL,
			'FBC_PATH' 	=> FBC_PATH,
			'subdomain' => get_option( 'fbc_flight_domain' ),
			'token'		=> get_option( 'fbc_app_token' ),
			'limit'		=> 30,
			'start'		=> 0
		);


	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts( $hook = '' ) {
		wp_register_script( $this->_token . '-admin',
			esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ),
			$this->_version );
		wp_enqueue_script( $this->_token . '-admin' );
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation() {
		load_plugin_textdomain( 'canto', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain() {
		$domain = 'canto';

		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()

	/**
	 * Main Canto Instance
	 *
	 * Ensures only one instance of Canto is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Canto()
	 * @return Main Canto instance
	 */
	public static function instance( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}

		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install() {
		$this->_log_version_number();
	} // End install ()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number() {
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()

}
