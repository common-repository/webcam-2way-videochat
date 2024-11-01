<?php

namespace VideoWhisper\VideoCalls;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait H5Videochat {
	
	static function videowhisper_videochat_filters($atts)
	{
			//Shortcode: Load Filters Form by AJAX
			$options = self::getOptions();

			// shortocode attributes
			$atts = shortcode_atts(
				array(
				'id'              => '',
				),
				$atts,
				'videowhisper_videochat_fiters'
			);

			$id = sanitize_text_field( $atts['id'] );
			if ( ! $id ) {
				$id = 1; // uniqid();
			}

		// semantic ui 
		self::enqueueUI();

		$genderOptions = $options['genders'];
		if (!count($genderOptions)) $genderOptions =[ 'Male', 'Female', 'Other' ]; //default

		[$gender, $country, $gender2, $country2] = self::getFilters();

		$countryOptions = self::countryOptions();

		// ajax url
		$ajaxurl = admin_url() . 'admin-ajax.php?action=vw_2w_filters' . '&id=' . esc_attr( $id ) ;

		$htmlCode = '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' tiny form" style="z-index: 150;"><div class="fields">';

		//$htmlCode .= "[$gender, $country, $gender2, $country2]";

		$htmlCode .= '<div class="field"> I am <select class="ui dropdown selection v-select vw2wfilter" id="gender" name="gender">';
		foreach ($genderOptions as $option) $htmlCode .= '<option value="' . esc_attr( $option ) . '" ' . ($option == $gender ? 'selected' : '' ) . '>' . esc_html( $option ) . '</option>';
		$htmlCode .=  '</select></div>';

		$htmlCode .= '<div class="field"> From <div class="ui dropdown selection v-select">';
		$htmlCode .= '<input class="vw2wfilter" type="hidden" id="country" name="country" value="' . $country . '">
  <i class="dropdown icon"></i>
  <div class="default text">Select Country</div>
  <div class="menu" >';
		foreach ($countryOptions as $flag => $option) $htmlCode .= '<div class="item" data-value="' . esc_attr( $option ) . '"><i class="' . strtolower( $flag ) . ' flag"></i> ' . esc_html( $option ) . '</div>';
		$htmlCode .=  '</div></div></div>';

		$htmlCode .= '<div class="field"> Looking for <div class="ui dropdown selection v-select" id="gender2" name="gender2">';
		$htmlCode .= '<input class="vw2wfilter" type="hidden" id="gender2" name="gender2" value="' . $gender2 . '">
		<i class="dropdown icon"></i>
		<div class="default text">Select Gender</div>
		<div class="menu">';
		$htmlCode .= '<div class="item" data-value="">' . 'Any' . '</div>';
		foreach ($genderOptions as $option) $htmlCode .= '<div class="item" data-value="' . esc_attr( $option ) . '">' . esc_html( $option ) . '</div>';
		$htmlCode .=  '</div></div></div>';

		$htmlCode .= '<div class="field">In <div class="ui dropdown selection v-select">';
		$htmlCode .= '<input class="vw2wfilter" type="hidden" id="country2" name="country2" value="' . $country2 . '">
  <i class="dropdown icon"></i>
  <div class="default text">Select Country</div>
  <div class="menu">';
  		$htmlCode .= '<div class="item" data-value="">' . 'Any Country' . '</div>';

		foreach ($countryOptions as $flag => $option) $htmlCode .= '<div class="item" data-value="' . esc_attr( $option ) . '"><i class="' . strtolower( $flag ) . ' flag"></i> ' . esc_html( $option ) . '</div>';
		$htmlCode .=  '</div></div></div>';

		$htmlCode .=  '</div></div>';

		$nonce = wp_create_nonce('vws') ;

		$htmlCode .= <<<HTMLCODE
		<PRE style="display: none">
		<SCRIPT language="JavaScript">	
		jQuery(document).ready(function()
		{

			jQuery(".ui.dropdown").dropdown();

			const select = jQuery(".vw2wfilter");

			 select.on("change", function() {
				// Create a data object with the data to be sent to the server
				const data = {
				  nonce: "$nonce",
				  name: typeof this.name != 'undefined' ? this.name : this.firstChild.name,
				  id:	typeof this.id != 'undefined' ? this.id : this.firstChild.id,
				  value:  typeof this.value != 'undefined' ? this.value : this.firstChild.value
				};
			  
				//console.log('2W Filter:',  this);

				// Send an AJAX request to the server
				jQuery.ajax({
				  url: "$ajaxurl",
				  type: "POST",
				  contentType: "application/x-www-form-urlencoded",
				  data: data,
				  success: function(response) {
					// Update the page as necessary
					console.log('2W Filter AJAX:', response);
				  }
				});
			  });

		});
		</SCRIPT>
		<STYLE>
		.vw2wfilter{
			min-width: 150px;
		}
		</STYLE>
		</PRE>
		HTMLCODE;

		return $htmlCode;
	}

	static function vw_2w_filters()
	{
		
		$options = self::getOptions();
		ob_clean();

		$nonce = $_POST['nonce'] ?? '';
		if ( ! wp_verify_nonce( $nonce, 'vws') )
		{
			echo json_encode(['error' => 'Invalid nonce.']);
			die;
		}

		if ( isset($_POST['name']) )
		{
			$name = sanitize_text_field( $_POST['name'] );
			if ( in_array($name, ['gender', 'country', 'gender2', 'country2']) ) 
			{

			$value = sanitize_text_field( $_POST['value'] );	

			//update user meta
			$current_user = wp_get_current_user();
			if ($current_user->ID) 
			{   
				//persistent meta for users
				update_user_meta($current_user->ID, 'v2w_' . $name, $value);
			} else 
			{
				//session for visitors
				session_start();
				$_SESSION['v2w_' . $name] = $value;
			};
			$result = [ 'updated' => $name, 'value'	=> $value ];
			} else $result = [ 'error'=>'Invalid filter!', 'post'=>$_POST ];

		}else $result = [ 'error'=>'Missing paraments!', 'post'=>$_POST ];

		echo json_encode($result);

		die();
	}

	static function getFilters()
	{
		$current_user = wp_get_current_user();
		if ($current_user->ID) 
		{   
			//meta for users
			$gender = get_user_meta($current_user->ID, 'v2w_gender', true);
			$gender2 = get_user_meta($current_user->ID, 'v2w_gender2', true);
			$country = get_user_meta($current_user->ID, 'v2w_country', true);
			$country2 = get_user_meta($current_user->ID, 'v2w_country2', true);

		} else 
		{
			//session for visitors
			session_start();
			$gender = $_SESSION['v2w_gender'] ?? '';
			$gender2 = $_SESSION['v2w_gender2'] ?? '';
			$country = $_SESSION['v2w_country'] ?? '';
			$country2 = $_SESSION['v2w_country2'] ?? '';
		};

		if (!$gender) $gender = 'Male';
		if (!$country) $country = self::detectCountry();
		if (!$country) $country = 'United States';
		
		return [ $gender, $country, $gender2, $country2 ];
	}

	static function detectCountry($ip = null)
	{
		if ( ! $ip ) $ip = self::get_ip_address();

		if ( function_exists( 'geoip_country_name_by_name' ) ) {
			return sanitize_text_field( geoip_country_name_by_name( $ip ) );
		}

		$country = sanitize_text_field( getenv( 'GEOIP_COUNTRY_NAME' ) ?? '' );

		return $country;
	}

	static function videowhisper_videochat_random($atts)
	{
			//Shortcode: Random Cam Videochat
			$options = self::getOptions();

			//current user	
			$current_user = wp_get_current_user();
			
				//access keys
				$userkeys = $current_user->roles;
				$userkeys[] = $current_user->user_login;
				$userkeys[] = $current_user->ID;
				$userkeys[] = $current_user->user_email;
				$userkeys[] = $current_user->display_name;

				self::enqueueUI();

				switch ($options['canWatch'])
				{
				case "all":
					$loggedin=1;
					break;
				case "members":
					if (!$current_user->ID) return __('<div class="ui message">Restricted to registered users, from backend settings!</div>');
					break;
				case "list";
						if ($current_user->ID) 
							if (self::inList($userkeys, $watchList)) $loggedin=1;
							else return __('<div class="ui message">You are not in the allowed users list!</div>','vw2wvc');
						else return __('<div class="ui message">Please login first or register an account if you do not have one!</div>', 'vw2wvc') ;
				break;
				}
			
			$userID = $current_user->ID;
			if ($userID == 0 )
			{
				//use a cookie for visitor username persistence, if possible
				if ($_COOKIE['htmlchat_username']) $userName = sanitize_file_name($_COOKIE['htmlchat_username']);
				else
				{
					$userName =  'G_' . base_convert(time()%36 * rand(0, 36*36), 10, 36);
					@setcookie('htmlchat_username', $userName);
				}
				
			}
			else $userName = $current_user->user_nicename;
			
				global $wpdb;
				$table_sessions = $wpdb->prefix . "vw_2w_sessions";

				//clean old sessions
				$exptime=time()-$options['sessionExpire'];
				$sql="DELETE FROM `$table_sessions` WHERE edate < $exptime";
				$wpdb->query($sql);
				
					
					$userMeta = array();
					$userMeta['createdBy'] = 'videochat_random';
					$userMetaS = esc_sql(serialize($userMeta));
					
					$ztime = time();
					$room = 'M' . $ztime;

					$ip =  self::get_ip_address();
					$sql="INSERT INTO `$table_sessions` ( `session`, `username`, `room`, `message`, `sdate`, `edate`, `status`, `type`, `uid`, `rid`, `ip`,`broadcaster`, `meta`) VALUES ('$userName', '$userName', '$room', '', $ztime, $ztime, 0, 2, $userID, 0, '$ip', 0, '$userMetaS')"; //rid 0 until finding next
					$wpdb->query($sql);

					$sessionID = $wpdb->insert_id;


		$nextRoom = self::nextRoomID($sessionID, $userName, $options);
		$nextRoomID = intval($nextRoom);

		if ($nextRoomID)
		{
		
		//update session room id	
		$wpdb->query("UPDATE `$table_sessions` SET rid = '$nextRoomID' WHERE id='$sessionID'");
		
		//self::autoMessage("👤$userName #$userID/$sessionID entered", $session);

		$ajaxurl = admin_url() . 'admin-ajax.php?action=vw_2w_app&match=1';

		$wlJS ='';
		if ($options['whitelabel']) $wlJS = ', checkWait: true, whitelabel: ' . $options['whitelabel'];

		$modeVersion = trim($options['modeVersion'] ?? '');

//embed the app: all integrations should contain this part
$dataCode = "window.VideoWhisper = {userID: $sessionID, sessionID: $sessionID, sessionKey: '$userID', roomID: $nextRoomID, performer: 0, serverURL: '" . $ajaxurl . "', modeVersion: '$modeVersion' $wlJS}";

$cssCode = html_entity_decode(stripslashes($options['appCSS']));

$bodyCode = <<<HTMLCODE
<!--VideoWhisper.com - HTML5 Videochat web app / Random Match - username:$userName uid:$sessionID r:$nextRoomID s:$sessionID-->
<noscript>You need to enable JavaScript to run this app. For more details see <a href="https://paidvideochat.com/html5-videochat/">HTML5 Videochat</a> or <a href="https://videowhisper.com">contact HTML5 videochat developers</a>.</noscript>
<div id="videowhisperAppContainer"><div id="videowhisperVideochat"></div></div>
<script>$dataCode;
document.cookie = "html5videochat=DoNotCache";
</script>
<style>

#videowhisperAppContainer
{
display: block;
min-height: 725px;
height: inherit;
background-color: #eee;
position: relative;
z-index: 102 !important;
}

#videowhisperVideochat
{
display: block;
width: 100%;
height: 100%;
position: absolute;
z-index: 102 !important;
}

$cssCode
</style>
HTMLCODE;



		wp_enqueue_style( 'semantic', dirname(plugin_dir_url(  __FILE__ )) . '/js/semantic/semantic.min.css');


		$CSSfiles = scandir(dirname(dirname(  __FILE__ )) . '/static/css/');
		$k = 0;
		
		foreach
		($CSSfiles as $filename)
			if (strpos($filename,'.css')&&!strpos($filename,'.css.map'))
				wp_enqueue_style( 'vw-call-app' . ++$k, dirname(plugin_dir_url(  __FILE__ )) . '/static/css/' . $filename);

			$countMain = 0;
		$countRuntime = 0;
		$JSfiles = scandir(dirname(dirname(  __FILE__ )) . '/static/js/');
		foreach ($JSfiles as $filename)
			if ( strpos($filename,'.js') && !strpos($filename,'.js.map')) // && !strstr($filename,'runtime~')
				{
				wp_enqueue_script('vw-call-app'. ++$k , dirname(plugin_dir_url(  __FILE__ )) . '/static/js/' . $filename, array(), '', true);

				if (!strstr($filename, 'LICENSE.txt')) if (substr($filename,0,5) == 'main.') $countMain++;
					if (!strstr($filename, 'LICENSE.txt')) if (substr($filename,0,7) =='runtime') $countRuntime++;
			}

		if ($countMain>1 || $countRuntime>1) $htmlCode .=   '<div class="ui segment red">Warning: Possible duplicate JS files in application folder! Only latest versions should be deployed.</div>';
		

		if ($options['matchmaking']) $bodyCode .= '<div class="ui segment fluid">' . do_shortcode('[videowhisper_videochat_filters]') . '<small>Select filters for next match.</small></div>';

		return $bodyCode;

		}
		else return 'Error: ' . $nextRoom;
	}
	
	static function nextRoomID($sessionID, $userName, $options = null)
	{
				
		if (!$options) $options = self::getOptions();
		$nextRoomID = 0; //no room found

		global $wpdb;
		$table_sessions = $wpdb->prefix . "vw_2w_sessions";
		$table_match = $wpdb->prefix . "vw_2wmatch"; //status: 0 waiting, 1 matched, 2 left
		
		$ztime = time();

		//clean old matches
		$exptime=$ztime-$options['sessionExpire'];
		$sql="DELETE FROM `$table_match` WHERE edate < $exptime";
		$wpdb->query($sql);
				
		//waiting rooms
		$matchingCoditions = '';
		[$gender, $country, $gender2, $country2] = ['', '', '', ''];
		
		if ( $options['matchmaking'] )
		{
			[$gender, $country, $gender2, $country2] = self::getFilters();
			$matchingCoditions = " AND ( `s1_gender2` = '$gender' OR `s1_gender2` = '' ) AND ( `s1_country2` = '$country' OR `s1_country2` = '' )"; //s2 matches s1 filters
			if ($gender2) $matchingCoditions .= " AND `s1_gender` = '$gender2'"; //current (s2) has gender filter
			if ($country2) $matchingCoditions .= " AND `s1_country` = '$country2'"; //current (s2) has country filter
		}

		//select room where s1 is waiting
		$sql = "SELECT * FROM $table_match WHERE status = 0 AND s2 = 0 AND s1 <> $sessionID AND s1u <> '$userName' $matchingCoditions ORDER BY edate DESC LIMIT 1";
		$room = $wpdb->get_row($sql);
		if ($wpdb->last_error !== '') return $wpdb->last_error . ( $options['debugMode']? " SQL: $sql" : '' );

		if ($room)	//match
		{
			$rid = $room->id;
			$rn = 'M' . $rid;
			
				//update watching and room for s1
				$sqlS = "SELECT * FROM `$table_sessions` WHERE id='" . $room->s1 . "' ORDER BY edate DESC LIMIT 0,1";
				$session = $wpdb->get_row($sqlS);
				if (!$session) return 'Error, not found: ' . $sqlS;
				$userMeta = unserialize($session->meta);
				if (!is_array($userMeta)) $userMeta = [];
				$userMeta['watch'] = $sessionID;
				$userMeta['watchConfirm'] = 0;
				$userMetaS = esc_sql(serialize($userMeta));
				$sql="UPDATE `$table_sessions` set meta='$userMetaS', rid= '$rid', room='$rn' where id ='" . $room->s1 . "'";
				$wpdb->query($sql);
	
				//update watching and room for s2
				$sqlS = "SELECT * FROM `$table_sessions` WHERE id='" . $sessionID . "' ORDER BY edate DESC LIMIT 0,1";
				$session = $wpdb->get_row($sqlS);
				if (!$session) return 'Error, not found: ' . $sqlS;
				$userMeta = unserialize($session->meta);
				if (!is_array($userMeta)) $userMeta = [];
				$userMeta['watch'] = $room->s1;
				$userMeta['watchConfirm'] = 0;				
				$userMetaS = esc_sql(serialize($userMeta));
				$sql="UPDATE `$table_sessions` set meta='$userMetaS', rid= '$rid', room='$rn'  where id ='" . $sessionID . "'";
				$wpdb->query($sql);
				
				//update match room
				$sql="UPDATE `$table_match` SET s2='$sessionID', status='1', edate='$ztime' where id ='" . $room->id . "'";
				$wpdb->query($sql);

				return $room->id;			
		};
		
		//no waiting room, create one
		$sql = "INSERT INTO `$table_match` ( `s1`, `s1u`, `s2`, `sdate`, `edate`, `status`, `s1_gender`, `s1_country`, `s1_gender2`, `s1_country2` ) VALUES ('$sessionID', '$userName', '0','$ztime', '$ztime', '0', '$gender', '$country', '$gender2', '$country2')";
		$wpdb->query($sql);
		$rid = $wpdb->insert_id;
		$rn = 'M' . $rid;
		
		if ($wpdb->last_error !== '') return $wpdb->last_error . ( $options['debugMode']? " SQL: $sql" : '' );
		
		//update session with new room
		$sql="UPDATE `$table_sessions` SET rid= '$rid', room='$rn' where id ='" . $sessionID . "'";
		$wpdb->query($sql);


		//room id
		return $rid;
	}
		
	/**
		 * Retrieves the best guess of the client's actual IP address.
		 * Takes into account numerous HTTP proxy headers due to variations
		 * in how different ISPs handle IP addresses in headers between hops.
		 */
		static function get_ip_address() {
			$ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
			foreach ($ip_keys as $key) {
				if (array_key_exists($key, $_SERVER) === true) {
					foreach (explode(',', $_SERVER[$key]) as $ip) {
						// trim for safety measures
						$ip = trim($ip);
						// attempt to validate IP
						if (self::validate_ip($ip)) {
							return $ip;
						}
					}
				}
			}

			return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
		}

		/**
		 * Ensures an ip address is both a valid IP and does not fall within
		 * a private network range.
		 */
		static function validate_ip($ip)
		{
			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
				return false;
			}
			return true;
		}
	
	
	
		static function appSfx()
	{
		//sound effects sources

		$base = dirname(plugin_dir_url(__FILE__)) . '/sounds/';


		return array(
			'message' => $base . 'message.mp3',
			'hello' => $base . 'hello.mp3',
			'leave' => $base . 'leave.mp3',
			'call' => $base . 'call.mp3',
			'warning' => $base . 'warning.mp3',
			'error' => $base . 'error.mp3',
			'buzz' => $base . 'buzz.mp3',
		);
	}


	static function appText()
	{
		//implement translations

		//returns texts
		return array(
			'Send' => __('Send', 'vw2wvc'),
			'Type your message' => __('Type your message', 'vw2wvc'),

			'Chat' => __('Chat', 'vw2wvc'),
			'Camera' => __('Camera', 'vw2wvc'),
			'Users' => __('Users', 'vw2wvc'),
			'Options' => __('Options', 'vw2wvc'),
			'Files' => __('Files', 'vw2wvc'),
			'Presentation' => __('Presentation', 'vw2wvc'),

			'Tap for Sound' => __('Tap for Sound', 'vw2wvc'),
			'Enable Audio' => __('Enable Audio', 'vw2wvc'),
			'Mute' => __('Mute', 'vw2wvc'),
			'Reload' => __('Reload', 'vw2wvc'),

			'Packet Loss: Download Connection Issue' => __('Packet Loss: Download Connection Issue', 'vw2wvc'),
			'Packet Loss: Upload Connection Issue'  => __('Packet Loss: Upload Connection Issue', 'vw2wvc'),

			'Broadcast' => __('Broadcast', 'vw2wvc'),
			'Stop Broadcast' => __('Stop Broadcast', 'vw2wvc'),
			'Make a selection to start!' => __('Make a selection to start!', 'vw2wvc'),

			'Gift' => __('Gift', 'vw2wvc'),
			'Gifts' => __('Gifts', 'vw2wvc'),

			'Lights On' => __('Lights On', 'vw2wvc'),
			'Dark Mode' => __('Dark Mode', 'vw2wvc'),
			'Enter Fullscreen' => __('Enter Fullscreen', 'vw2wvc'),
			'Exit Fullscreen' => __('Exit Fullscreen', 'vw2wvc'),

			'Site Menu' => __('Site Menu', 'vw2wvc'),

			'Next' => __('Next', 'vw2wvc'),
			'Next: Random Videochat Room' => __('Next: Random Videochat Room', 'vw2wvc'),

			'Profile' => __('Profile', 'vw2wvc'),
			'Show' => __('Show', 'vw2wvc'),

			'Private Call' => __('Private Call', 'vw2wvc'),
			'Exit' => __('Exit', 'vw2wvc'),

		);
	}


	static function is_true($val, $return_null=false)
	{
		$boolval = ( is_string($val) ? filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : (bool) $val );
		return $boolval===null && !$return_null ? false : $boolval;
	}


	static function notificationMessage($message, $session, $privateUID = 0, $meta = null)
	{
		//adds a notification from server, only visible to user

		$ztime = time();

		global $wpdb;
		$table_chatlog = $wpdb->prefix . "vw_2w_chatlog";

		if (!$meta) $meta = array();
		$metaS = esc_sql(serialize($meta));

		$sql="INSERT INTO `$table_chatlog` ( `username`, `room`, `room_id`, `message`, `mdate`, `type`, `user_id`, `meta`, `private_uid`) VALUES ('" .$session->username. "', '" .$session->room. "', '" .$session->rid. "', '$message', $ztime, '3', '" .$session->uid. "', '$metaS', '$privateUID')";
		$wpdb->query($sql);

		//todo maybe: also update chat log file

		return $sql;
	}


	static function autoMessage($message, $session, $privateUID = 0, $meta = null)
	{
		//adds automated user message from server, automatically generated by user action

		$ztime = time();

		global $wpdb;
		$table_chatlog = $wpdb->prefix . "vw_2w_chatlog";

		if (!$meta) $meta = array();
		$meta['automated'] = true;
		$metaS = esc_sql(serialize($meta));

		$message = esc_sql($message);

		if (!$session) $session = (object) array('username' => 'System', 'room' => 'System', 'rid' => 0, 'uid' => get_current_user_id() );

		$sql="INSERT INTO `$table_chatlog` ( `username`, `room`, `room_id`, `message`, `mdate`, `type`, `user_id`, `meta`, `private_uid`) VALUES ('" .$session->username. "', '" .$session->room. "', '" .$session->rid. "', '$message', $ztime, '2', '" .$session->uid . "', '$metaS', '$privateUID')";
		$wpdb->query($sql);

		return $sql;
	}


	//! WebRTC
		static function webrtcStreamQuery($userID, $postID, $broadcaster, $stream_webrtc, $options = null, $transcoding = 0, $room ='', $privateUID = 0)
		{

			if (!$options) $options = get_option('VWvideoChatOptions');
			$clientIP = self::get_ip_address();

			if (!$room) $room = $stream_webrtc; //same as stream name

			if ($broadcaster)
			{
				$key = md5('vw' . $options['webKey'] . $userID . $postID);

			}
			else
			{
				$key = md5('vw' . $options['webKey']. $postID );
			}

			$streamQuery = sanitize_file_name($stream_webrtc) . '?channel_id=' . intval($postID) . '&userID=' . urlencode(intval($userID)) . '&key=' . urlencode($key) . '&ip=' . urlencode($clientIP) . '&transcoding=' . $transcoding . '&room=' . urlencode(sanitize_file_name($room)). '&privateUID=' . intval($privateUID);
			return $streamQuery;

		}


	static function appRoom($rm, $session, $options, $match = 0,  $welcome = '')
	{
		//private call room parameters, specific for this user

		$room = array();

		
		$room['ID'] = $rm->id;

		if (!$options) $options = self::getOptions();
		
		$room['serverType'] = $options['serverType'] ?? 'videowhisper'; //wowza/videowhisper

		if ($session->meta) $userMeta = unserialize($session->meta);
		if (!is_array($userMeta)) $userMeta = array();

		if (!array_key_exists('watch', $userMeta)) 
		{
		  if ($match) $watch = 0;
		  if ($session->broadcaster) $watch = $rm->client;
		  else $watch = $rm->owner ?? 0;	  
		}
		else $watch = $userMeta['watch'];
		
		$room['screen'] = 'Way2Screen'; //private 2 way video screen
		if ($room['audioOnly'] ?? false ) $room['screen'] = 'Way2AudioScreen'; //2 way audio only mode
		if ($room['textOnly'] ?? false ) $room['screen'] = 'TextScreen'; //text only mode

		$room['privateUID'] = 0;

		$room['welcomeImage'] = dirname(plugin_dir_url(__FILE__)) . '/images/users2.png';

		if (!$match)
		{
		$room['welcome'] = sprintf('Welcome to private booth "%s", %s!', sanitize_file_name($rm->name), $session->username) ;
		if ($session->broadcaster) $room['welcome'] .= "\n".__('You are room owner.', 'vw2wvc');
		if ($room['audioOnly']) $room['welcome'] .= "\n" . __('Chat Type', 'vw2wvc') . ': ' . __('Audio chat', 'vw2wvc');
		if ($room['textOnly']) $room['welcome'] .= "\n" . __('Chat Type', 'vw2wvc') . ': ' . __('Text chat', 'vw2wvc');
		}
		else 
		{
					global $wpdb;
					$table_sessions = $wpdb->prefix . "vw_2w_sessions";
					
			$room['welcome'] = sprintf('Welcome to this random match booth %d:%d-%d, %s #%d!', $rm->id, $rm->s1, $rm->s2, $session->username, $session->id);
			
			if ( $options['matchmaking'] )
			{
				[ $gender, $country, $gender2, $country2 ] = self::getFilters();
				if ($gender || $country) $room['welcome'] .= "\n" . __('Matchmaking', 'vw2wvc') .': ' . sprintf('You are %s from %s looking for %s from %s.', $gender, $country, $gender2 ? $gender2 : 'any user', $country2 ? $country2 :'any location');
			}

			if ($rm->s1 == $session->id && !$rm->s2 ) $room['welcome'] .= "\n " .  __('⏳ No waiting user was found. You are waiting for a new user to enter.', 'ppv-live-webcams');
			//otherwise it can be s1 or s2 in match

		}
		//$room['welcome'] .= "\n Meta: " . $pSession->meta;

		if ($match)
		{
			$streamBroadcast = 'M' . $rm->id . '_' . $session->id;
			$streamPlayback = 'M' . $rm->id  . '_' . $watch;
			
			
			$room['streamBroadcast'] = self::webrtcStreamQuery($session->id, $rm->id, 1, $streamBroadcast, $options, 0, 'M' . $rm->id);
			$room['streamPlayback'] = self::webrtcStreamQuery($session->id, $rm->id, 0, $streamPlayback, $options, 0, 'M' . $rm->id);
			
			//	$room['welcome'] .= "\n out:$streamBroadcast in:$streamPlayback";
			//	$room['welcome'] .= "\n StreamPlayback: " . $room['streamPlayback'];

		}
		else
		{
		//special private streams based on user id
		$streamBroadcast = sanitize_file_name($rm->name) . '_' . ($session->broadcaster ? $session->uid : $session->id);
		$streamPlayback = sanitize_file_name($rm->name) . '_' . $watch;
		
		$room['streamBroadcast'] = self::webrtcStreamQuery($session->uid, $rm->id, 1, $streamBroadcast, $options, 0, $rm->name);
		$room['streamPlayback'] = self::webrtcStreamQuery($session->uid, $rm->id, 0, $streamPlayback, $options, 0, $rm->name);
		}

		//in 2w always receive broadcast keys

		$room['streamUID'] = intval($watch);
		
		$room['actionPrivate'] = false;
		$room['actionPrivateClose'] = false;

		$room['actionID'] = $actionID ?? 0;
		//$room['welcome'] .= "\n--- " . serialize($room);

		//$other = get_userdata($privateUID);

		//custom buttons
		$actionButtons = array();

		//exit
		$pid = $options['p_videowhisper_videochat_manage'];
		$url = get_permalink( $pid );
		//_ will be added to target
		$actionButtons['exitDashboard'] = array('name'=> 'exitDashboard', 'icon'=>'close', 'color'=> 'red', 'floated'=>'right', 'target' => 'top', 'url'=> $url,'text'=>'', 'tooltip'=> __('Exit', 'vw2wvc'));
		$room['actionButtons'] = $actionButtons;

		if ($match) 
		{
			$room['next'] = true;
			$room['welcome'] .= "\n ➡️ " .  __('Use Next button to find a different match.', 'ppv-live-webcams');

		}
	
		$room['welcome'] .= $welcome;
	
		return $room;

	}
	
	
	static function appRoomUsers($roomID, $options, $match = 0)
	{
		
		global $wpdb;
		$table_sessions = $wpdb->prefix . "vw_2w_sessions";
	

		//clean old sessions
		$exptime=time()-$options['sessionExpire'];
		$sql="DELETE FROM `$table_sessions` WHERE edate < $exptime";
		$wpdb->query($sql);
				
				

		$webStatusInterval = $options['webStatusInterval'] ?? 60;
		if ($webStatusInterval < 10) $webStatusInterval = 60;

		global $wpdb;
		$table_sessions = $wpdb->prefix . "vw_2w_sessions";

		$ztime = time();

		//update room user list

		$items = array();
		

		//list current
		$sql = "SELECT * FROM `$table_sessions` WHERE rid='" . $roomID . "' AND status = 0 ORDER BY broadcaster DESC, username ASC, edate DESC";
		$sqlRows = $wpdb->get_results($sql);

		$no = 0;
		if ($wpdb->num_rows>0)
			foreach ($sqlRows as $sqlRow)
			{
				if ($sqlRow->meta) $userMeta = unserialize($sqlRow->meta);
				if (!is_array($userMeta)) $userMeta = array();

				$roomMeta = unserialize($sqlRow->roptions);
				if (!is_array($roomMeta)) $roomMeta = array();

				$item = [];
				if (!$match) $item['userID'] = intval($sqlRow->uid);
				else $item['userID'] = intval($sqlRow->id);
				
				$item['userName'] = sanitize_file_name($sqlRow->username);
				if (!$item['userName']) $item['userName'] = '#' . $sqlRow->id;

				$item['sdate'] = intval($sqlRow->sdate);
				$item['updated'] = intval($sqlRow->edate);
				$item['avatar'] = get_avatar_url($sqlRow->uid, array('default'=> dirname(plugin_dir_url(__FILE__)).'/images/avatar.png'));
				$item['rid'] = intval($sqlRow->rid);

				//buddyPress profile url
				$bp_url = '';
				if (function_exists('bp_core_get_user_domain')) $bp_url = bp_core_get_user_domain($sqlRow->uid);


				if ($sqlRow->broadcaster) $url = $options['performerProfile'] ? $options['performerProfile'] .  urlencode($sqlRow->username) : ( $bp_url ? $bp_url : get_author_posts_url($sqlRow->uid));
				else $url = ( $options['clientProfile'] ?? false ) ? $options['clientProfile'] .  urlencode($sqlRow->username) : $bp_url;



				$item['url'] = $url;

/*
				if (array_key_exists('privateUpdate', $userMeta)) if ($ztime - intval($userMeta['privateUpdate']) < $options['onlineTimeout']) $item['hide'] = true; //in private
					//if ($ztime - intval($sqlRow->edate) < $options['onlineTimeout']) $item['hide'] = true; //offline

					if (array_key_exists('userMode', $roomMeta)) if ($roomMeta['userMode'] == 'voyeur') $item['hide'] = true; //voyeur

						if ($sqlRow->broadcaster) //HLS for broadcaster
							{

							//updated external broadcast info
							if (array_key_exists('externalUpdate', $userMeta))
								if ($userMeta['externalUpdate'] < time() - $webStatusInterval) $userMeta['external'] = false; //went offline?

								if (array_key_exists('external', $userMeta)) if ($userMeta['external'])
										$item['hls'] = self::appUserHLS($sqlRow->username, $options);
						}
*/

					//include updated user meta
					$item['meta'] = $userMeta;


				$item['order'] = ++$no;
				
				//user id for owner and session id for client (can be visitor with user id 0)
				if ($sqlRow->broadcaster) $ix = $sqlRow->uid;
				else $ix = $sqlRow->id;
				
				if ($match) $ix = $sqlRow->id;
						
				$items[$ix] = $item;
			}
		else
		{
			$item['userID'] = 0;
			$item['userName'] = 'ERROR_empty';
			$item['sql'] = $sql;
			$item['wpdb-last_error'] = $wpdb->last_error;
			$item['sdate'] = 0;
			$item['updated'] = 0;
			$item['meta']  = array();
			$items[0] = $item;
		}

		return $items;
	}
	
	
		static function appFail($message = 'Request Failed', $response = null)
	{
		//bad request: fail

		if (!$response) $response = array();

		$response['error'] = $message;

		$response['VideoWhisper'] = 'https://videowhisper.com';

		echo json_encode($response);

		die();
	}
	
		//! user sessions vw_2w_sessions
		static function sessionValid($sessionID, $userID)
		{
			//returns true if session is valid

			global $wpdb;
			$table_sessions = $wpdb->prefix . "vw_2w_sessions";

			$sqlS = "SELECT * FROM $table_sessions WHERE id='$sessionID' AND uid='$userID' AND status=0 LIMIT 0,1";
			$session = $wpdb->get_row($sqlS);

			if ($session) return $session;
			else return false;
		}
		
	static function appUserOptions($session, $options)
	{
		return [
		'h5v_sfx' =>  self::is_true(get_user_meta($session->uid, 'h5v_sfx', true )),
		'h5v_audio' =>  self::is_true(get_user_meta($session->uid, 'h5v_audio', true )),
		'h5v_dark' => self::is_true(get_user_meta($session->uid, 'h5v_dark', true )) ,
		'h5v_reveal' => self::is_true(get_user_meta($session->uid, 'h5v_reveal', true )) ,
		'h5v_reveal_warmup' => intval(get_user_meta($session->uid, 'h5v_reveal_warmup', true )),
		];
	}
	
	static function handle_upload($file, $destination)
	{
		//ex $_FILE['myfile']
		
		$movefile = wp_handle_upload( $file, [ 'test_form' => false ] ); 
		
		if ( $movefile && ! isset( $movefile['error'] ) ) 
		{
		if (!$destination) return 0; 		
		rename($movefile['file'], $destination); //$movefile[file, url, type]
		return 0;
		} 
		else 
		{
		    /*
		     * Error generated by _wp_handle_upload()
		     * @see _wp_handle_upload() in wp-admin/includes/file.php
		     */
		    return $movefile['error']; //return error
		}

	}


	//!App Ajax handlers
	static function vw_2w_app()
	{


		$options = get_option('VWvideoChatOptions');
		//output clean
		ob_clean();

		//D: login, public room (1 w broadcaster/viewer), 2w private vc, status
		//TD: tips


		global $wpdb;
		$table_videocalls = $wpdb->prefix . "vw_2wrooms";
		$table_match = $wpdb->prefix . "vw_2wmatch"; //status: 0 waiting, 1 matched, 2 left
						
		$table_sessions = $wpdb->prefix . "vw_2w_sessions";
		$table_chatlog = $wpdb->prefix . "vw_2w_chatlog";

		$http_origin = get_http_origin();
		$response['http_origin'] = $http_origin;
		$response['VideoWhisper'] = 'https://videowhisper.com';


		$task = sanitize_file_name( $_POST['task']);
		$devMode = self::is_true($_POST['devMode']); //app in devMode
		
		$match = boolval($_GET['match']); //random match mode

		$requestUID = intval($_POST['requestUID'] ?? 0 ); //directly requested private call

		//originally passed trough window after creating session
		//urlvar user_id > php var $userID

		$VideoWhisper = isset( $_POST['VideoWhisper'] ) ? (array) $_POST['VideoWhisper'] : ''; 	//array: session info received trough VideoWhisper POST var
		$VideoWhisper = array_map( 'intval', $VideoWhisper );
		
		$isPerformer = 0;

		if ($VideoWhisper)
		{
			$userID = intval($VideoWhisper['userID']);
			$sessionID = intval($VideoWhisper['sessionID']);
			$roomID = intval($VideoWhisper['roomID']);
			$sessionKey = intval($VideoWhisper['sessionKey']);

			$privateUID = intval($VideoWhisper['privateUID'] ?? 0 ); //in private call
			$roomActionID = intval($VideoWhisper['roomActionID'] ?? 0 );
		}

		if (!$match)
		{
			$rm = $wpdb->get_row("SELECT * FROM `$table_videocalls` where status='1' AND id = '$roomID'");				
			if (!$rm) self::appFail('Room not found: ' . $roomID . ' Try disabling cache.');
			$room = $roomName = $rm->name;
			$isPerfomer = ($userID == $rm->owner);

		}else
		{
			$rm = $wpdb->get_row("SELECT * FROM `$table_match` WHERE id = '$roomID'");				
			if (!$rm) self::appFail('Match booth not found: ' . $roomID . ' Try disabling cache.');
			$room = $roomName = 'M' . $roomID;
			$isPerfomer = ($sessionID == $rm->s1); //waiting
			
								
		}
		
		$postID = $roomID;
		$public_room = array();

		$ztime = time();
		

		// Handling the supported tasks:

		$response['task'] = $task;

		//handle auth / session

		if ($task != 'login')
		{
			//check session
			if (!$match) if (! $session = self::sessionValid($sessionID, $userID)) self::appFail('Session invalid or closed. Usually occurs if browser tab gets paused in background or room type changes. Please RELOAD! Session #' . $sessionID . ' User #' .  $userID . ' Room #' . $roomID .' ' . $task );
			
			if ($match) if (! $session = self::sessionValid($sessionID, $sessionKey)) self::appFail('Random chat session invalid or closed. Usually occurs if browser tab gets paused in background or room type changes. Please RELOAD! Session #' . $sessionID . ' User #' .  $sessionKey . ' Booth #' . $roomID .' ' . $task );

			$userName = sanitize_file_name($session->username);	
			//update session
			$wpdb->query("UPDATE `$table_sessions` set edate=$ztime where id ='$sessionID'");
			if ($match)	$wpdb->query("UPDATE `$table_match` SET edate='$ztime' where id ='$roomID'");

		}


		if ($task == 'login')
		{
			//retrieve wp info
			//$user = get_userdata($userID);
			
			/*
			if (!$user)
			{
				//
				$isVisitor =1;
				//self::appFail('User not found: ' . $userID);

				if ($_COOKIE['htmlchat_username']) $userName = sanitize_file_name($_COOKIE['htmlchat_username']);
				else
				{
					$userName =  'G_' . base_convert(time()%36 * rand(0,36*36),10,36);
					setcookie('htmlchat_username', $userName);
				}

				$isPerformer = 0;
			}*/

		if (!$match) if (! $session = self::sessionValid($sessionID, $userID)) self::appFail('Session login failed: s#' . $sessionID . ' u#' .  $userID . ' Cache plugin may prevent access to this dynamic content.');
		if ($match) if (! $session = self::sessionValid($sessionID, $sessionKey)) self::appFail('Match session login failed: s#' . $sessionID . ' u#' .  $sessionKey . ' Cache plugin may prevent access to this dynamic content.');
		$userName = sanitize_file_name($session->username);	
		
			//reset user preferences
			if ($userID)
				if (is_array($options['appSetup']))
					if (array_key_exists('User', $options['appSetup']))
						if (is_array($options['appSetup']['User']))
							foreach ($options['appSetup']['User'] as $key => $value)
							{
								$optionCurrent = get_user_meta( $userID, $key, true );

								if (empty($optionCurrent) || $options['appOptionsReset'])
								{
									update_user_meta($userID, $key, $value);
								}
							}

					//	$balance = floatval(self::balance($userID, false, $options)); //final only, not temp

					//user session parameters and info, updates
					$response['user'] = [
					'ID'=> intval($userID),
					'name'=> $userName,
					'sessionID'=> intval($sessionID),
					'sessionKey'=> intval($sessionKey),
					'loggedIn'=> true,
					'balance' => 0,
					'avatar' => get_avatar_url($userID, array('default'=> dirname(plugin_dir_url(__FILE__)).'/images/avatar.png'))
					];
					
					if ($match) $response['user']['ID'] = intval($sessionID);



			//if ($userID != $rm->owner) $privateUID = $rm->owner; //the other user is room owner
			
			$response['room'] = self::appRoom($rm, $session, $options, $match);

			$response['user']['options'] = self::appUserOptions($session, $options);

		
			//config params, const
			$response['config'] = [

			'serverType' => $options['webrtcServer'] ?? 'videowhisper',
			'vwsSocket' => $options['vwsSocket'],
			'vwsToken' => $options['vwsToken'],

			'wss' => $options['wsURLWebRTC'],
			'application' => $options['applicationWebRTC'],

			'videoCodec' =>  $options['webrtcVideoCodec'],
			'videoBitrate' =>  $options['webrtcVideoBitrate'],
			'audioBitrate' =>  $options['webrtcAudioBitrate'],
			'audioCodec' =>  $options['webrtcAudioCodec'],

			'snapshotInterval' => 180,
			'snapshotDisable' => true,
			
			'autoBroadcast' => false,
			'actionFullscreen' => true,
			'actionFullpage' => false,

			'serverURL' =>  admin_url() . 'admin-ajax.php?action=vw_2w_app',
			];

			//appMenu
			if ($options['appSiteMenu']>0)
			{
				$menus = wp_get_nav_menu_items($options['appSiteMenu']);
				//https://developer.wordpress.org/reference/functions/wp_get_nav_menu_items/

				$appMenu = array();
				if (is_array($menus)) if (count($menus))
					{
						foreach ( (array) $menus as $key => $menu_item )
						{
							$appMenuItem = array();
							$appMenuItem['title'] =  $menu_item->title;
							$appMenuItem['url'] =  $menu_item->url;
							$appMenuItem['ID'] =  intval($menu_item->ID);
							$appMenuItem['parentID'] =  intval($menu_item->menu_item_parent);
							$appMenu[] = $appMenuItem;
						}

						$appMenu[] = ['title'=>'END']; // menu end (last item ignored by app)

						$response['config']['siteMenu'] = $appMenu;
					}
			}

			//translations
			$response['config']['text'] = self::appText();


			$response['config']['sfx'] = self::appSfx();


			$response['config']['exitURL'] = ( $url = get_permalink( $options['p_videowhisper_videochat_manage'] ) ) ? $url : get_site_url() ;
			//$response['config']['balanceURL'] =  ( $url = get_permalink( $options['balancePage'] ) ) ? $url : get_site_url() ;

			//pass app setup config parameters
			if (is_array($options['appSetup']))
				if (array_key_exists('Config', $options['appSetup']))
					if (is_array($options['appSetup']['Config']))
						foreach ($options['appSetup']['Config'] as $key => $value)
							$response['config'][$key] = $value;


					if (!$isPerformer)
						if (array_key_exists('cameraAutoBroadcastAll', $response['config'])) $response['config']['cameraAutoBroadcast'] = $response['config']['cameraAutoBroadcastAll'];
						else $response['config']['cameraAutoBroadcast'] = '0';

						$response['config']['loaded'] = true;



		}

		if ($session->meta) $userMeta = unserialize($session->meta);
		if (!is_array($userMeta)) $userMeta = array();

		$ztime = time();

		
		//check if other left
		if ($match)
		{
			//detect if other user left
			if ($rm->status == '1') //marked as matched and online?
			{
				//clean old sessions
				$exptime=$ztime-$options['sessionExpire'];
				$sql="DELETE FROM `$table_sessions` WHERE edate < $exptime";
				$wpdb->query($sql);
				
				//other session ID
				if ($rm->s1 == $sessionID) $otherID = $rm->s2;
				else $otherID = $rm->s1;
				
				$otherSession = $wpdb->get_row("SELECT * FROM $table_sessions WHERE id='$otherID'");
				
				if (!$otherSession) 
				{
							
				//mark old session as closed
				$userMeta = ['closedBy'=>'next'];
				$userMeta['connected'] = false;
				$userMeta['connectedUpdate'] = time();
				$userMetaS = serialize($userMeta);
				$wpdb->query("UPDATE `$table_sessions` set meta='$userMetaS', status='3' WHERE id ='$sessionID'");

		
				//create new session 			
				$ztime = time();
				$ip =  self::get_ip_address();
				
				$userMeta = ['createdBy'=>'matchLeft'];
				$userMetaS = serialize($userMeta);


				$sql="INSERT INTO `$table_sessions` ( `session`, `username`, `room`, `message`, `sdate`, `edate`, `status`, `type`, `uid`, `rid`, `ip`,`broadcaster`, `meta`) VALUES ('$userName', '$userName', 'M" . $nextRm->id . "', '', $ztime, $ztime, 0, 2, $session->uid, '$nextRoomID', '$ip', 0, '$userMetaS')";
				$wpdb->query($sql);
				$sessionID = $nextSessionID = $wpdb->insert_id;
				$sesson = $nextSession = $wpdb->get_row("SELECT * FROM $table_sessions WHERE id='$sessionID'");

	
				$nextRoom = self::nextRoomID($nextSessionID, $userName, $options);
				$nextRoomID = intval($nextRoom);
			
			if ($nextRoomID)
			{

			//updated session with new meta from nextRoomID
			$session = $wpdb->get_row("SELECT * FROM $table_sessions WHERE id='$sessionID'");
		
				//next room
				$response['nextRoomID'] = $nextRoomID;
				$nextRm = $wpdb->get_row("SELECT * FROM `$table_match` WHERE id = '$nextRoomID'");				
				if (!$nextRm) $response['warning'] = 'Match room failed!';
				
				$response['warning'] = "User #$otherID left: Moving to a new match booth #$nextRoomID:" . $nextRm->s1 . '-' . $nextRm->s2 ;

					
				//$wpdb->query("UPDATE `$table_sessions` SET uid='$nextSessionID WHERE id = $nextSessionID");
				

				//update match room
				//$wpdb->query("UPDATE `$table_match` SET s1='$nextSessionID', edate='$ztime' WHERE id ='$nextRoomID' AND s1='$sessionID'");				
				//$wpdb->query("UPDATE `$table_match` SET s2='$nextSessionID', edate='$ztime' WHERE id ='$nextRoomID' AND s2='$sessionID'");

				//update user
				$response['user'] = [
				'ID'=> intval($nextSessionID),
				'name'=>$userName,
				'sessionID'=> intval($nextSessionID),
				'loggedIn'=> true,
				'balance' => 0,
				'cost' => 0,
				'avatar' => get_avatar_url($userID, array('default'=> dirname(plugin_dir_url(__FILE__)).'/images/avatar.png'))
				];
				
				//move to next room
				$response['room'] = self::appRoom($nextRm, $nextSession, $options, 1);

				$session = $nextSession;
				$rm = $nextRm;
				$changedRoom = 1;
			}
			else $response['warning'] = 'Next match error: ' . $nextRoom;

					
				}
			
			}
			
			//update room		
			$wpdb->query("UPDATE `$table_match` SET edate='$ztime' where id ='$roomID'");
		}


		$needUpdate = array();

		//process app task (other than login)
		switch ($task)
		{
			case 'login':
			case 'tick':
			break;
			
		
			case 'next':
			
				//mark old session as closed
				$userMeta = ['closedBy'=>'next'];
				$userMeta['connected'] = false;
				$userMeta['connectedUpdate'] = time();
				$userMetaS = serialize($userMeta);
				$wpdb->query("UPDATE `$table_sessions` set meta='$userMetaS', status='3' WHERE id ='$sessionID'");
				
				//create next session first				
				$ztime = time();
				$ip =  self::get_ip_address();
				$userMeta = ['createdBy'=>'next'];
				$userMetaS = serialize($userMeta);
				$sql="INSERT INTO `$table_sessions` ( `session`, `username`, `room`, `message`, `sdate`, `edate`, `status`, `type`, `uid`, `rid`, `ip`,`broadcaster`, `meta`) VALUES ('$userName', '$userName', 'M" . 0 . "', '', $ztime, $ztime, 0, 2, $session->uid, '0', '$ip', 0, '$userMetaS')";
				$wpdb->query($sql);
				$nextSessionID = $wpdb->insert_id;
				$nextSession = $wpdb->get_row("SELECT * FROM $table_sessions WHERE id='$sessionID'");

				$session = $nextSession;

			
			$nextRoom = self::nextRoomID($nextSessionID, $userName, $options);
			$nextRoomID = intval($nextRoom);
				
			if ($nextRoomID)
			{
				//next room
				$response['nextRoomID'] = $nextRoomID;
				$nextRm = $wpdb->get_row("SELECT * FROM `$table_match` WHERE id = '$nextRoomID'");				
				if (!$nextRm) $response['warning'] = 'Match room failed!';
				
				//update match room
				//$wpdb->query("UPDATE `$table_match` SET s1='$nextSessionID', edate='$ztime' WHERE id ='$nextRoomID' AND s1='$sessionID'");				
				//$wpdb->query("UPDATE `$table_match` SET s2='$nextSessionID', edate='$ztime' WHERE id ='$nextRoomID' AND s2='$sessionID'");

				//update user
				$response['user'] = [
				'ID'=> intval($nextSessionID),
				'name'=>$userName,
				'sessionID'=> intval($nextSessionID),
				'loggedIn'=> true,
				'balance' => 0,
				'cost' => 0,
				'avatar' => get_avatar_url($userID, array('default'=> dirname(plugin_dir_url(__FILE__)).'/images/avatar.png'))
				];

				//move to next room
				$response['room'] = self::appRoom($nextRm, $nextSession, $options, 1, " \n 🎲" . __('You requested a new match.', 'ppv-live-webcams'));

				$rm = $nextRm;
				$changedRoom = 1;
			}
			else $response['warning'] = 'Next match error: ' . $nextRoom;
			break;
			

			case 'recorder_upload':

			if (!$roomName) self::appFail('No room for recording.');
			if (strstr($filename,".php")) self::appFail('Bad uploader!');

			$mode = sanitize_file_name( $_POST['mode'] );
			$scenario = sanitize_file_name( $_POST['scenario'] );
			//
			if (!$privateUID)  $privateUID = 0; //public room

			//generate same private room folder for both users
			if ($privateUID)
			{
				if ($isPerformer) $proom = $userID . "_" . $privateUID; //performer id first
				else $proom = $privateUID ."_". $userID;
			}

			$destination = sanitize_text_field( $options['uploadsPath'] );
			if (!file_exists($destination)) mkdir($destination);

			$destination .= '/'. sanitize_file_name( $roomName );
			if (!file_exists($destination)) mkdir($destination);

			//
			$response['_FILES'] = $_FILES;


			$allowed = array('mp3','ogg','opus','mp4', 'webm', 'mkv');

			$uploads = 0;
			$filename = '';

			if ($_FILES) if (is_array($_FILES))
					foreach ($_FILES as $ix => $file)
					{
						$filename = sanitize_file_name($file['name']);

						$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
						$response['uploadRecLastExt'] = $ext;
						$response['uploadRecLastF'] = $filename;

						$filepath = $destination . '/' . $filename;

						if (in_array($ext,$allowed))
							if (file_exists($file['tmp_name']))
							{
								//move_uploaded_file($file['tmp_name'], $filepath); //replaced with handling trough wp_handle_upload()
								$errorUp = self::handle_upload($file, $filepath); //handle trough wp_handle_upload()
								if ($errorUp) $response['warning'] = ($response['warning'] ? $response['warning'] . '; ' : '' ) . 'Error uploading ' .  esc_html($filename . ':' . $errorUp);
								//$response['uploadRecLast'] = $destination . $filename;
								$uploads++;
							}
					}

				$response['uploadCount'] = $uploads;

			//1 file
			if (!file_exists($filepath))
			{
				$response['warning'] = ( $response['warning'] ? $response['warning'] . '; ' : '' )  .  'Recording upload failed: file missing!';
			}

			if ( !$response['warning'] && $scenario == 'chat' )
			{
				$url = self::path2url($filepath);

				$response['recordingUploadSize'] = filesize($filepath);
				$response['recordingUploadURL'] = $url;

				$messageText = '';
				$messageUser = $userName;
				$userAvatar = get_avatar_url($userID, array('default'=> dirname(plugin_dir_url(__FILE__)).'/images/avatar.png'));
				$messageUserAvatar = esc_url_raw($userAvatar);

				$meta = array(
					'userAvatar' => $messageUserAvatar,
				);

				if ($mode == 'video') $meta['video']= $url;
				else $meta['audio']= $url;

				$metaS = esc_sql(serialize($meta));

				//msg type: 2 web, 1 flash, 3 own notification
				$sql="INSERT INTO `$table_chatlog` ( `username`, `room`, `room_id`, `message`, `mdate`, `type`, `user_id`, `meta`, `private_uid`) VALUES ('$messageUser', '$roomName', '$roomID', '$messageText', $ztime, '2', '$userID', '$metaS', '$privateUID')";
				$wpdb->query($sql);

				$response['sql'] = $sql;

				$response['insertID'] =  $wpdb->insert_id;

				//also update chat log file
				if ($roomName) if ($messageText)
					{

						$messageText = strip_tags($messageText,'<p><a><img><font><b><i><u>');

						$messageText = date("F j, Y, g:i a", $ztime) . " <b>$userName</b>: $messageText <audio controls src='$url'></audio>";


						$day=date("y-M-j",time());

						$dfile = fopen($destination . "/Log$day.html","a");
						fputs($dfile,$messageText."<BR>");
						fclose($dfile);
					}
			}

			break;

		case 'options':

			$name = sanitize_file_name($_POST['name']);
			$value = sanitize_file_name($_POST['value']);

			if (!in_array($name, ['requests_disable', 'room_private', 'calls_only', 'group_disabled', 'room_slots', 'room_conference', 'conference_auto', 'room_audio', 'room_text','vw_presentationMode', 'h5v_audio', 'h5v_sfx', 'h5v_dark', 'h5v_reveal', 'h5v_reveal_warmup', 'party', 'party_reserved', 'stream_record', 'stream_record_all', 'stream_record_private'])) self::appFail('Preference not supported!');

			if (substr($name,0,3) == 'h5v') $userOption = 1;
			else $userOption = 0;

			if (!is_user_logged_in()) break; //visitors don't edit any preferences

			if (!$session->broadcaster && !$userOption)
			{
				$response['warning'] = __('Only room owner can edit room options.', 'vw2wvc') ;
				break;
			}

			if ($userOption)
			{
				update_user_meta($userID, $name, $value);
				update_user_meta($userID, 'updated_options', time());
				$needUpdate['user'] = 1;
			}
			else
			{
				
				$response['warning'] = 'Not implemented.' ;
				//update_post_meta($postID, $name, $value);
				//update_post_meta($postID, 'updated_options', time());
			}

			$needUpdate['options'] = 1;



			break;

		case 'update':
			//something changed - let everybody know (later implementation - selective updates, triggers)
			$update = sanitize_file_name($_POST['update']);
			// update_post_meta($postID, 'updated_' . $update, time());
			$needUpdate[$update] = 1;

			break;

		case 'media':
			//notify user media (streaming) updates

			$connected = ($_POST['connected'] == 'true'?true:false);

			if ($session->meta) $userMeta = unserialize($session->meta);
			if (!is_array($userMeta)) $userMeta = array();

			//if ($options['debugMode']) $userMeta['updateMediaMeta'] = $session->meta;

			$userMeta['connected'] = $connected;
			$userMeta['connectedUpdate'] = time();

			//also update external broadcast info on web media publishing
			$webStatusInterval = $options['webStatusInterval'] ?? 60;
			if ($webStatusInterval < 10) $webStatusInterval = 60;

			if (array_key_exists('externalUpdate', $userMeta))
				if ($userMeta['externalUpdate'] < time() - $webStatusInterval) $userMeta['external'] = false;

				$userMetaS = esc_sql(serialize($userMeta));

			$sql = "UPDATE `$table_sessions` set meta='$userMetaS' WHERE id ='" . $session->id . "'";
			$wpdb->query($sql);

			$response['taskSQL'] = $sql;
		
			break;

	case 'message':

			$message = isset( $_POST['message'] ) ? (array) $_POST['message'] : ''; //array

			$messageText = esc_sql(wp_encode_emoji(sanitize_textarea_field($message['text'])));
			$messageUser = sanitize_text_field($message['userName']);
			$messageUserAvatar = esc_url_raw($message['userAvatar']);

			$meta = array(
				'notification'=>sanitize_text_field( $message['notification'] ),
				'userAvatar' => $messageUserAvatar,
				'mentionMessage' => intval($message['mentionMessage']),
				'mentionUser'=> sanitize_text_field($message['mentionUser'])
			);
			
			$metaS = esc_sql(serialize($meta));

			if (!$privateUID)  $privateUID = 0; //public room

			//msg type: 2 web, 1 flash, 3 own notification
			$sql="INSERT INTO `$table_chatlog` ( `username`, `room`, `room_id`, `message`, `mdate`, `type`, `user_id`, `meta`, `private_uid`) VALUES ('$messageUser', '$roomName', '$roomID', '$messageText', $ztime, '2', '$userID', '$metaS', '$privateUID')";
			$wpdb->query($sql);

			$response['sql'] = $sql;

			$response['insertID'] =  $wpdb->insert_id;

			//also update chat log file
			if ($roomName) if ($messageText)
				{

					$messageText = strip_tags($messageText,'<p><a><img><font><b><i><u>');

					$messageText = date("F j, Y, g:i a", $ztime) . " <b>$userName</b>: $messageText";

					//generate same private room folder for both users
					if ($privateUID)
					{
						if ($isPerformer) $proom = $userID . "_" . $privateUID; //performer id first
						else $proom = $privateUID ."_". $userID;
					}

					$dir = sanitize_text_field( $options['uploadsPath'] );
					if (!file_exists($dir)) mkdir($dir);

					$dir.="/$roomName";
					if (!file_exists($dir)) mkdir($dir);

/*
					if ($proom)
					{
						$dir.="/$proom";
						if (!file_exists($dir)) mkdir($dir);
					}
*/
					$day=date("y-M-j",time());

					$dfile = fopen($dir."/Log$day.html","a");
					fputs($dfile,$messageText."<BR>");
					fclose($dfile);
				}



			break;
			
	
			default:
			$response['warning'] = 'Not implemented in this integration: ' . esc_html( $task );
		}
		
		
		//update room and users
		$users = self::appRoomUsers($roomID, $options, $match);
		
		//is watching correct client?
		if ($isPerfomer && !$match) 
		{
			$watching = 1;
		
			if (!array_key_exists('watch', $userMeta)) $watching = 0;
			else if ($userMeta['watch'] != $rm->client) $watching = 0;
			
			$clientSession = $wpdb->get_row("SELECT * FROM $table_sessions WHERE status=0 AND id='". $rm->client ."'");

			if (!$watching && $clientSession)
			{
				$userMeta['watch'] = $rm->client;
			
				$userMetaS = esc_sql(serialize($userMeta));
	
				//update session
				$wpdb->query("UPDATE `$table_sessions` set meta='$userMetaS' WHERE id ='$sessionID'");
				$session = $wpdb->get_row("SELECT * FROM $table_sessions WHERE id='$sessionID'");

				$response['room'] = self::appRoom($rm, $session, $options, $match, "\n 👤 Client Entered:" . $clientSession->username . ' #' . $clientSession->uid . '/' . $clientSession->id );
			}	
		}
		
		if ($match)
		{
			//watch available and not applied
			if (!( $userMeta['watchConfirm'] ?? false ) && ( $userMeta['watch'] ?? false ) )
			{
				$userMeta['watchConfirm'] = 1;
				$userMetaS = esc_sql(serialize($userMeta));
				//update session
				$wpdb->query("UPDATE `$table_sessions` set meta='$userMetaS' WHERE id ='$sessionID'");
				$session = $wpdb->get_row("SELECT * FROM $table_sessions WHERE id='$sessionID'");

				$clientSession = $wpdb->get_row("SELECT * FROM $table_sessions WHERE id='". $userMeta['watch'] ."'");
				$response['room'] = self::appRoom($rm, $session, $options, $match, "\n 👤 User Matched: " . $clientSession->username . ' #' . $clientSession->id );
			}
		}

		$response['roomUpdate']['users'] = $users; //always update online users list
		$response['roomUpdate']['updated'] = $ztime;

		//update time
		$lastMessage = intval($_POST['lastMessage'] ?? 0 );
		$lastMessageID = intval($_POST['lastMessageID'] ?? 0 );


		//retrieve only messages since user came online / updated
		$sdate = 0;
		if ($session) $sdate = $session->sdate;
		$startTime = max($sdate, $lastMessage);

		$response['startTime'] = $startTime;


		//!messages

		//clean old chat logs
		$closeTime = time() - 900; //only keep for 15min
		$sql="DELETE FROM `$table_chatlog` WHERE mdate < $closeTime";
		$wpdb->query($sql);


		$items = array();

		$cndNotification = "AND (type < 3 OR (type=3 AND user_id='$userID' AND username='$userName'))"; //chat message or own notification (type 3)


		$cndPrivate = "AND private_uid = '0'";
		if ($privateUID) $cndPrivate = "AND ( (private_uid = '$privateUID' AND user_id = '$userID') OR (private_uid ='$userID' AND user_id = '$privateUID') )"; //messages in private from each to other

		$cndTime = "AND mdate >= $startTime AND mdate <= $ztime AND id > $lastMessageID";

		$sql = "SELECT * FROM `$table_chatlog` WHERE room='$roomName' $cndNotification $cndPrivate $cndTime ORDER BY mdate DESC LIMIT 0,100"; //limit to last 100 messages, until processed date
		$sql = "SELECT * FROM ($sql) items ORDER BY mdate ASC"; //but order ascendent

		//$response['sqlM'] = $sql;

		$sqlRows = $wpdb->get_results($sql);

		$idMax = 0;
		if ($wpdb->num_rows>0) foreach ($sqlRows as $sqlRow)
			{
				$item = [];

				$item['ID'] = intval($sqlRow->id);

				if ($item['ID']>$idMax) $idMax = $item['ID'];

				$item['userName'] = $sqlRow->username;
				$item['userID'] = intval($sqlRow->user_id);

				$item['text'] = html_entity_decode($sqlRow->message);
				$item['time'] = intval($sqlRow->mdate * 1000); //time in ms for js

				//avatar
				$uid  = $sqlRow->user_id;
				if (!$uid)
				{
					$wpUser = get_user_by($userName, $sqlRow->username);
					if (!$wpUser) $wpUser = get_user_by('login', $sqlRow->username);
					$uid = $wpUser->ID;
				}

				$item['userAvatar'] = get_avatar_url($uid);

				//meta
				if ($sqlRow->meta)
				{
					$meta = unserialize($sqlRow->meta);
					foreach ($meta as $key=>$value) $item[$key] = $value;

					$item['notification'] =  ($meta['notification'] == 'true'?true:false);
				}

				if ($sqlRow->type == 3) $item['notification'] = true;

				$items[] = $item;
			}

		$response['messages'] = $items; //messages list

		$response['timestamp'] = $ztime; //update time

		$response['lastMessageID'] = $idMax;

		///update message
	
		echo json_encode($response);
		die();

		}

		static function countryOptions()
		{
			return array(
				"AF" => "Afghanistan",
				"AL" => "Albania",
				"DZ" => "Algeria",
				"AS" => "American Samoa",
				"AD" => "Andorra",
				"AO" => "Angola",
				"AI" => "Anguilla",
				"AQ" => "Antarctica",
				"AG" => "Antigua and Barbuda",
				"AR" => "Argentina",
				"AM" => "Armenia",
				"AW" => "Aruba",
				"AU" => "Australia",
				"AT" => "Austria",
				"AZ" => "Azerbaijan",
				"BS" => "Bahamas",
				"BH" => "Bahrain",
				"BD" => "Bangladesh",
				"BB" => "Barbados",
				"BY" => "Belarus",
				"BE" => "Belgium",
				"BZ" => "Belize",
				"BJ" => "Benin",
				"BM" => "Bermuda",
				"BT" => "Bhutan",
				"BO" => "Bolivia",
				"BA" => "Bosnia and Herzegovina",
				"BW" => "Botswana",
				"BV" => "Bouvet Island",
				"BR" => "Brazil",
				"IO" => "British Indian Ocean Territory",
				"BN" => "Brunei Darussalam",
				"BG" => "Bulgaria",
				"BF" => "Burkina Faso",
				"BI" => "Burundi",
				"KH" => "Cambodia",
				"CM" => "Cameroon",
				"CA" => "Canada",
				"CV" => "Cape Verde",
				"KY" => "Cayman Islands",
				"CF" => "Central African Republic",
				"TD" => "Chad",
				"CL" => "Chile",
				"CN" => "China",
				"CX" => "Christmas Island",
				"CC" => "Cocos (Keeling) Islands",
				"CO" => "Colombia",
				"KM" => "Comoros",
				"CG" => "Congo",
				"CD" => "Congo, the Democratic Republic of the",
				"CK" => "Cook Islands",
				"CR" => "Costa Rica",
				"CI" => "Cote D'Ivoire",
				"HR" => "Croatia",
				"CU" => "Cuba",
				"CY" => "Cyprus",
				"CZ" => "Czech Republic",
				"DK" => "Denmark",
				"DJ" => "Djibouti",
				"DM" => "Dominica",
				"DO" => "Dominican Republic",
				"EC" => "Ecuador",
				"EG" => "Egypt",
				"SV" => "El Salvador",
				"GQ" => "Equatorial Guinea",
				"ER" => "Eritrea",
				"EE" => "Estonia",
				"ET" => "Ethiopia",
				"FK" => "Falkland Islands (Malvinas)",
				"FO" => "Faroe Islands",
				"FJ" => "Fiji",
				"FI" => "Finland",
				"FR" => "France",
				"GF" => "French Guiana",
				"PF" => "French Polynesia",
				"TF" => "French Southern Territories",
				"GA" => "Gabon",
				"GM" => "Gambia",
				"GE" => "Georgia",
				"DE" => "Germany",
				"GH" => "Ghana",
				"GI" => "Gibraltar",
				"GR" => "Greece",
				"GL" => "Greenland",
				"GD" => "Grenada",
				"GP" => "Guadeloupe",
				"GU" => "Guam",
				"GT" => "Guatemala",
				"GN" => "Guinea",
				"GW" => "Guinea-Bissau",
				"GY" => "Guyana",
				"HT" => "Haiti",
				"HM" => "Heard Island and Mcdonald Islands",
				"VA" => "Holy See (Vatican City State)",
				"HN" => "Honduras",
				"HK" => "Hong Kong",
				"HU" => "Hungary",
				"IS" => "Iceland",
				"IN" => "India",
				"ID" => "Indonesia",
				"IR" => "Iran, Islamic Republic of",
				"IQ" => "Iraq",
				"IE" => "Ireland",
				"IL" => "Israel",
				"IT" => "Italy",
				"JM" => "Jamaica",
				"JP" => "Japan",
				"JO" => "Jordan",
				"KZ" => "Kazakhstan",
				"KE" => "Kenya",
				"KI" => "Kiribati",
				"KP" => "Korea, Democratic People's Republic of",
				"KR" => "Korea, Republic of",
				"KW" => "Kuwait",
				"KG" => "Kyrgyzstan",
				"LA" => "Lao People's Democratic Republic",
				"LV" => "Latvia",
				"LB" => "Lebanon",
				"LS" => "Lesotho",
				"LR" => "Liberia",
				"LY" => "Libyan Arab Jamahiriya",
				"LI" => "Liechtenstein",
				"LT" => "Lithuania",
				"LU" => "Luxembourg",
				"MO" => "Macao",
				"MK" => "Macedonia, the Former Yugoslav Republic of",
				"MG" => "Madagascar",
				"MW" => "Malawi",
				"MY" => "Malaysia",
				"MV" => "Maldives",
				"ML" => "Mali",
				"MT" => "Malta",
				"MH" => "Marshall Islands",
				"MQ" => "Martinique",
				"MR" => "Mauritania",
				"MU" => "Mauritius",
				"YT" => "Mayotte",
				"MX" => "Mexico",
				"FM" => "Micronesia, Federated States of",
				"MD" => "Moldova, Republic of",
				"MC" => "Monaco",
				"MN" => "Mongolia",
				"MS" => "Montserrat",
				"MA" => "Morocco",
				"MZ" => "Mozambique",
				"MM" => "Myanmar",
				"NA" => "Namibia",
				"NR" => "Nauru",
				"NP" => "Nepal",
				"NL" => "Netherlands",
				"AN" => "Netherlands Antilles",
				"NC" => "New Caledonia",
				"NZ" => "New Zealand",
				"NI" => "Nicaragua",
				"NE" => "Niger",
				"NG" => "Nigeria",
				"NU" => "Niue",
				"NF" => "Norfolk Island",
				"MP" => "Northern Mariana Islands",
				"NO" => "Norway",
				"OM" => "Oman",
				"PK" => "Pakistan",
				"PW" => "Palau",
				"PS" => "Palestinian Territory, Occupied",
				"PA" => "Panama",
				"PG" => "Papua New Guinea",
				"PY" => "Paraguay",
				"PE" => "Peru",
				"PH" => "Philippines",
				"PN" => "Pitcairn",
				"PL" => "Poland",
				"PT" => "Portugal",
				"PR" => "Puerto Rico",
				"QA" => "Qatar",
				"RE" => "Reunion",
				"RO" => "Romania",
				"RU" => "Russian Federation",
				"RW" => "Rwanda",
				"SH" => "Saint Helena",
				"KN" => "Saint Kitts and Nevis",
				"LC" => "Saint Lucia",
				"PM" => "Saint Pierre and Miquelon",
				"VC" => "Saint Vincent and the Grenadines",
				"WS" => "Samoa",
				"SM" => "San Marino",
				"ST" => "Sao Tome and Principe",
				"SA" => "Saudi Arabia",
				"SN" => "Senegal",
				"CS" => "Serbia and Montenegro",
				"SC" => "Seychelles",
				"SL" => "Sierra Leone",
				"SG" => "Singapore",
				"SK" => "Slovakia",
				"SI" => "Slovenia",
				"SB" => "Solomon Islands",
				"SO" => "Somalia",
				"ZA" => "South Africa",
				"GS" => "South Georgia and the South Sandwich Islands",
				"ES" => "Spain",
				"LK" => "Sri Lanka",
				"SD" => "Sudan",
				"SR" => "Suriname",
				"SJ" => "Svalbard and Jan Mayen",
				"SZ" => "Swaziland",
				"SE" => "Sweden",
				"CH" => "Switzerland",
				"SY" => "Syrian Arab Republic",
				"TW" => "Taiwan, Province of China",
				"TJ" => "Tajikistan",
				"TZ" => "Tanzania, United Republic of",
				"TH" => "Thailand",
				"TL" => "Timor-Leste",
				"TG" => "Togo",
				"TK" => "Tokelau",
				"TO" => "Tonga",
				"TT" => "Trinidad and Tobago",
				"TN" => "Tunisia",
				"TR" => "Turkey",
				"TM" => "Turkmenistan",
				"TC" => "Turks and Caicos Islands",
				"TV" => "Tuvalu",
				"UG" => "Uganda",
				"UA" => "Ukraine",
				"AE" => "United Arab Emirates",
				"GB" => "United Kingdom",
				"US" => "United States",
				"UM" => "United States Minor Outlying Islands",
				"UY" => "Uruguay",
				"UZ" => "Uzbekistan",
				"VU" => "Vanuatu",
				"VE" => "Venezuela",
				"VN" => "Viet Nam",
				"VG" => "Virgin Islands, British",
				"VI" => "Virgin Islands, U.s.",
				"WF" => "Wallis and Futuna",
				"EH" => "Western Sahara",
				"YE" => "Yemen",
				"ZM" => "Zambia",
				"ZW" => "Zimbabwe"
				);
		}

	}