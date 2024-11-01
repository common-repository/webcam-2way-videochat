<?php
/*
Plugin Name: 2Way VideoCalls and Random Chat - HTML5 Webcam Videochat
Plugin URI: https://videowhisper.com/?p=WordPress-Webcam-2Way-VideoChat
Description: <strong>2Way VideoCalls and Random Chat - HTML5 Webcam Videochat</strong> provides instant web based 1 on 1 private video call rooms using HTML5 Videochat.  <a href='https://videowhisper.com/tickets_submit.php?topic=2Way+VideoCalls+Plugin'>Contact Support</a> | <a href='admin.php?page=videocalls&tab=setup'>Setup</a>
Version: 5.4.11
Requires PHP: 7.4
Author: VideoWhisper.com
Author URI: https://videowhisper.com/
Contributors: videowhisper, VideoWhisper.com
Domain Path: /languages/
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

defined( 'ABSPATH' ) or exit;

require_once plugin_dir_path( __FILE__ ) .'/inc/options.php';
require_once plugin_dir_path( __FILE__ ) .'/inc/h5videochat.php';
require_once plugin_dir_path( __FILE__ ) .'/inc/requirements.php';

use VideoWhisper\VideoCalls;

if (!class_exists("VWvideoChat"))
{

	class VWvideoChat
	{

		use VideoWhisper\VideoCalls\Options;
		use VideoWhisper\VideoCalls\H5Videochat;
		use VideoWhisper\VideoCalls\Requirements;

		public function __construct()
		{
		}

		function VWvideoChat()
		{ 
				//constructor
				self::__construct();
		}

		//


		static function varSave($path, $var)
		{
			file_put_contents($path, serialize($var));
		}


		static function varLoad($path)
		{
			if (!file_exists($path)) return false;

			return unserialize(file_get_contents($path));
		}


		static function stringSave($path, $var)
		{
			file_put_contents($path, $var);
		}


		static function stringLoad($path)
		{
			if (!file_exists($path)) return false;

			return file_get_contents($path);
		}


		static function plugins_loaded()
		{
			//translations
			load_plugin_textdomain('vw2wvc', false, dirname(plugin_basename(__FILE__)) .'/languages');


			$plugin = plugin_basename(__FILE__);
			add_filter("plugin_action_links_$plugin",  array('VWvideoChat', 'settings_link') );


			//notify admin about requirements
			if ( current_user_can( 'administrator' ) ) self::requirements_plugins_loaded();

			//wp_register_sidebar_widget('videoChatWidget', 'VideoWhisper Videochat', array('VWvideoChat', 'widget') );

			//shortcodes
			add_shortcode('videowhisper_videochat_manage', array( 'VWvideoChat', 'videowhisper_videochat_manage'));
			add_shortcode('videowhisper_videochat_random', array( 'VWvideoChat', 'videowhisper_videochat_random'));
			add_shortcode('videowhisper_videochat_filters', array( 'VWvideoChat', 'videowhisper_videochat_filters'));

			$options = self::getAdminOptions();

			//only if flash enabled
			if ($options['flash'])
			{
			//add_action( 'wp_ajax_v2wvc', array('VWvideoChat','v2wvc_callback') );
			//add_action( 'wp_ajax_nopriv_v2wvc', array('VWvideoChat','v2wvc_callback') );
			}

			//filters ajax calls
			add_action( 'wp_ajax_vw_2w_filters', array('VWvideoChat','vw_2w_filters') );
			add_action( 'wp_ajax_nopriv_vw_2w_filters', array('VWvideoChat','vw_2w_filters') );

					
			//web app ajax calls
			add_action( 'wp_ajax_vw_2w_app', array('VWvideoChat','vw_2w_app') );
			add_action( 'wp_ajax_nopriv_vw_2w_app', array('VWvideoChat','vw_2w_app') );


			//check db
			$vw2vc_db_version = "5.3.2c";

			global $wpdb;
			$table_name = $wpdb->prefix . "vw_2wsessions";
			$table_videocalls = $wpdb->prefix . "vw_2wrooms";
			$table_match = $wpdb->prefix . "vw_2wmatch";

			$table_sessions = $wpdb->prefix . "vw_2w_sessions";
			$table_chatlog = $wpdb->prefix . "vw_2w_chatlog";

			$installed_ver = get_option( "vw2vc_db_version" );

			if
			( $installed_ver != $vw2vc_db_version )
			{
				//$wpdb->flush();

		$sql = "DROP TABLE IF EXISTS `$table_match`;
		CREATE TABLE `$table_match` (
		  `id` int(11) NOT NULL auto_increment,
		  `s1` int(11) NOT NULL,
		  `s1u` varchar(64) NOT NULL,		  
		  `s2` int(11) NOT NULL,	  		  	  
		  `sdate` int(11) NOT NULL,
		  `edate` int(11) NOT NULL,
		  `status` tinyint(4) NOT NULL,
		  `s1_gender` varchar(16) NOT NULL,
		  `s1_country` varchar(32) NOT NULL,
		  `s1_gender2` varchar(16) NOT NULL,
		  `s1_country2` varchar(32) NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `status` (`status`),
		  KEY `s1` (`s1`),
		  KEY `s2` (`s2`),
		  KEY `s1_gender` (`s1_gender`),
		  KEY `s1_country` (`s1_country`),
		  KEY `s1_gender2` (`s1_gender2`),
		  KEY `s1_country2` (`s1_country2`),
		  KEY `edate` (`edate`)		  
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='VideoWhisper: Sessions 2015-2021@videowhisper.com' AUTO_INCREMENT=1;
				
		DROP TABLE IF EXISTS `$table_name`;
		CREATE TABLE `$table_name` (
		  `id` int(11) NOT NULL auto_increment,
		  `session` varchar(64) NOT NULL,
		  `username` varchar(64) NOT NULL,
		  `room` varchar(64) NOT NULL,
		  `message` text NOT NULL,
		  `sdate` int(11) NOT NULL,
		  `edate` int(11) NOT NULL,
		  `status` tinyint(4) NOT NULL,
		  `type` tinyint(4) NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `status` (`status`),
		  KEY `type` (`type`),
		  KEY `room` (`room`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='Video Whisper: Sessions - 2009-2021@videowhisper.com' AUTO_INCREMENT=1 ;

		DROP TABLE IF EXISTS `$table_videocalls`;
		CREATE TABLE `$table_videocalls` (
		  `id` int(11) NOT NULL auto_increment,
		  `name` varchar(64) NOT NULL,
		  `owner` int(11) NOT NULL,
		  `client` int(11) NOT NULL,	  		  	  
		  `access` varchar(255) NOT NULL,
		  `sdate` int(11) NOT NULL,
		  `edate` int(11) NOT NULL,
		  `status` tinyint(4) NOT NULL,
		  `type` tinyint(4) NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `name` (`name`),
		  KEY `status` (`status`),
		  KEY `type` (`type`),
		  KEY `owner` (`owner`),
		  KEY `client` (`client`)		  
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='Video Whisper: Rooms - 2009-2021@videowhisper.com' AUTO_INCREMENT=1;
		
		DROP TABLE IF EXISTS `$table_sessions`;
		CREATE TABLE `$table_sessions` (
		  `id` int(11) NOT NULL auto_increment,
		  `session` varchar(64) NOT NULL,
		  `username` varchar(64) NOT NULL,
		  `uid` int(11) NOT NULL,
		  `broadcaster` tinyint(4) NOT NULL,
		  `room` varchar(64) NOT NULL,
		  `rid` int(11) NOT NULL,
		  `rsdate` int(11) NOT NULL,
		  `redate` int(11) NOT NULL,
		  `roptions` text NOT NULL,
		  `meta` text NOT NULL,
		  `rmode` tinyint(4) NOT NULL,
		  `message` text NOT NULL,
		  `ip` text NOT NULL,
		  `sdate` int(11) NOT NULL,
		  `edate` int(11) NOT NULL,
		  `status` tinyint(4) NOT NULL,
		  `type` tinyint(4) NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `status` (`status`),
		  KEY `broadcaster` (`broadcaster`),
		  KEY `type` (`type`),
		  KEY `rid` (`rid`),
		  KEY `uid` (`uid`),
		  KEY `rmode` (`rmode`),
		  KEY `rsdate` (`rsdate`),
		  KEY `redate` (`redate`),
		  KEY `sdate` (`sdate`),
		  KEY `edate` (`edate`),
		  KEY `room` (`room`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='VideoWhisper: Sessions 2015-2019@videowhisper.com' AUTO_INCREMENT=1 ;
		
		DROP TABLE IF EXISTS `$table_chatlog`;
		CREATE TABLE `$table_chatlog` (
		  `id` int(11) unsigned NOT NULL auto_increment,
		  `username` varchar(64) NOT NULL,
		  `user_id` int(11) unsigned NOT NULL,
		  `room` varchar(64) NOT NULL,
		  `room_id` int(11) unsigned NOT NULL,
		  `message` text NOT NULL,
		  `mdate` int(11) NOT NULL,
		  `type` tinyint(4) NOT NULL,
		  `private_uid` int(11) unsigned NOT NULL,
		  `meta` TEXT,
		  PRIMARY KEY  (`id`),
		  KEY `room` (`room`),
		  KEY `mdate` (`mdate`),
		  KEY `type` (`type`),
		  KEY `private_uid` (`private_uid`),
		  KEY `user_id` (`user_id`),
		  KEY `room_id` (`room_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='VideoWhisper: Chat Logs 2018-2019@videowhisper.com' AUTO_INCREMENT=1;
		
		";
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
				dbDelta($sql);

				if($wpdb->last_error !== '') echo 'Webcam 2 Way Videochat SQL setup error: ' . esc_html( $wpdb->last_error );

				if (!$installed_ver) add_option("vw2vc_db_version", $vw2vc_db_version);
				else update_option( "vw2vc_db_version", $vw2vc_db_version );

				$wpdb->flush();

				//update permalinks
				flush_rewrite_rules();
			}

		}


		//rewrite eula.txt

		static function init()
		{
			add_rewrite_rule( 'eula.txt$', 'index.php?vw2wvc_eula=1', 'top' );
			add_rewrite_rule( 'crossdomain.xml$', 'index.php?vw2wvc_crossdomain=1', 'top' );
			add_rewrite_rule('^videochat/([0-9a-zA-Z\.\-\s_]+)/?', 'index.php?vw2wvc_room=$matches[1]', 'top');

		}


		static function query_vars( $query_vars )
		{
			$query_vars[] = 'vw2wvc_eula';
			$query_vars[] = 'vw2wvc_crossdomain';
			$query_vars[] = 'vw2wvc_room';
			return $query_vars;
		}


		static function login_redirect( $redirect_to, $request, $user )
		{

			global $user;

			//wp_users & wp_usermeta
			//$user = get_userdata(get_current_user_id());

			if ( isset( $user->roles ) && is_array( $user->roles ) )
			{
				//check for admins
				if ( in_array( 'administrator', $user->roles ) )
				{
					// redirect them to the default place
					return $redirect_to;
				} else
				{

					$options = get_option('VWvideoChatOptions');

					if (!$options['loginRedirect']) return $redirect_to;
					if ($redirectPage = get_permalink($options['loginRedirect'])) return $redirectPage;
					return $redirect_to;


				}
			} else
			{
				return $redirect_to;
			}
		}


		static function parse_request( &$wp )
		{
			$error = '';

			if ( array_key_exists( 'vw2wvc_eula', $wp->query_vars ) )
			{
				$options = get_option('VWvideoChatOptions');
				echo wp_kses_post( html_entity_decode(stripslashes($options['eula_txt'])) );
				exit();
			}

			if ( array_key_exists( 'vw2wvc_crossdomain', $wp->query_vars ) )
			{
				$options = get_option('VWvideoChatOptions');
				echo esc_xml(html_entity_decode(stripslashes($options['crossdomain_xml'])));
				exit();
			}

			if ( array_key_exists( 'vw2wvc_room', $wp->query_vars ) )
			{
				$options = get_option('VWvideoChatOptions');

				$r = sanitize_file_name($wp->query_vars['vw2wvc_room']);


				//HLS if iOS detected
				$agent = $_SERVER['HTTP_USER_AGENT'];
				$Android = stripos($agent,"Android");
				$iOS = ( strstr($agent,'iPhone') || strstr($agent,'iPod') || strstr($agent,'iPad'));

			//current user	
			$current_user = wp_get_current_user();
			
			$userID = intval($current_user->ID);
			
			if ($userID == 0 )
			{
				
				//use a cookie for visitor username persistence
				if ($_COOKIE['htmlchat_username'] ?? false) $userName = sanitize_file_name($_COOKIE['htmlchat_username']);
				else
				{
					$userName =  'G_' . base_convert(time()%36 * rand(0, 36*36), 10, 36);
					setcookie('htmlchat_username', $userName);
				}
				$isVisitor = 1 ;
	
				$userkeys = array('Guest', 0);
			
			}
			else
			{
			$userName = urlencode($current_user->user_nicename);

			//access keys
			$userkeys = $current_user->roles;
			$userkeys[] = $userName;
			$userkeys[] = $current_user->user_login;
			$userkeys[] = $current_user->ID;
			$userkeys[] = $current_user->user_email;
			$userkeys[] = $current_user->display_name;
			}
			
		//room	
		$room = $r;
			
				global $wpdb;
				$table_name = $wpdb->prefix . "vw_2wsessions";				
				$table_videocalls = $wpdb->prefix . "vw_2wrooms";
				$table_sessions = $wpdb->prefix . "vw_2w_sessions";


				$rm = $wpdb->get_row("SELECT * FROM `$table_videocalls` where status='1' AND name = '$room'");
				

					if (!$rm)
					{
						$error = "Room $room is not available!";
					}
					else if (!VWvideoChat::inList($userkeys, $rm->access))
						{
							$loggedin=0;
							$error = "Access is not permitted in this room ($room)!";
						}				
					
					if ($error)
					{
					 echo esc_html( $error );
					 exit();	
					}
				
				
			//session		
				$isPerformer = $owner = ( $rm->owner == $userID ? 1 : 0 ) ;
				$roomID = $postID = intval($rm->id);
				$ztime=time();
	
				//clean old sessions
				$exptime=$ztime-$options['sessionExpire'];
				$sql="DELETE FROM `$table_sessions` WHERE edate < $exptime";
				$wpdb->query($sql);


				//clientSession in this room
				$clientSession = 0;
				if ($rm->client)
				{
				$sqlS = "SELECT * FROM `$table_sessions` where id=". $rm->client ." AND status='0'"; //only if client active status
				$clientSession = $wpdb->get_row($sqlS);
				}
	

				$ztime=time();

				$ip = self::get_ip_address();

				//online session
				$sqlS = "SELECT * FROM `$table_sessions` WHERE session='$userName' AND rid='$roomID' AND status='0' AND ip = '$ip' ORDER BY edate DESC LIMIT 0,1";
				$session = $wpdb->get_row($sqlS);

				$sessionID = 0;
			
			
				$userMeta = array();
				if (!$owner) $userMeta['watch'] = $rm->owner;
				else $userMeta['watch'] = $rm->client;
							
				if (!$session)
				{
					
					if ($clientSession && !$owner) if ($clientSession->ip != $ip || $clientSession->uid != $userID) //if same ip && uid allow overwrite (in case called repeatedly)
					{
						echo 'Call is Busy. Another client is already present: ' . esc_html( $clientSession->username) . ' Last: ' . date(DATE_RFC2822, $clientSession->edate ) ;
						echo '<br>Your username: ' . esc_html($userName);
						exit();
					}			
						
					$userMeta['createdBy'] = 'parse_request';
					if ($options['debugMode']) $userMeta['notFound'] = esc_sql ($sqlS);
					

					$userMetaS = esc_sql(serialize($userMeta));
					

					$sql="INSERT INTO `$table_sessions` ( `session`, `username`, `room`, `message`, `sdate`, `edate`, `status`, `type`, `uid`, `rid`, `ip`,`broadcaster`, `meta`) VALUES ('$userName', '$userName', '$room', '', $ztime, $ztime, 0, 1, $userID, $roomID, '$ip', $owner, '$userMetaS')";
					$wpdb->query($sql);


					$sessionID = $wpdb->insert_id;

					$session = $wpdb->get_row($sqlS);
				}
				else 
				{
					$sessionID = intval($session->id);
					
					$userMetaS = esc_sql(serialize($userMeta));

					$sql="UPDATE `$table_sessions` set edate=$ztime, meta='$userMetaS' where id ='$sessionID'";
					$wpdb->query($sql);

					$session = $wpdb->get_row($sqlS);
				}
				
				
				//current client
				if (!$owner) $wpdb->query("UPDATE `$table_videocalls` set client='$sessionID' where id ='$roomID'");

				self::autoMessage("👤$userName #$userID/$sessionID entered", $session);


//


	$k = 0;
	$dataCode = '';
	$cssCode = '';

	$CSSfiles = scandir(dirname(  __FILE__ ) . '/static/css/');
		foreach
		($CSSfiles as $filename)
			if (strpos($filename,'.css')&&!strpos($filename,'.css.map'))
				wp_enqueue_style( 'vw-call-app' . ++$k, plugin_dir_url(  __FILE__ ) . '/static/css/' . $filename);

			$countMain = 0;
		$countRuntime = 0;
		$JSfiles = scandir(dirname(  __FILE__ ) . '/static/js/');
		foreach ($JSfiles as $filename)
			if ( strpos($filename,'.js') && !strpos($filename,'.js.map')) // && !strstr($filename,'runtime~')
				{
				wp_enqueue_script('vw-call-app'. ++$k , plugin_dir_url(  __FILE__ ) . '/static/js/' . $filename, array(), '', true);

				if (!strstr($filename, 'LICENSE.txt')) if (substr($filename,0,5) == 'main.') $countMain++;
					if (!strstr($filename, 'LICENSE.txt')) if (substr($filename,0,7) =='runtime') $countRuntime++;
			}





			echo '<head>';
			wp_head();
			
			echo '</head>';
	
			echo '<body>';

			if ($countMain>1 || $countRuntime>1) $htmlCode .=   '<div class="ui segment red">Warning: Possible duplicate JS files in application folder! Only latest versions should be deployed.</div>';

	$ajaxurl = admin_url() . 'admin-ajax.php?action=vw_2w_app';

		$wlJS ='';
		if ($options['whitelabel']) $wlJS = ', checkWait: true, whitelabel: ' . sanitize_text_field( $options['whitelabel'] );
		$sessionKey = '';
		
		$dataCode .= "window.VideoWhisper = {userID: $userID, sessionID: $sessionID, sessionKey: '$sessionKey', roomID: $roomID, performer: $isPerformer, serverURL: '" . $ajaxurl . "' $wlJS}"; // each element sanitized previously

		
		$cssCode .= html_entity_decode(stripslashes( sanitize_textarea_field( $options['appCSS'] ) ));

echo <<<HTMLCODE
<!--VideoWhisper.com - HTML5 Videochat web app -->
<noscript>You need to enable JavaScript to run this app. For more details see <a href="https://paidvideochat.com/html5-videochat/">HTML5 Videochat</a> or <a href="https://videowhisper.com">contact HTML5 videochat developers</a>.</noscript>
<div id="videowhisperAppContainer"><div id="videowhisperVideochat"></div></div>
<script>$dataCode;
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


		//room link
			$roomURL = $_SERVER['REQUEST_SCHEME'] .'://'. $_SERVER['HTTP_HOST'] . explode('?', $_SERVER['REQUEST_URI'], 2)[0];
			if (!$_GET['r']) echo '<div class="ui segment"><h4 class="ui header">Call URL</h4>' . esc_url( $roomURL ) .'</div>';


			$state = 'block' ;
			if (!$options['videowhisper']) $state = 'none';
			if ($options['videowhisper']) echo'<div id="VideoWhisper" style="display: ' . esc_attr( $state ) . ';"><p>Powered by <a href="https://videowhisper.com">VideoWhisper Live Video Site Builder</a> / <a href="https://paidvideochat.com/html5-videochat/">HTML5 Videochat</a>.</p></div>';
		
			echo '</body>';

			wp_footer();
			echo '</body>';

				exit;
				}			

			return;
		}



		//if any key matches any listing
		static function inList($keys, $data)
		{
			if (!$keys) return 0;
			if (!trim($data)) return 0;
			if (strtolower(trim($data)) == 'all') return 1;
			if (strtolower(trim($data)) == 'none') return 0;

			$list=explode(",", strtolower(trim($data)));
			if (in_array('all', $list)) return 1;

			foreach ($keys as $key)
				foreach ($list as $listing)
					if (strtolower(trim($key)) == trim($listing) ) return 1;

					return 0;
		}


		static function path2url($file, $Protocol='http://')
		{
			$url = $Protocol.$_SERVER['HTTP_HOST'];


			//on godaddy hosting uploads is in different folder like /var/www/clients/ ..
			$upload_dir = wp_upload_dir();
			if (strstr($file, $upload_dir['basedir']))
				return  $upload_dir['baseurl'] . str_replace($upload_dir['basedir'], '', $file);

			if (strstr($file, $_SERVER['DOCUMENT_ROOT']))
				return  $url . str_replace($_SERVER['DOCUMENT_ROOT'], '', $file);

			return $url . $file;
		}



		static function roomLink($room)
		{
			$options = get_option('VWvideoChatOptions');

			switch ($options['roomLink'])
			{
			case 'rewrite':
				return site_url() . '/videochat/' . urlencode(sanitize_file_name($room));
				break;

			case 'plugin':
				return plugin_dir_url( __FILE__ ) . '2wvc/?r=' . urlencode($room);
				break;
			}
		}


		static function enqueueUI()
		{
			wp_enqueue_script("jquery");

			wp_enqueue_style( 'semantic', plugin_dir_url(  __FILE__ ) . '/js/semantic/semantic.min.css');
			wp_enqueue_script( 'semantic', plugin_dir_url(  __FILE__ ) . '/js/semantic/semantic.min.js', array('jquery'));

			//wp_enqueue_style( 'fomantic', 'https://cdn.jsdelivr.net/npm/fomantic-ui@2.8.7/dist/semantic.min.css');
			//wp_enqueue_script( 'fomantic', 'https://cdn.jsdelivr.net/npm/fomantic-ui@2.8.7/dist/semantic.min.js', array('jquery'));
		}


		static function videowhisper_videochat_manage()
		{

			self::enqueueUI();
?>

		<script language="JavaScript">
		function censorName()
			{
				document.adminForm.room.value = document.adminForm.room.value.replace(/^[\s]+|[\s]+$/g, '');
				document.adminForm.room.value = document.adminForm.room.value.replace(/[^0-9a-zA-Z_\-]+/g, '-');
				document.adminForm.room.value = document.adminForm.room.value.replace(/\-+/g, '-');
				document.adminForm.room.value = document.adminForm.room.value.replace(/^\-+|\-+$/g, '');
				if (document.adminForm.room.value.length>0) return true;
				else
				{
				alert("A room name is required!");
				document.adminForm.button.disabled=false;
				document.adminForm.button.value="Create";
				return false;
				}
			}
			</script>

		<?php

			global $wpdb;

			$this_page    =   $_SERVER['REQUEST_URI'];

			//can user create room?
			$options = get_option('VWvideoChatOptions');
			$canBroadcast = $options['canBroadcast'];
			$broadcastList = $options['broadcastList'];
			$userName =  $options['userName']; if (!$userName) $userName='user_nicename';

			$loggedin=0;

			$current_user = wp_get_current_user();
			if ($current_user->$userName) $username=$current_user->$userName;

			//access keys
			$userkeys = $current_user->roles;
			$userkeys[] = $current_user->user_login;
			$userkeys[] = $current_user->ID;
			$userkeys[] = $current_user->user_email;
			$userkeys[] = $current_user->display_name;

			switch ($canBroadcast)
			{

			case "members":
				if ($username) $loggedin=1;
				else $msg = __('Please login first or register an account if you do not have one!','vw2wvc');
				break;
			case "list";
				if ($username)
					if (VWvideoChat::inList($userkeys, $broadcastList)) $loggedin=1;
					else $msg = __('You are not allowed to setup rooms!','vw2wvc');
					else $msg =  __('Please login first or register an username if you do not have one!', 'vw2wvc');
					break;
			}


			if (!$loggedin)
			{
				echo '<p>' . esc_html( $msg ) . '</p>';
				echo '<p>' . __('This pages allows creating and managing video chat rooms for register members that have this feature enabled.') . '</p>';
			}

			if ($loggedin)
			{
				$table_name = $wpdb->prefix . "vw_2wsessions";
				$table_videocalls = $wpdb->prefix . "vw_2wrooms";
				$table_sessions = $wpdb->prefix . "vw_2w_sessions";

				//delete
				if ($delid=intval($_GET['delete'] ?? 0 ))
				{
					$sql = $wpdb->prepare("DELETE FROM $table_videocalls where owner='".$current_user->ID."' AND id='%d'", array($delid));
					$wpdb->query($sql);
					$wpdb->flush();
					echo "<div class='update'>Room #" .esc_html( $delid ). "  was deleted.</div>";
				}

				//! create room
				$room = sanitize_file_name($_POST['room'] ?? '');
				if ($room) echo VWvideoChat::createRoom($room, $current_user, sanitize_text_field($_POST['access']) );

				//! auto room
				if (in_array($options['autoRoom'], array('manage','always') ) ) VWvideoChat::createRoom($username, $current_user);

				//clean online users
				$ztime=time();
				$exptime=$ztime-$options['sessionExpire'];
				$sql="DELETE FROM `$table_name` WHERE edate < $exptime";
				$wpdb->query($sql);

				//! list rooms
				$wpdb->flush();

				$sql = "SELECT * FROM $table_videocalls where owner='". $current_user->ID ."'";
				$rooms=$wpdb->get_results($sql);

				$roomsNo = count($rooms);

				if ($options['maxRooms']) $roomsNo .=  '/' . $options['maxRooms'];

				echo '<H3 class="ui header">' . __('My Calls', 'vw2wvc') . ' (' . esc_html( $roomsNo ) . ')</H3>';
				if (count($rooms))
				{
					echo '<table class="ui celled striped table">';
					echo '<tr><th>' . __('Call Booth', 'vw2wvc') . '</th><th>' . __('Link to Share', 'vw2wvc') . '</th><th>' . __('Online', 'vw2wvc') . '</th><th>' . __('Access', 'vw2wvc') . '</th><th>' . __('Manage', 'vw2wvc') . '</th></tr>';
					$root_url = plugins_url() . "/";
					foreach ($rooms as $rd)
					{
						$rm=$wpdb->get_row("SELECT count(*) as no, group_concat(username separator ' <BR> ') as users, room as room FROM `$table_sessions` where status='0' AND room='". sanitize_text_field( $rd->name ) ."' GROUP BY room");

						echo '<tr> <td><a href="' . VWvideoChat::roomLink(esc_attr( $rd->name )) . '"><B>' . esc_html( $rd->name ) . '</B></a> </td> <td>' ;
						if ($options['appEnabled']) if ($options['appSchema']) echo ' <br>Web: ';
							echo  VWvideoChat::roomLink(esc_attr( $rd->name ));
						if ($options['appEnabled']) if ($options['appSchema']) echo ' <br>App: '.esc_html( $options['appSchema'] ).'://call?room='.urlencode(esc_html( $rd->name )).'';
							echo  '</td> <td>' . ( ($rm->no ?? 0) > 0 ? wp_kses_post( $rm->users ) :'0') . '</td> <td>' . esc_html( $rd->access ) . '</td> <td><a href="' . esc_url( $this_page ) . (strstr($this_page,'?')?'&':'?') . 'delete=' . esc_attr( $rd->id ).'">' . __('Delete', 'vw2wvc') . '</a> <BR><a href="' . VWvideoChat::path2url($options['uploadsPath']) . '/' . urlencode(esc_attr( $rd->name) ) . '/">' . __('Logs', 'vw2wvc') . '</a> </td> </tr>';
					}
					echo "</table>";

				}
				else _e('You do not currently have any rooms.','vw2wvc');


				//! create room form
				if (!$room && (!$options['maxRooms'] || count($rooms) < $options['maxRooms']) )
					echo '<h3 class="ui header">' . __('Setup a New Call', 'vw2wvc') . '</h3><form class="ui form" method="post" action="' . esc_attr( $this_page ) .'"  name="adminForm">
		  ' . __('Name', 'vw2wvc') . ' <input name="room" type="text" id="room" value="' . esc_attr( $current_user->user_nicename ) . '_'.base_convert((time()-1225000000).rand(0,10),10,36).'" size="20" maxlength="64" onChange="censorName()"/>
		  <br>' . __('Access List', 'vw2wvc') . ' <input name="access" type="text" id="access" value="all" size="64" maxlength="255""/>
  <BR><input type="submit" class="ui button" name="button" id="button" value="' . __('Create', 'vw2wvc') . '" onclick="censorName(); adminForm.submit();"/>
		</form>
		';
		
			$state = 'block' ;
			if (!$options['videowhisper']) $state = 'none';
			if ($options['videowhisper']) echo '<div id="VideoWhisper" style="display: ' . esc_attr( $state ) . ';"><p>Powered by <a href="https://videowhisper.com">VideoWhisper - Turnkey Live Video Sites</a>.</p></div>';
			
			}

		}

		static function createRoom($room, $user, $access = 'all')
		{

			$room = sanitize_file_name($room);

			if (!$room) return  '<div class="error">' . __('No room name. Use valid characters!', 'vw2wvc') . '</div>';

			$access = sanitize_text_field($access);

			global $wpdb;
			$table_videocalls = $wpdb->prefix . "vw_2wrooms";

			$wpdb->flush();
			$ztime=time();

			$sql = $wpdb->prepare("SELECT owner FROM $table_videocalls where name='%s'", array($room));
			$rdata = $wpdb->get_row($sql);
			if (!$rdata)
			{
				$sql=$wpdb->prepare("INSERT INTO `$table_videocalls` ( `name`, `access`, `owner`, `sdate`, `edate`, `status`, `type`, `client`) VALUES ('%s', '%s','" . $user->ID . "', '$ztime', '0', 1, 1, 0)", array($room, $access));
				$wpdb->query($sql);
				
				if ($wpdb->last_error !== '') return  '<div class="ui message error">' . $wpdb->last_error. '</div>';
				
				return '<div class="ui message update">' . sprintf(__('Room "%s" was created.', 'vw2wvc'), $room) . '</div>';
			}
			else
			{
				return '<div class="ui message error">' .sprintf( __('Room name "%s" is already in use. Please choose another name!', 'vw2wvc'),$room) . '</div>';
				$room="";
			}

		}


		static function user_register($user_id)
		{

			//create room when user registers

			$options = get_option('VWvideoChatOptions');

			if (!in_array($options['autoRoom'], array('register','always') ) ) return; //not enabled

			//can user create room?
			$canBroadcast = $options['canBroadcast'];
			$broadcastList = $options['broadcastList'];
			$userName =  $options['userName']; if (!$userName) $userName='user_nicename';

			$loggedin=0;

			$current_user = get_userdata($user_id);

			if ($current_user->$userName) $username=urlencode($current_user->$userName);

			//access keys
			$userkeys = $current_user->roles;
			$userkeys[] = $current_user->user_login;
			$userkeys[] = $current_user->ID;
			$userkeys[] = $current_user->user_email;
			$userkeys[] = $current_user->display_name;

			switch ($canBroadcast)
			{

			case "members":
				if ($username) $loggedin=1;
				else $msg=urlencode(__('Please login first or register an account if you do not have one!','vw2wvc'));
				break;
			case "list";
				if ($username)
					if (VWvideoChat::inList($userkeys, $broadcastList)) $loggedin=1;
					else $msg=urlencode(__('You are not allowed to setup rooms!','vw2wvc'));
					else $msg=urlencode( __('Please login first or register an username if you do not have one!', 'vw2wvc') );
					break;
			}

			if ($loggedin) if (in_array($options['autoRoom'], array('register','always') ) ) VWvideoChat::createRoom($username, $current_user);

		}


	}


}

//instantiate
if (class_exists("VWvideoChat"))
{
	$videoChat = new VWvideoChat();
}

//Actions and Filters
if (isset($videoChat))
{
	add_action( 'plugins_loaded', array(&$videoChat, 'plugins_loaded'));

	//backend menus
	add_action( 'admin_menu', array(&$videoChat, 'admin_menu'));
	add_action( 'admin_bar_menu', array(&$videoChat, 'admin_bar_menu'),90 );

	add_action( 'init', array(&$videoChat, 'init'));
	add_filter( 'query_vars', array(&$videoChat, 'query_vars'));
	add_action( 'parse_request', array(&$videoChat, 'parse_request'));

	add_filter( 'login_redirect', array('VWvideoChat','login_redirect'), 10, 3 );

	add_action( 'user_register', array('VWvideoChat','user_register'), 10, 1 );

	register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
}

?>
