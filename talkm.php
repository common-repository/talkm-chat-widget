<?php
/*
Plugin Name: TalkM Chat Widget
Plugin URI: https://www.talkm.com
Description: TalkM is a live chat system that helps build better customer experience by connecting business to customers instantly to better serve them.
Version: 1.0
Author: TalkM
Text Domain: talkm-to-live-chat
*/
/* We begin to Write this Plugin For TalkM Live Chat Integration Into Your Website*/
/* People Must purchase the plan from the Website www.talkm.com */

/*Setting Class For talkM */
if(!class_exists('TalkM_Settings')){

	class TalkM_Settings{
		
		const TALKM_WIDGET_TEENANT_VARIABLE = 'talkm-embed-widget-teenant-key';
		const TALKM_WIDGET_STATUS_VARIABLE = 'talkm-embed-widget-status-id';
		const TALKM_WIDGET_EXPIRE_VARIABLE = 'talkm-embed-widget-expire-id';
		const TALKM_VISIBILITY_OPTIONS = 'talkm-visibility-options';
		
		/* Few Field For Expiration purpose */
		const TALKM_WIDGET_COMPANY_VARIABLE = 'talkm-embed-widget-company-id';
		const TALKM_WIDGET_USERNAME_VARIABLE = 'talkm-embed-widget-username-id';
		const TALKM_WIDGET_PASSWORD_VARIABLE = 'talkm-embed-widget-password-id';

		
		public function __construct(){

			if(!get_option('talkm-visibility-options',false))
			{
			$visibility = array (
				'always_display_talkm' => 1,
				'exclude_url_talkm' => 1,
				'excluded_url_list_talkm' => '',
			);
			update_option( 'talkm-visibility-options', $visibility);
			}

			add_action('admin_init', array(&$this, 'talkm_admin_init'));
			add_action('admin_menu', array(&$this, 'talkm_add_menu'));
			add_action('wp_ajax_talkm_setwidget',  array(&$this, 'talkm_action_setwidget'));
			add_action('wp_ajax_nopriv_talkm_setwidget', array(&$this, 'talkm_action_setwidget'));
			
			add_action('wp_ajax_talkm_removewidget',  array(&$this, 'talkm_action_removewidget'));

			add_action('admin_enqueue_scripts', array($this,'talkm_settings_assets') );
			add_action( 'admin_notices', array($this,'talkm_admin_notice') );
		}

		public function talkm_settings_assets($hook)
		{
			if($hook != 'settings_page_talkm_plugin')
				return;

			wp_register_style( 'talkm_admin_style', plugins_url( 'assets/talkm.admin.css' , __FILE__ ) );
        	wp_enqueue_style( 'talkm_admin_style' );

        	wp_enqueue_script( 'talkm_admin_script', plugins_url( 'assets/talkm.admin.js' , __FILE__ ) );
        
		}

		public function talkm_admin_init(){
			register_setting( 'talkm_options', 'talkm-visibility-options', array(&$this,'talkm_validate_options') );
		}

		public function talkm_action_setwidget() {
			header('Content-Type: application/json');

			/* User Request For Authorization */
			$comapany_name = $_POST['talkm_Company_Name'];
			$talkusername = $_POST['talkm_username'];
			$talkpassword = $_POST['talkm_password'];

			if(empty($comapany_name) || empty($talkusername) || empty($talkpassword)){
				echo json_encode(array('success' => FALSE ,'error_description'=> 'Some Fields Are Blank' ));
				die(); //Because one of the field is blank
			}

			/* Three Step Auth */
			$firststepurl = "https://$comapany_name.talkm.com/oauth/token";
			$nextstepurl  = "https://$comapany_name.talkm.com/api/subscriptions";
			$laststepurl  = "https://$comapany_name.talkm.com/api";

			/*First Step Authorization*/
			/*Call OAuth API to get access token*/
			$client_id = 'web-prod-id';
			$client_secret = 'web-prod-secret';
			$tokenContent = "grant_type=password&username=$talkusername&password=$talkpassword";
			$authorization = base64_encode("$client_id:$client_secret");
			
			/* Setup Header for wp_remote_post */
			$headers = array(
					'Authorization'  => 'Basic ' . $authorization,
					'Content-Type'   => 'application/x-www-form-urlencoded'
				);
			/* Setup variable for wp_remote_post*/
			$post = array(
					'method' => 'POST',
					'headers' => $headers,
					'httpversion' => '1.0',
					'sslverify' => false,
					'body' => $tokenContent
				);
			$response_return = wp_remote_post($firststepurl,$post);
			if( is_wp_error( $response_return ) ) {
				return false; /* Bail early */
			}

			$response = trim( wp_remote_retrieve_body( $response_return ) );
			
			$token_array = json_decode($response, true);
			if(!empty($token_array['error'])){

			if($token_array["error_description"] =='Invalid Email address or password.' ){
				echo json_encode(array('success' => FALSE ,'error_description' =>$token_array["error_description"].'Please recover your account from https://www.talkm.com.'));
				}else{
					echo json_encode(array('success' => FALSE ,'error_description' =>$token_array["error_description"]));
					}				
			die();
				
			}
			$check_scope_of_user = preg_split('/\s+/', $token_array['scope']);
			
			/* Authorization Of User IS admin or not */
			if(!in_array("chat_setting_appearance",$check_scope_of_user) && !in_array("chat_setting_general",$check_scope_of_user) ){	
			echo json_encode(array('success' => FALSE ,'error_description' =>"Sorry you don't have permissions."));	
			die();	
			}	
			
			/*Second Step Authorization*/
			/*Get Tenant Status. Show “Active” when expired is false. “Expired” when expired is true*/
			$headers = array(
				'Authorization'  => 'Bearer ' . $token_array["access_token"],
				'Content-Type'   => 'application/json'
			);

			$post = array(
				'method' => 'GET',
				'headers' => $headers,
				'httpversion' => '1.0',
				'sslverify' => false,
			);
			
			$request = wp_remote_get( $nextstepurl ,$post);

			if( is_wp_error( $request ) ) {
				return false; /* Bail early */
			}

			$return = wp_remote_retrieve_body( $request );
			$expiration_array = json_decode($return, true);
			/* print_r($expiration_array['freeTrial']);
			   print_r($expiration_array['status']);
			   print_r($expiration_array['expired']);
			*/
			
			/* Third Step Authorization*/
			/* Get Tenant Key. */
			
			$headers_last = array(
				'Authorization'  => 'Bearer ' . $token_array["access_token"],
				'Content-Type'   => 'application/json'
			);

			$post = array(
				'method' => 'GET',
				'headers' => $headers_last,
				'httpversion' => '1.0',
				'sslverify' => false,
			);
			
			$request = wp_remote_get( $laststepurl ,$post);

			if( is_wp_error( $request ) ) {
				return false; /* Bail early */
			}

			$return_last = wp_remote_retrieve_body( $request );
			$last_array = json_decode($return_last, true);

			update_option(self::TALKM_WIDGET_TEENANT_VARIABLE,	$last_array['tenantKey']);
			update_option(self::TALKM_WIDGET_STATUS_VARIABLE, $expiration_array['status']);
			update_option(self::TALKM_WIDGET_EXPIRE_VARIABLE, $expiration_array['endDate']);
			update_option(self::TALKM_WIDGET_COMPANY_VARIABLE, $comapany_name);
			update_option(self::TALKM_WIDGET_USERNAME_VARIABLE, $talkusername);
			update_option(self::TALKM_WIDGET_PASSWORD_VARIABLE, $talkpassword);

			echo json_encode(array('success' => TRUE,'status'=>'Connected Successfully'));
			die();
		}

		function talkm_admin_notice() {

		   	if( isset($_GET["settings-updated"]) ) 
		   	{
			    ?>
			    <div class="notice notice-warning is-dismissible">
			        <p><?php _e( 'You might need to clear cache if your using a cache plugin to see your updates', 'talkm-to-live-chat' ); ?></p>
			    </div>
			    <?php
			}
		}

		public function talkm_action_removewidget() {
			header('Content-Type: application/json');

			update_option(self::TALKM_WIDGET_TEENANT_VARIABLE, '');
			update_option(self::TALKM_WIDGET_STATUS_VARIABLE, '');
			update_option(self::TALKM_WIDGET_EXPIRE_VARIABLE, '');
			update_option(self::TALKM_WIDGET_COMPANY_VARIABLE, '');
			update_option(self::TALKM_WIDGET_USERNAME_VARIABLE, '');
			update_option(self::TALKM_WIDGET_PASSWORD_VARIABLE, '');
			
			echo json_encode(array('success' => TRUE,'Status'=>'Disconnected Successfully'));
			die();
		}
		
		public function talkm_expiration_remove() {
		    update_option(self::TALKM_WIDGET_TEENANT_VARIABLE, '');
			update_option(self::TALKM_WIDGET_STATUS_VARIABLE, '');
			update_option(self::TALKM_WIDGET_EXPIRE_VARIABLE, '');
			update_option(self::TALKM_WIDGET_COMPANY_VARIABLE, '');
			update_option(self::TALKM_WIDGET_USERNAME_VARIABLE, '');
			update_option(self::TALKM_WIDGET_PASSWORD_VARIABLE, '');	
		}
		
		public function talkm_validate_options($input){

			$input['always_display_talkm'] = ($input['always_display_talkm'] != '1')? 0 : 1;
			$input['exclude_url_talkm'] = ($input['exclude_url_talkm'] != '1')? 0 : 1;
			$input['excluded_url_list_talkm'] = sanitize_text_field($input['excluded_url_list_talkm']);
			return $input;
		}

		public function talkm_add_menu(){
			add_options_page(
				__('TalkM Settings','talkm-to-live-chat'),
				__('TalkM','talkm-to-live-chat'),
				'manage_options',
				'talkm_plugin',
				array(&$this, 'create_plugin_settings_page_talkm')
			);
		}

		public function create_plugin_settings_page_talkm(){

			global $wpdb;

			if(!current_user_can('manage_options'))	{
				wp_die(__('You do not have sufficient permissions to access this page.'));
			}
			
			include(sprintf("%s/talkm_templates/talkm_settings.php", dirname(__FILE__)));
		}
		
	}
}
/* Class For talkM */
if(!class_exists('talkm')){
	class talkm{
	
	    private $talkm_scriptUrl = '.talkm.org/embedded-chat';
		public function __construct(){
			$TalkM_Settings = new TalkM_Settings();
			/* add_shortcode( 'talkm', array($this,'shortcode_print_embed_code_talkm') ); */
		}
		/* Here We Activate The Plugin */
		public static function activate_talkm(){
			$visibility = array (
				'always_display_talkm' => 1,
				'exclude_url_talkm' => 1,
				'excluded_url_list_talkm' => '',
			);
			add_option(TalkM_Settings::TALKM_WIDGET_TEENANT_VARIABLE, '', '', 'yes');
			add_option(TalkM_Settings::TALKM_WIDGET_STATUS_VARIABLE, '', '', 'yes');
			add_option(TalkM_Settings::TALKM_WIDGET_EXPIRE_VARIABLE, '', '', 'yes');
			add_option(TalkM_Settings::TALKM_VISIBILITY_OPTIONS, $visibility, '', 'yes');
			add_option(TalkM_Settings::TALKM_WIDGET_COMPANY_VARIABLE, '', '', 'yes');
			add_option(TalkM_Settings::TALKM_WIDGET_USERNAME_VARIABLE, '', '', 'yes');
			add_option(TalkM_Settings::TALKM_WIDGET_PASSWORD_VARIABLE, '', '', 'yes');
			
			if( !wp_next_scheduled( 'talkm_add_every_five_minutes_event' ) ){
				wp_schedule_event( time(), 'talkm_every_five_minutes', 'talkm_add_every_five_minutes_event' );
			}

		}
		/* Here We Deactivate The Plugin */
		public static function deactivate_talkm(){
			delete_option(TalkM_Settings::TALKM_WIDGET_TEENANT_VARIABLE);
			delete_option(TalkM_Settings::TALKM_WIDGET_STATUS_VARIABLE);
			delete_option(TalkM_Settings::TALKM_WIDGET_EXPIRE_VARIABLE);
			delete_option(TalkM_Settings::TALKM_VISIBILITY_OPTIONS);
			delete_option(TalkM_Settings::TALKM_WIDGET_COMPANY_VARIABLE);
			delete_option(TalkM_Settings::TALKM_WIDGET_USERNAME_VARIABLE);
			delete_option(TalkM_Settings::TALKM_WIDGET_PASSWORD_VARIABLE);
		
			if( wp_next_scheduled( 'talkm_add_every_five_minutes_event' ) ){
				wp_clear_scheduled_hook( 'talkm_add_every_five_minutes_event' );
			}
			
		}

		/* public function shortcode_print_embed_code_talkm(){
			add_action('wp_footer',  array($this, 'embed_code_of_talkm'),100);
		} */
		
		public function embed_code_of_talkm()
		{
			$teenant_key =  get_option('talkm-embed-widget-teenant-key');
			
			if(!empty($teenant_key))
			{
				wp_enqueue_script('talkm-chat','https://'.$teenant_key.$this->talkm_scriptUrl, array( 'jquery' ),'',true);
			}
		}

		public function print_embed_code_of_talkm()
		{
			$vsibility = get_option( 'talkm-visibility-options' );
			
			$display = FALSE;

			if($vsibility['always_display_talkm'] == 1){ $display = TRUE; }
			if(($vsibility['exclude_url_talkm'] == 1)){
				$excluded_url_list_talkm = $vsibility['excluded_url_list_talkm'];

				$current_url = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
				$current_url = urldecode($current_url);

				$ssl      = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' );
			    $sp       = strtolower( $_SERVER['SERVER_PROTOCOL'] );
			    $protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );

			    $current_url = $protocol.'://'.$current_url;
			    $current_url = strtolower($current_url);
				$excluded_url_list_talkm = preg_split('/\s+/', $excluded_url_list_talkm);
				/* $excluded_url_list_talkm = preg_split("/,/", $excluded_url_list_talkm);*/
				foreach($excluded_url_list_talkm as $exclude_url_talkm)
				{
				 $exclude_url_talkm = strtolower(urldecode(trim($exclude_url_talkm)));
					if(!empty($exclude_url_talkm))
					{						
						if (strpos($current_url, $exclude_url_talkm) !== false) 
						{
							if(strcmp($current_url, $exclude_url_talkm) === 0)
							{
								$display = false;
							}
						}
					}
				}
			}
			if($display == TRUE)
			{
				$this->embed_code_of_talkm();
			}
		}
		
		/* The schedule filter hook */
		public function talkm_add_every_five_minutes( $schedules ) {
			$schedules['talkm_every_five_minutes'] = array(
				'interval'  => 300,
				'display'   => __( 'TalkM Five Minutes', 'talkm-to-live-chat' )
			);
			return $schedules;
		}

		/* The WP TalkM Cron event callback function */
		public function talkm_every_five_minutes_event_func() { 
		
			$expiration_date = get_option('talkm-embed-widget-expire-id');	
			$teenant_key =  get_option('talkm-embed-widget-teenant-key');
			$comapany_name = get_option('talkm-embed-widget-company-id');
			$talkusername = get_option('talkm-embed-widget-username-id');
			$talkpassword = get_option('talkm-embed-widget-password-id');
			$TalkM_Settings = new TalkM_Settings();
			/* $current_time = current_time('G:i'); */
			/* if(strtotime($current_time) > strtotime($expiration_date) && !empty($teenant_key) ) */
					if(!empty($teenant_key) ){
						/* Three Step Auth */
						$firststepurl = "https://$comapany_name.talkm.com/oauth/token";
						$nextstepurl  = "https://$comapany_name.talkm.com/api/subscriptions";
						$laststepurl  = "https://$comapany_name.talkm.com/api";

						/*First Step Authorization*/
						/*Call OAuth API to get access token*/
						$client_id = 'web-prod-id';
						$client_secret = 'web-prod-secret';
						$tokenContent = "grant_type=password&username=$talkusername&password=$talkpassword";
						$authorization = base64_encode("$client_id:$client_secret");
						
						
						/* Setup Header for wp_remote_post */
						$headers = array(
								'Authorization'  => 'Basic ' . $authorization,
								'Content-Type'   => 'application/x-www-form-urlencoded'
							);
						/* Setup variable for wp_remote_post */
						$post = array(
								'method' => 'POST',
								'headers' => $headers,
								'httpversion' => '1.0',
								'sslverify' => false,
								'body' => $tokenContent
							);
						$response_return = wp_remote_post($firststepurl,$post);
						if( is_wp_error( $response_return ) ) {
							return false; /* Bail early */
						}

						$response = trim( wp_remote_retrieve_body( $response_return ) );
						$token_array = json_decode($response, true);
						
						$check_scope_of_user = preg_split('/\s+/', $token_array['scope']);
			
						/* Authorization Of User IS admin or not */
						if(!in_array("chat_setting_appearance",$check_scope_of_user) && !in_array("chat_setting_general",$check_scope_of_user) ){	
								$TalkM_Settings->talkm_expiration_remove();
						die();	
						}
						
						/*Second Step Authorization*/
						/*Get Tenant Status. Show “Active” when expired is false. “Expired” when expired is true*/
						
						
					$headers = array(
						'Authorization'  => 'Bearer ' . $token_array["access_token"],
						'Content-Type'   => 'application/json'
					);

					$post = array(
						'method' => 'GET',
						'headers' => $headers,
						'httpversion' => '1.0',
						'sslverify' => false,
					);
			
					$request = wp_remote_get( $nextstepurl ,$post);

					if( is_wp_error( $request ) ) {
						return false; /* Bail early */
					}

					$return = wp_remote_retrieve_body( $request );
					$expiration_array = json_decode($return, true);	
	
							if($expiration_array['expired'] == TRUE){
								
							   $TalkM_Settings->talkm_expiration_remove();
								
							}else{
								
								update_option(TalkM_Settings::TALKM_WIDGET_EXPIRE_VARIABLE, $expiration_array['endDate']);
							}
							
					}
			}
	
	}
}

if(class_exists('TalkM')){
	register_activation_hook(__FILE__, array('TalkM', 'activate_talkm'));
	register_deactivation_hook(__FILE__, array('TalkM', 'deactivate_talkm'));

	$talkm = new TalkM();

	if(isset($talkm)){
		function talkm_plugin_settings_link($links){
			$settings_link = '<a href="options-general.php?page=talkm_plugin">Settings</a>';
			array_unshift($links, $settings_link);
			return $links;
		}
		
		$plugin = plugin_basename(__FILE__);
		add_filter("plugin_action_links_$plugin", 'talkm_plugin_settings_link');
	}
	/* Expiration Check After a certain Time Period 
	add_action('wp_footer',  array($talkm, 'check_expiration_embed_code_of_talkm')); */
	/*add_action('wp_footer',  array($talkm, 'print_embed_code_of_talkm'));*/
	
	/* Print In Footer If Not Expired */
	add_action( 'wp_enqueue_scripts', array($talkm, 'print_embed_code_of_talkm'));
	
	
	/* This is Cron For authentaction - Expiration Check After a certain Time Period */
	add_filter( 'cron_schedules', array($talkm, 'talkm_add_every_five_minutes'));
	add_action( 'talkm_add_every_five_minutes_event',array($talkm, 'talkm_every_five_minutes_event_func' ));

}