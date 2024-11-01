<?php
namespace VideoWhisper\VideoCalls;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait Options {
	//define and edit settings


	//! Admin Side

		static function admin_bar_menu($wp_admin_bar)
		{
			if (!is_user_logged_in()) return;

			$options = get_option('VWvideoChatOptions');

			if( current_user_can('editor') || current_user_can('administrator') ) {


				//find VideoWhisper menu
				$nodes = $wp_admin_bar->get_nodes();
				if (!$nodes) $nodes = array();
				$found = 0;
				foreach ( $nodes as $node ) if ($node->title == 'VideoWhisper') $found = 1;

					if (!$found)
					{
						$wp_admin_bar->add_node( array(
								'id'     => 'videowhisper',
								'title' => 'VideoWhisper',
								'href'  => admin_url('plugin-install.php?s=videowhisper&tab=search&type=term'),
							) );

						//more VideoWhisper menus

						$wp_admin_bar->add_node( array(
								'parent' => 'videowhisper',
								'id'     => 'videowhisper-add',
								'title' => __('Add Plugins', 'paid-membership'),
								'href'  => admin_url('plugin-install.php?s=videowhisper&tab=search&type=term'),
							) );

						$wp_admin_bar->add_node(
								array(
									'parent' => 'videowhisper',
									'id'     => 'videowhisper-consult',
									'title'  => __( 'Consult Developers', 'paid-membership' ),
									'href'   => 'https://consult.videowhisper.com/'),
								);
							
						$wp_admin_bar->add_node( array(
								'parent' => 'videowhisper',
								'id'     => 'videowhisper-contact',
								'title' => __('Contact Support', 'paid-membership'),
								'href'  => 'https://videowhisper.com/tickets_submit.php?topic=WordPress+Plugins+' . urlencode($_SERVER['HTTP_HOST']),
							) );
					}


				$menu_id = 'videowhisper-videocalls';

				$wp_admin_bar->add_node( array(
						'parent' => 'videowhisper',
						'id'     => $menu_id,
						'title' => '👥 ' . 'VideoCalls',
						'href'  => admin_url('admin.php?page=videocalls')
					) );

						$wp_admin_bar->add_node( array(
						'parent' => $menu_id,
						'id'     => $menu_id . '-settings',
						'title' => __('Settings', 'ppv-live-webcams'),
						'href'  => admin_url('admin.php?page=videocalls')
					) );


				$wp_admin_bar->add_node( array(
						'parent' => $menu_id,
						'id'     => $menu_id . '-hosting',
						'title' => __('Streaming Hosting', 'ppv-live-webcams'),
						'href'  => 'https://webrtchost.com/hosting-plans/'
					) );

				$wp_admin_bar->add_node( array(
						'parent' => $menu_id,
						'id'     => $menu_id . '-turnkey',
						'title' => __('Turnkey Plans', 'ppv-live-webcams'),
						'href'  => 'https://paidvideochat.com/order/'
					) );
			}
			
			$current_user = wp_get_current_user();

			if ($options['p_videowhisper_videochat_manage'] ?? false)
				if ( $options['canBroadcast'] == 'members' || self::inList($current_user->roles, $options['broadcastList'] . ',administrator,super-admin' ) )
					$wp_admin_bar->add_node(array(
							'parent' => 'my-account',
							'id'     => 'videowhisper_videochat_manage',
							'title' => '👥 ' . __('Video Calls', 'ppv-live-webcams') ,
							'href'  =>  get_permalink($options['p_videowhisper_videochat_manage']),
						));
	
			if ($options['p_videowhisper_videochat_random'] ?? false)
			if ( $options['canBroadcast'] == 'all' || self::inList($current_user->roles, $options['watchList'] . ',administrator,super-admin' ) )
				$wp_admin_bar->add_node(array(
						'parent' => 'my-account',
						'id'     => 'videowhisper_videochat_random',
						'title' => '🎲 ' . __('Random Videochat', 'ppv-live-webcams') ,
						'href'  =>  get_permalink($options['p_videowhisper_videochat_random']),
					));				

		}

		
			static function admin_menu() {

			add_menu_page('VideoCalls', 'VideoCalls', 'manage_options', 'videocalls', array('VWvideoChat', 'adminOptions'), 'dashicons-video-alt2',83);

			add_submenu_page("videocalls", "Settings for Live Webcams", "Settings", 'manage_options', "videocalls-settings", array('VWvideoChat', 'adminOptions'));
	
			//hide add submenu
			$options = get_option('VWvideoChatOptions');
			global $submenu;
			unset($submenu['edit.php?post_type=' . ($options['custom_post'] ?? 'videocall') ][10]);
		}
		
		static function settings_link($links) {
			$settings_link = '<a href="admin.php?page=videocalls">'.__("Settings").'</a>';
			array_unshift($links, $settings_link);
			return $links;
		}



	//! Feature Pages and Menus
	
	static function setupPagesList($options = null)
	{

	if (!$options) $options = get_option('VWvideoChatOptions');

		
	//shortcode pages
	 return array(
			'videowhisper_videochat_manage' => __('Video Calls', 'ppv-live-webcams'),
			'videowhisper_videochat_random' => __('Random Videochat', 'ppv-live-webcams'),
			
			);	
	}
	
	static function setupPagesContent($options = null)
	{

	if (!$options) $options = get_option('VWvideoChatOptions');

	return [];
	}
	
	static function setupPages()
	{
				
		$options = get_option('VWvideoChatOptions');
		if ($options['disableSetupPages']) return;

		$pages = self::setupPagesList();

		$noMenu = array();

		$parents = [
		'videowhisper_videochat_manage' => [ 'Performer', 'Performer Dashboard' ],
		];
		
		//custom content (not shortcode)
		$content = self::setupPagesContent(); 
				
		$duplicate = [];

		//create a menu and add pages
		$menu_name = 'VideoWhisper';
		$menu_exists = wp_get_nav_menu_object( $menu_name );

		if (!$menu_exists) $menu_id = wp_create_nav_menu($menu_name);
		else $menu_id = $menu_exists->term_id;
		$menuItems = [];

		//create pages if not created or existant
		foreach ($pages as $key => $value)
		{
			$pid = $options['p_'.$key] ?? 0;
			$page = get_post($pid);
			if (!$page) $pid = 0;

			if (!$pid)
			{
				global $user_ID;
				$page = array();
				$page['post_type']    = 'page';				
				$page['post_parent']  = 0;
				$page['post_status']  = 'publish';
				$page['post_title']   = $value;
				$page['comment_status'] = 'closed';

				if (array_key_exists($key, $content)) $page['post_content'] = $content[$key]; //custom content
				else $page['post_content'] = '['.$key.']';

				$pid = wp_insert_post ($page);

				$options['p_'.$key] = $pid;
				$link = get_permalink( $pid);
				
				//get updated menu
				if ($menu_id) $menuItems = wp_get_nav_menu_items($menu_id,  array('output' => ARRAY_A));

				//find if menu exists, to update
				$foundID = 0;
				foreach ($menuItems as $menuitem) if ($menuitem->title == $value) 
					{
						$foundID = $menuitem->ID;
						break;
					}
					
				if (!in_array($key, $noMenu))
					if ($menu_id)
					{
						//select menu parent
						$parentID = 0;
						if (array_key_exists($key, $parents))
							foreach ($parents[$key] as $parent)
								foreach ($menuItems as $menuitem) if ($menuitem->title == $parent)
									{
										$parentID =  $menuitem->ID;
										break 2;
									}

								//update menu for page
								$updateID = wp_update_nav_menu_item($menu_id, $foundID, array(
										'menu-item-title' =>  $value,
										'menu-item-url' => $link,
										'menu-item-status' => 'publish',
										'menu-item-object-id' => $pid,
										'menu-item-object' => 'page',
										'menu-item-type' => 'post_type',
										'menu-item-parent-id' => $parentID,           )
								);
								
								//duplicate menu, only first time for main menu
								if (!$foundID) if (!$parentID) if (intval($updateID)) 
								if (in_array($key, $duplicate)) 		
								wp_update_nav_menu_item($menu_id, 0, array(
										'menu-item-title' =>  $value,
										'menu-item-url' => $link,
										'menu-item-status' => 'publish',
										'menu-item-object-id' => $pid,
										'menu-item-object' => 'page',
										'menu-item-type' => 'post_type',
										'menu-item-parent-id' => $updateID,           )
								);
						
					}

			}

		}

		update_option('VWvideoChatOptions', $options);
	}


		//! Pages
		
		
		/*
		static function updatePages()
		{

			global $user_ID;
			$page = array();
			$page['post_type']    = 'page';
			$page['post_content'] = '[videowhisper_videochat_manage]';
			$page['post_parent']  = 0;
			$page['post_author']  = $user_ID;
			$page['post_status']  = 'publish';
			$page['post_title']   = 'Video Chat';

			$page_id = get_option("vw_2vc_page_room");
			if ($page_id>0) $page['ID'] = $page_id;

			$pageid = wp_insert_post ($page);

			update_option( "vw_2vc_page_room", $pageid);
		}

		static function deletePages()
		{
			$page_id = get_option("vw_2vc_page_room");
			if ($page_id > 0)
			{
				wp_delete_post($page_id);
				update_option( "vw_2vc_page_room", -1);
			}
		}
*/

		//! Widget
		static function widgetContent()
		{
			global $wpdb;
			$table_name = $wpdb->prefix . "vw_2wsessions";

			$root_url = plugins_url();

			$options = get_option('VWvideoChatOptions');

			//clean online sessions
			$exptime=time() - intval($options['sessionExpire']);
			$sql="DELETE FROM `$table_name` WHERE edate < $exptime";
			$wpdb->query($sql);
			$wpdb->flush();

			$items =  $wpdb->get_results("SELECT count(*) as no, group_concat(username separator ', ') as users, room as room FROM `$table_name` where status='1' and type='1' GROUP BY room");

			echo "<ul>";

			if ($items)
				foreach ($items as $item)
				{
					if ($item->no<2) echo "<li><a href='" . add_query_arg( 'r', esc_attr( $item->room ) ,  plugins_url( '2wvc/', dirname(__FILE__) ) ) ."'><B>". esc_html( $item->room ) ."</B> (". esc_html( $item->users ) .") " .( $item->message ? ": " . esc_html( $item->message ) :"") ."</a></li>";
					else echo "<li><B>".esc_html( $item->room )."</B> (". esc_html( $item->users ) .") ".( $item->message ? ": ". esc_html( $item->message ) :"") ."</li>";
				}
			else echo "<li>No users online.</li>";
			echo "</ul>";

			$state = 'block' ;
			if (!$options['videowhisper']) $state = 'none';
			if ($options['videowhisper']) echo '<div id="VideoWhisper" style="display: ' . esc_attr( $state ) . ';"><p>Powered by VideoWhisper <a href="https://videowhisper.com/?p=WordPress-Webcam-2Way-VideoChat">WordPress VideoChat</a>.</p></div>';
		}

		static function widget($args) {
			extract($args);
			echo esc_html( $before_widget );
			echo esc_html( $before_title );?>Video Chat<?php echo esc_html( $after_title );
			self::widgetContent();
			echo esc_html( $after_widget );
		}


		//! Options
		
		static function getAdminOptions() {

		$adminOptions = self::adminOptionsDefault();

		$options = get_option('VWvideoChatOptions');

		if (!empty($options)) {
			foreach ($options as $key => $option)
				$adminOptions[$key] = $option;
		}

		update_option('VWvideoChatOptions', $adminOptions);

		return $adminOptions;
	}


	static function getOptions()
	{
		$options = get_option('VWvideoChatOptions');
		if (!$options) $options =  self::adminOptionsDefault();

		return $options;
	}

	//! Options

	static function adminOptionsDefault()
	{

			$upload_dir = wp_upload_dir();

			$root_url = plugins_url();
			//$root_path = plugin_dir_path( __FILE__ );
			$root_ajax = admin_url( 'admin-ajax.php?action=v2wvc&task=');

			return array(

			'debugMode' => 0,
			'matchmaking' => 1,
			'gendersList' => 'Male,Female,Other',
			'genders' => unserialize('a:3:{i:0;s:4:"Male";i:1;s:6:"Female";i:2;s:5:"Other";}'),

			'disableSetupPages' => 0,
				
			'interfaceClass' => '',

			'serverType' => 'videowhisper',
			'vwsSocket' => '',
			'vwsToken' => '',

			'webrtc' =>'0', //enable webrtc
			'wsURLWebRTC' => 'wss://[wowza-server-with-ssl]:[port]/webrtc-session.json', // Wowza WebRTC WebSocket URL (wss with SSL certificate)
			'applicationWebRTC' => '[application-name]', // Wowza Application Name (configured or WebRTC usage)

			'webrtcVideoCodec' =>'VP8',
			'webrtcAudioCodec' =>'opus',

			'webrtcVideoBitrate' => 500,
			'webrtcAudioBitrate' => 32,
			
			'flash' => 0, 
			
			'whitelabel' => 0,

			'appComplexity' => 1,
			'appSiteMenu' => -1,

						'appSetup' => unserialize('a:2:{s:6:"Config";a:13:{s:8:"darkMode";s:0:"";s:19:"cameraAutoBroadcast";s:1:"1";s:22:"cameraAutoBroadcastAll";s:1:"1";s:14:"cameraControls";s:1:"1";s:13:"videoAutoPlay";s:0:"";s:16:"resolutionHeight";s:3:"360";s:7:"bitrate";s:3:"500";s:12:"audioBitrate";s:2:"32";s:9:"frameRate";s:2:"15";s:19:"maxResolutionHeight";s:4:"1080";s:10:"maxBitrate";s:4:"3500";s:12:"timeInterval";s:4:"5000";s:15:"recorderMaxTime";s:3:"300";}s:4:"User";a:5:{s:7:"h5v_sfx";s:0:"";s:8:"h5v_dark";s:0:"";s:9:"h5v_audio";s:0:"";s:10:"h5v_reveal";s:0:"";s:17:"h5v_reveal_warmup";s:2:"30";}}'),
			'appSetupConfig' => '
; This configures HTML5 Videochat application and other apps that use same API.

[Config]						; Application settings
darkMode = false 			 	; true/false : start app in dark mode
cameraAutoBroadcast = true	; true/false : start broadcast automatically for owner
cameraAutoBroadcastAll = true	; true/false : start broadcast automatically for all
cameraControls = true 			; true/false : broadcast control panel
videoAutoPlay = false 			; true/false : try to play video without broadcaster notification
resolutionHeight = 360			; streaming resolution height, maximum 360p in free mode
bitrate = 500					; streaming bitrate in kbps, maximum 750kbps in free mode
audioBitrate = 32				; streaming audio bitrate in kbps, maximum 32kbps in free mode
frameRate = 15					; streaming frames per second, maximum 15fps in free mode
maxResolutionHeight = 1080 		; maximum selectable resolution height, maximum 480p in free mode
maxBitrate = 3500				; maximum selectable streaming bitrate in kbps, maximum 750kbps in free mode, also limited by hosting
timeInterval = 5000				; chat and interaction update in milliseconds, if no action taken by user, min 2000ms
recorderMaxTime = 300			; maximum recording time in seconds, limited in free mode

[User]				; Defaults for user preferences
h5v_sfx = false     	 	; true/false : User sound effects preference
h5v_dark = false     	 ; true/false : User dark mode preference
h5v_audio = false   	  ; true/false : User audio only mode (no webcam)
h5v_reveal = false      	; true/false : Reveal mode with microphone only until webcam is started with specific Reveal button, requires audio only disabled
h5v_reveal_warmup = 30      ; Number of seconds required before webcam can be revealed
								',

			'appCSS' => '
.ui.button
{
width: auto !important;
height: inherit !important;
}

.ui .item
{
 margin-top: 0px !important;
}

.ui.modal>.content
{
margin: 0px !important;
}
.ui.header .content
{
background-color: inherit !important;
}

.site-inner
{
max-width: 100%;
}

.panel
{
padding: 0px !important;
margin: 0px !important;
}						
			',

				'userName' => 'display_name',
				'rtmp_server' => 'rtmp://localhost/videowhisper',
				'rtmp_amf' => 'AMF3',

				'canBroadcast' => 'members',
				'broadcastList' => 'Super Admin, Administrator, Editor, Author',
				'canWatch' => 'all',
				'watchList' => 'Super Admin, Administrator, Editor, Author, Contributor, Subscriber',
				'autoRoom' => 'manage',
				'maxRooms' => '5',
				'welcome'=> 'Welcome to video chat room! High quality snapshots of other person can be taken on request.',
				'loginRedirect' => '0',
				'sessionExpire' => '60',
				'parameters' => '&limitByBandwidth=1&camPicture=0&showCamSettings=1&silenceLevel=0&silenceTimeout=0&micGain=50&showTimer=1&showCredit=1&disconnectOnTimeout=1&camWidth=640&camHeight=480&camFPS=30&disableEmoticons=0&showTextChat=1&sendTextChat=1&webfilter=0&serverProxy=best&verboseLevel=4&disableVideo=0&disableSound=0&bufferLive=0&bufferFull=0&bufferLivePlayback=0&bufferFullPlayback=0&autoSnapshots=1&snapshotsTime=300000&configureConnection=1&configureSource=0&enableNext=0&enableBuzz=1&enableSoundFx=1&requestSnapshot=1&enableButtonLabels=1&enableFullscreen=1&enableSwap=1&enableLogout=1&enableLogo=1&enableHeaders=1&enableTitles=1&videoW=480&videoH=365&video2W=480&video2H=365&adsInterval=600000&adsTimeout=20000&pushToTalk=0&silenceToTalk=0',
				'layoutCode' => 'id=soundfx&x=766&y=571; id=bFul&x=15&y=105; id=VideoSlot2&x=510&y=140; id=ChatSlot&x=250&y=505; id=VideoSlot1&x=10&y=140; id=TextInput&x=250&y=670; id=head2&x=510&y=100; id=logo&x=389&y=25; id=bSnd&x=920&y=107; id=head&x=10&y=100; id=next&x=186&y=521; id=bVid&x=885&y=109; id=connection&x=186&y=571; id=bLogout&x=950&y=10; id=bFul2&x=955&y=105; id=bSwap&x=120&y=111; id=bSwap2&x=850&y=111; id=snapshot&x=766&y=621; id=camera&x=186&y=621; id=bCam&x=85&y=109; id=bMic&x=50&y=107; id=buzz&x=766&y=521',
				'layoutCodeMobile' => 'id=VideoBackground2&x=0&y=130&w=1280&h=720&z=2; id=head&x=1280&y=40&w=640&h=70&z=11; id=TextInput&x=1280&y=980&w=480&h=60&z=6; id=bVid&x=30&y=30&w=70&h=70&z=17&m=1.5; id=ChatSlot&x=1280&y=470&w=640&h=500&z=5; id=soundfx&x=660&y=980&w=240&h=60&z=25&m=2.0; id=head2&x=0&y=60&w=1280&h=70&z=12; id=bSend&x=1790&y=974&w=140&h=60&z=21&m=1.4; id=bSwap&x=120&y=111&w=48&h=48&z=19; id=snapshot&x=340&y=980&w=240&h=60&z=27&m=2.0; id=bSwap2&x=850&y=111&w=48&h=48&z=20; id=VideoSlot2&x=0&y=130&w=1280&h=720&z=4; id=logo&x=500&y=0&w=520&h=60&z=22; id=undefined&x=0&y=0&w=1920&h=1080&z=0; id=Timers&x=980&y=880&w=200&h=23.45&z=8; id=bCam&x=1720&y=20&w=70&h=70&z=15&m=1.5; id=VideoBackground1&x=1280&y=110&w=640&h=360&z=1; id=bMic&x=1820&y=20&w=70&h=70&z=16&m=1.5; id=title2&x=180&y=66&w=1100&h=60&z=24; id=bSnd&x=130&y=30&w=70&h=70&z=18&m=1.5; id=title&x=1280&y=46&w=460&h=60&z=23; id=VideoSlot1&x=1280&y=110&w=640&h=360&z=3; id=buzz&x=980&y=980&w=240&h=60&z=26&m=2.0; id=bLogout&x=1150&y=30&w=64&h=64&z=24&m=1.5; id=flag&x=50&y=880&w=82.5h=32&z=27&m=1.5;',
				'parametersMobile' => '&limitByBandwidth=0&camPicture=0&showCamSettings=0&silenceLevel=0&silenceTimeout=0&micGain=50&showTimer=1&showCredit=1&disconnectOnTimeout=1&camWidth=1280&camHeight=720&camFPS=15&disableEmoticons=0&showTextChat=1&sendTextChat=1&chatTextSize=32&webfilter=0&serverProxy=best&verboseLevel=4&disableVideo=0&disableSound=0&bufferLive=0&bufferFull=0&bufferLivePlayback=0&bufferFullPlayback=0&autoSnapshots=1&snapshotsTime=600000&configureConnection=0&configureSource=0&enableNext=0&enableBuzz=1&enableSoundFx=1&requestSnapshot=1&enableButtonLabels=1&enableFullscreen=1&enableSwap=1&enableLogout=1&enableLogo=1&enableHeaders=1&enableTitles=1&videoW=640&videoH=360&video2W=1280&video2H=720&adsInterval=0&adsTimeout=0&pushToTalk=0&silenceToTalk=0',
				'translationCode' => '<t text="Successfully connected to RTMFP server." translation="Successfully connected to RTMFP server."/>
<t text="External Encoder" translation="External Encoder"/>
<t text="Toggle Sound Effects" translation="Toggle Sound Effects"/>
<t text="Buzz!" translation="Buzz!"/>
<t text="Swap Panels" translation="Swap Panels"/>
<t text="LogOut" translation="LogOut"/>
<t text="Sound Effects" translation="Sound Effects"/>
<t text="Toggle Audio" translation="Toggle Audio"/>
<t text="Camera" translation="Camera"/>
<t text="Toggle Video" translation="Toggle Video"/>
<t text="Next!" translation="Next!"/>
<t text="Server Connection" translation="Server Connection"/>
<t text="Microphone" translation="Microphone"/>
<t text="Server / P2P" translation="Server / P2P"/>
<t text="Entering room" translation="Entering room"/>
<t text="Successfully connected to RTMP server." translation="Successfully connected to RTMP server."/>
<t text="Connecting to RTMFP server." translation="Connecting to RTMFP server."/>
<t text="FullScreen" translation="FullScreen"/>
<t text="Toggle Microphone" translation="Toggle Microphone"/>
<t text="Toggle Webcam" translation="Toggle Webcam"/>
<t text="joined" translation="joined"/>
<t text="Save Photo in Logs" translation="Save Photo in Logs"/>
<t text="Translation XML was copied to clipboard. Just paste it in a text editor." translation="Translation XML was copied to clipboard. Just paste it in a text editor."/>
<t text="Snapshot" translation="Snapshot"/>
<t text="No Connection" translation="No Connection"/>
<t text="Webcam / External Encoder" translation="Webcam / External Encoder"/>',

				'camBandwidth' => '75000',
				'camMaxBandwidth' => '200000',

				'videoCodec'=>'H263',
				'codecProfile' => 'main',
				'codecLevel' => '3.1',

				'soundCodec'=> 'Nellymoser',
				'soundQuality' => '9',
				'micRate' => '22',

				'overLogo' => dirname(plugin_dir_url(  __FILE__ )) .'2wvc/logo.png',
				'overLink' => 'https://videowhisper.com',

				'tokenKey' => 'VideoWhisper',
				'webKey' => 'VideoWhisper',

				'serverRTMFP' => 'rtmfp://stratus.adobe.com/f1533cc06e4de4b56399b10d-1a624022ff71/',
				'p2pGroup' => 'VideoWhisper',
				'supportRTMP' => '1',
				'supportP2P' => '0',
				'alwaysRTMP' => '1',
				'alwaysP2P' => '0',
				'disableBandwidthDetection' => '1',
				'videowhisper' => 0,
				'disablePage' => '0',
				'uploadsPath' => $upload_dir['basedir'] . '/2wvc',
				'adServer' => $root_ajax .'2_ads',
				'roomLink' => 'rewrite',
				'appEnabled' => '0',
				'appSchema' => 'vw2wvc',
				'eula_txt' =>'The following Terms of Use (the "Terms") is a binding agreement between you, either an individual subscriber, customer, member, or user of at least 18 years of age or a single entity ("you", or collectively "Users") and owners of this application, service site and networks that allow for the distribution and reception of video, audio, chat and other content (the "Service").

By accessing the Service and/or by clicking "I agree", you agree to be bound by these Terms of Use. You hereby represent and warrant to us that you are at least eighteen (18) years of age or and otherwise capable of entering into and performing legal agreements, and that you agree to be bound by the following Terms and Conditions. If you use the Service on behalf of a business, you hereby represent to us that you have the authority to bind that business and your acceptance of these Terms of Use will be treated as acceptance by that business. In that event, "you" and "your" will refer to that business in these Terms of Use.

Prohibited Conduct

The Services may include interactive areas or services (" Interactive Areas ") in which you or other users may create, post or store content, messages, materials, data, information, text, music, sound, photos, video, graphics, applications, code or other items or materials on the Services ("User Content" and collectively with Broadcaster Content, " Content "). You are solely responsible for your use of such Interactive Areas and use them at your own risk. BY USING THE SERVICE, INCLUDING THE INTERACTIVE AREAS, YOU AGREE NOT TO violate any law, contract, intellectual property or other third-party right or commit a tort, and that you are solely responsible for your conduct while on the Service. You agree that you will abide by these Terms of Service and will not:

use the Service for any purposes other than to disseminate or receive original or appropriately licensed content and/or to access the Service as such services are offered by us;

rent, lease, loan, sell, resell, sublicense, distribute or otherwise transfer the licenses granted herein;

post, upload, or distribute any defamatory, libelous, or inaccurate Content;

impersonate any person or entity, falsely claim an affiliation with any person or entity, or access the Service accounts of others without permission, forge another persons digital signature, misrepresent the source, identity, or content of information transmitted via the Service, or perform any other similar fraudulent activity;

delete the copyright or other proprietary rights notices on the Service or Content;

make unsolicited offers, advertisements, proposals, or send junk mail or spam to other Users of the Service, including, without limitation, unsolicited advertising, promotional materials, or other solicitation material, bulk mailing of commercial advertising, chain mail, informational announcements, charity requests, petitions for signatures, or any of the foregoing related to promotional giveaways (such as raffles and contests), and other similar activities;

harvest or collect the email addresses or other contact information of other users from the Service for the purpose of sending spam or other commercial messages;

use the Service for any illegal purpose, or in violation of any local, state, national, or international law, including, without limitation, laws governing intellectual property and other proprietary rights, and data protection and privacy;

defame, harass, abuse, threaten or defraud Users of the Service, or collect, or attempt to collect, personal information about Users or third parties without their consent;

remove, circumvent, disable, damage or otherwise interfere with security-related features of the Service or Content, features that prevent or restrict use or copying of any content accessible through the Service, or features that enforce limitations on the use of the Service or Content;

reverse engineer, decompile, disassemble or otherwise attempt to discover the source code of the Service or any part thereof, except and only to the extent that such activity is expressly permitted by applicable law notwithstanding this limitation;

modify, adapt, translate or create derivative works based upon the Service or any part thereof, except and only to the extent that such activity is expressly permitted by applicable law notwithstanding this limitation;

intentionally interfere with or damage operation of the Service or any user enjoyment of them, by any means, including uploading or otherwise disseminating viruses, adware, spyware, worms, or other malicious code;

relay email from a third party mail servers without the permission of that third party;

use any robot, spider, scraper, crawler or other automated means to access the Service for any purpose or bypass any measures we may use to prevent or restrict access to the Service;

manipulate identifiers in order to disguise the origin of any Content transmitted through the Service;

interfere with or disrupt the Service or servers or networks connected to the Service, or disobey any requirements, procedures, policies or regulations of networks connected to the Service;use the Service in any manner that could interfere with, disrupt, negatively affect or inhibit other users from fully enjoying the Service, or that could damage, disable, overburden or impair the functioning of the Service in any manner;

use or attempt to use another user account without authorization from such user and us;

attempt to circumvent any content filtering techniques we employ, or attempt to access any service or area of the Service that you are not authorized to access; or

attempt to indicate in any manner that you have a relationship with us or that we have endorsed you or any products or services for any purpose.

Further, BY USING THE SERVICE, INCLUDING THE INTERACTIVE AREAS YOU AGREE NOT TO post, upload to, transmit, distribute, store, create or otherwise publish through the Service any of the following:

Content that would constitute, encourage or provide instructions for a criminal offense, violate the rights of any party, or that would otherwise create liability or violate any local, state, national or international law or regulation;

Content that may infringe any patent, trademark, trade secret, copyright or other intellectual or proprietary right of any party. By posting any Content, you represent and warrant that you have the lawful right to distribute and reproduce such Content;

Content that is unlawful, libelous, defamatory, obscene, pornographic, indecent, lewd, suggestive, harassing, threatening, invasive of privacy or publicity rights, abusive, inflammatory, fraudulent or otherwise objectionable;

Content that impersonates any person or entity or otherwise misrepresents your affiliation with a person or entity;

private information of any third party, including, without limitation, addresses, phone numbers, email addresses, Social Security numbers and credit card numbers;

viruses, corrupted data or other harmful, disruptive or destructive files; and

Content that, in the sole judgment of Service moderators, is objectionable or which restricts or inhibits any other person from using or enjoying the Interactive Areas or the Service, or which may expose us or our users to any harm or liability of any type.

Service takes no responsibility and assumes no liability for any Content posted, stored or uploaded by you or any third party, or for any loss or damage thereto, nor is liable for any mistakes, defamation, slander, libel, omissions, falsehoods, obscenity, pornography or profanity you may encounter. Your use of the Service is at your own risk. Enforcement of the user content or conduct rules set forth in these Terms of Service is solely at Service discretion, and failure to enforce such rules in some instances does not constitute a waiver of our right to enforce such rules in other instances. In addition, these rules do not create any private right of action on the part of any third party or any reasonable expectation that the Service will not contain any content that is prohibited by such rules. As a provider of interactive services, Service is not liable for any statements, representations or Content provided by our users in any public forum, personal home page or other Interactive Area. Service does not endorse any Content or any opinion, recommendation or advice expressed therein, and Service expressly disclaims any and all liability in connection with Content. Although Service has no obligation to screen, edit or monitor any of the Content posted in any Interactive Area, Service reserves the right, and has absolute discretion, to remove, screen or edit any Content posted or stored on the Service at any time and for any reason without notice, and you are solely responsible for creating backup copies of and replacing any Content you post or store on the Service at your sole cost and expense. Any use of the Interactive Areas or other portions of the Service in violation of the foregoing violates these Terms and may result in, among other things, termination or suspension of your rights to use the Interactive Areas and/or the Service.
',
				'crossdomain_xml' =>'<cross-domain-policy>
<allow-access-from domain="*"/>
<site-control permitted-cross-domain-policies="master-only"/>
</cross-domain-policy>'
			);

		}


		static function adminOptions()
		{

			$options = self::getAdminOptions();


			if (isset($_POST)) if (!empty($_POST))
			{

				$nonce = $_REQUEST['_wpnonce'];
				if ( ! wp_verify_nonce( $nonce, 'vwsec' ) )
				{
					echo 'Invalid nonce!';
					exit;
				}

				foreach ($options as $key => $value)
					if (isset($_POST[$key])) $options[$key] = sanitize_textarea_field( $_POST[$key] );

					//config parsing
					
					if (isset($_POST['appSetupConfig']))
						$options['appSetup'] = parse_ini_string(sanitize_textarea_field($_POST['appSetupConfig']), true);

					if (isset($_POST['gendersList']))
					{
						$genders = explode(',', $options['gendersList']);
						foreach ($genders as $key=>$gender) $genders[$key] = trim($gender);
						$options['genders'] = $genders;
					}

				update_option('VWvideoChatOptions', $options);
			}



			$optionsDefault = self::adminOptionsDefault();


			$active_tab = isset( $_GET[ 'tab' ] ) ? sanitize_text_field( $_GET[ 'tab' ] )  : 'support';

?>
<div class="wrap">
<div id="icon-options-general" class="icon32"><br></div>
<h2>VideoWhisper Webcam 2 Way VideoChat Settings</h2>
</div>

<h2 class="nav-tab-wrapper">

	<a href="admin.php?page=videocalls&tab=support" class="nav-tab <?php echo $active_tab=='support'?'nav-tab-active':'';?>">Support</a>
	
    <a href="admin.php?page=videocalls&tab=setup" class="nav-tab <?php echo $active_tab=='setup'?'nav-tab-active':'';?>">Setup</a>	
	
	 <a href="admin.php?page=videocalls&tab=server" class="nav-tab <?php echo $active_tab=='server'?'nav-tab-active':'';?>">Server</a>
	 <a href="admin.php?page=videocalls&tab=app" class="nav-tab <?php echo $active_tab=='app'?'nav-tab-active':'';?>">HTML5 Videochat</a>

        
    <a href="admin.php?page=videocalls&tab=pages" class="nav-tab <?php echo $active_tab=='pages'?'nav-tab-active':'';?>">Pages</a>
    
	
	<a href="admin.php?page=videocalls&tab=calls" class="nav-tab <?php echo $active_tab=='calls'?'nav-tab-active':'';?>">Call Setup & Access</a>
    <a href="admin.php?page=videocalls&tab=random" class="nav-tab <?php echo $active_tab=='random'?'nav-tab-active':'';?>">Random Videochat</a>

	<a href="admin.php?page=videocalls&tab=integration" class="nav-tab <?php echo $active_tab=='integration'?'nav-tab-active':'';?>">Integration</a>
     
	<a href="admin.php?page=videocalls&tab=import" class="nav-tab <?php echo $active_tab=='import'?'nav-tab-active':'';?>">Import Settings</a>

</h2>

<form method="post" action="<?php echo wp_nonce_url($_SERVER["REQUEST_URI"], 'vwsec'); ?>">
<?php
	

	
			switch ($active_tab)
			{
				case 'random':
					?>
					<h3>Random Videochat</h3>
					Random videochat is displayed with shortcode [videowhisper_videochat_random]. Matchmaking by filters (gender, country) is possible. 

					<h4>Matchmaking Filters</h4>
<select name="matchmaking" id="matchmaking">
  <option value="0" <?php echo !$options['matchmaking']?"selected":""?>>Disabled</option>
  <option value="1" <?php echo $options['matchmaking']?"selected":""?>>Enabled</option>
</select>
<br>Enable matchmaking filters: gender, country.

<h4>Genders</h4>
<input name="gendersList" type="text" id="gendersList" size="100" maxlength="256" value="<?php echo esc_attr($options['gendersList'])?>"/>
<br>Comma separated genders list. In example: <?php echo esc_html($optionsDefault['gendersList'])?>
<br>Serialized:
					<?php
					echo esc_html(serialize($options['genders']));
	?>
	<h4>GeoIP</h4>
			GeoIP detected country (if available on web hosting): 
	<?php
			echo self::detectCountry();
				break;
				
			case 'server':
?>
<h3>Server Configuration</h3>
Configure hosting settings (web and live streaming server).
<BR>This solution requires specific HTML5 live streaming server services. Make sure your hosting environment meets all <a href="https://videowhisper.com/?p=Requirements" target="_blank">requirements</a>, including Wowza SE configured for HTML5 WebRTC streaming over SSL or P2P VideoWhisper WebRTC (recommended for private calls). For a quick and cost effective setup, start with VideoWhisper plans instead of setting up own live streaming servers.
<BR>Recommended Turnkey Hosting: <a href="https://webrtchost.com/hosting-plans/" target="_vwhost">Complete Hosting with HTML5 Live Streaming</a> - All hosting requirements including HTML5 live streaming server services, SSL for site and streaming, specific server tools and configurations for advanced features. Settings can be quickly imported:
<BR><a href="admin.php?page=videocalls&tab=import" class="button">Import Settings</a>

<h3>WebRTC</h3>
WebRTC can be used to broadcast and playback live video streaming in HTML5 browsers with low latency. Latency can be under 1s depending on network conditions, compared to HLS from RTMP which can be have up to 10s latency because of delivery tehnology. Recommended for interactive scenarios and 2 way calls / conferencing. 

<h4>WebRTC Streaming Server</h4>
<select name="serverType" id="serverType">
<option value="wowza" <?php echo ( $options['serverType'] == 'wowza' ) ? 'selected' : ''; ?>>Wowza Streaming Engine</option>
<option value="videowhisper" <?php echo ( $options['serverType'] == 'videowhisper' ) ? 'selected' : ''; ?>>VideoWhisper WebRTC</option>
</select>
<br/>VideoWhisper WebRTC currently provides WebRTC signaling for P2P streaming and supports TURN for relaying. It's recommended for private rooms with 2 users as provided by this implementation.

<h3>VideoWhisper Server</h3>
The new <a href="https://github.com/videowhisper/videowhisper-webrtc">VideoWhisper WebRTC server</a> is a NodeJS based server that provides WebRTC signaling and can be used in combination with TURN/STUN servers. It's a great option for low latency P2P live streaming between 2 or few users but not recommended for 1 to many scenarios. In P2P streaming, broadcaster streams to each viewer which is optimal for latency, but requires a high speed connection to handle all this streaming. 
It's a new server that is still in development and is not yet recommended for production. 

<p>Get a <b>Free Developers</b> or paid account from <a href="https://webrtchost.com/hosting-plans/#WebRTC-Only">WebRTC Host: P2P</a>.</p>

<h4>Address / VideoWhisper WebRTC</h4>
<input name="vwsSocket" type="text" id="vwsSocket" size="100" maxlength="256" value="<?php echo esc_attr( $options['vwsSocket'] ); ?>"/>
<BR>VideoWhisper NodeJS server address. Formatted as wss://[socket-server]:[port] . Example: wss://videowhisper.yourwebsite.com:3000

<h4>Token / VideoWhisper WebRTC </h4>
<input name="vwsToken" type="text" id="vwsToken" size="100" maxlength="256" value="<?php echo esc_attr( $options['vwsToken'] ); ?>"/>
<BR>Token (account token) for VideoWhisper WebRTC server. 

<BR><?php echo self::requirementRender( 'vwsSocket' ); ?>

<h3>Wowza Streaming Engine</h3>

<h4>Wowza SE WebRTC WebSocket URL</h4>
<input name="wsURLWebRTC" type="text" id="wsURLWebRTC" size="100" maxlength="256" value="<?php echo esc_attr($options['wsURLWebRTC'])?>"/>
<BR>Wowza WebRTC WebSocket URL (wss with SSL certificate). Formatted as wss://[wowza-server-with-ssl]:[port]/webrtc-session.json .
<BR><?php echo self::requirementRender('wsURLWebRTC_configure') ?>
<BR>Requires a Wowza SE relay WebRTC streaming server with a SSL certificate. Such setup is available with the <a href="https://webrtchost.com/hosting-plans/#Complete-Hosting" target="_vwhost">Turnkey Complete Hosting plans</a>.

<?php
			submit_button();

			$wsURLWebRTC_configure = self::requirementDisabled('wsURLWebRTC_configure');
			if ($wsURLWebRTC_configure) $options['webrtc'] = 0;
?>

<h4>Wowza SE WebRTC Application Name</h4>
<input name="applicationWebRTC" type="text" id="applicationWebRTC" size="100" maxlength="256" value="<?php echo esc_attr($options['applicationWebRTC'])?>"/>
<BR>Wowza Application Name (configured or WebRTC usage). Ex: videowhisper-webrtc


<h4>Video Codec</h4>
<select name="webrtcVideoCodec" id="webrtcVideoCodec">
  <option value="42e01f" <?php echo $options['webrtcVideoCodec']=='42e01f'?"selected":""?>>H.264 Profile 42e01f</option>
  <option value="VP8" <?php echo $options['webrtcVideoCodec']=='VP8'?"selected":""?>>VP8</option>
 <!--

     <option value="VP8" <?php echo $options['webrtcVideoCodec']=='VP8'?"selected":""?>>VP8</option>
  <option value="VP9" <?php echo $options['webrtcVideoCodec']=='VP9'?"selected":""?>>VP9</option>

  <option value="420010" <?php echo $options['webrtcVideoCodec']=='420010'?"selected":""?>>H.264 420010</option>
  <option value="420029" <?php echo $options['webrtcVideoCodec']=='420029'?"selected":""?>>H.264 420029</option>

  -->
</select>
<br>Safari supports VP8 from version 12.1 for iOS & PC and H264 in older versions. Because Safari uses hardware encoding for H264, profile may not be suitable for playback without transcoding, depending on device: VP8 is recommended when broadcasting with latest Safari. 

<h4>Maximum Video Bitrate</h4>
<?php
			$sessionsVars = self::varLoad($options['uploadsPath']. '/sessionsApp');
			if (is_array($sessionsVars))
			{
				if (array_key_exists( 'limitClientRateIn', $sessionsVars) )
				{
					$limitClientRateIn = intval($sessionsVars['limitClientRateIn']) * 8 / 1000;

					echo 'Detected hosting client upload limit: ' . ($limitClientRateIn?$limitClientRateIn.'kbps': 'unlimited') . '<br>';

					$maxVideoBitrate = $limitClientRateIn - 100;
					if ($options['webrtcAudioBitrate']>90) $maxVideoBitrate = $limitClientRateIn - $options['webrtcAudioBitrate'] - 10;

					if ($limitClientRateIn) if ($options['webrtcVideoBitrate'] > $maxVideoBitrate)
						{
							echo '<b>Warning: Adjust bitrate to prevent disconnect / failure.<br>Video bitrate should be 100kbps lower than total upload so it fits with audio and data added. Save to apply!</b><br>';
							$options['webrtcVideoBitrate'] =  $maxVideoBitrate;
						}
				}
			}
?>

<input name="webrtcVideoBitrate" type="text" id="webrtcVideoBitrate" size="10" maxlength="16" value="<?php echo esc_attr( $options['webrtcVideoBitrate'] )?>"/> kbps
<BR>Ex: 800. Max 400 for TCP. HTML5 Videochat app will adjust default bitrate and options depending on selected resolution. Very high bitrate setting may be discarded by browsers or result in failures or interruptions due to user connection limits. Application may have lower restrictions. Default: <?php echo esc_attr( $optionsDefault['webrtcVideoBitrate'] )?>
<br>If streaming hosting upload is limited, video bitrate should be 100kbps lower than total upload so it fits with audio and data added. Trying to broadcast higher will result in disconnect/failure.

<h4>Audio Codec</h4>
<select name="webrtcAudioCodec" id="webrtcAudioCodec">
  <option value="opus" <?php echo $options['webrtcAudioCodec']=='opus'?"selected":""?>>Opus</option>
  <option value="vorbis" <?php echo $options['webrtcAudioCodec']=='vorbis'?"selected":""?>>Vorbis</option>
</select>
<BR>Recommended: Opus.

<h4>Maximum Audio Bitrate</h4>
<input name="webrtcAudioBitrate" type="text" id="webrtcAudioBitrate" size="10" maxlength="16" value="<?php echo esc_attr( $options['webrtcAudioBitrate'] )?>"/> kbps
<br>Ex: 64 or 72, 96 Default: <?php echo esc_attr( $optionsDefault['webrtcAudioBitrate'] )?>
<br>If client upload bitrate is limited, using higher audio bitrate also involves reducing maximum video bitrate.


<h4>Web Key</h4>
<input name="webKey" type="text" id="webKey" size="32" maxlength="64" value="<?php echo esc_attr( $options['webKey'] )?>"/>
<BR>A web key can be used for <a href="http://www.videochat-scripts.com/videowhisper-rtmp-web-authetication-check/">VideoWhisper RTMP Web Session Check</a>.
<?php
				$root_ajax = admin_url( 'admin-ajax.php?action=v2wvc&task=');
				
				echo "<BR>webLogin: " . esc_url($root_ajax) ."rtmp_login&s=";
				echo "<BR>webLogout: " . esc_url($root_ajax) . "rtmp_logout&s=";
?>

<h4>Debug Mode / Dev Mode</h4>
<select name="debugMode" id="debugMode">
  <option value="1" <?php echo $options['debugMode'] == '1' ? 'selected' : ''; ?>>On</option>
  <option value="0" <?php echo $options['debugMode'] == '0' ? 'selected' : ''; ?>>Off</option>
</select>
<BR>Outputs various debugging info, including query parameters when there are no listings to show, room settings when going live, various information in text chat, matchmaking criteria.


<?php
				break;


		case 'pages';

?>
<h3><?php _e('Setup Pages','live-streaming'); ?></h3>

<?php
			if ($_POST['submit'] ?? false)
			{
				echo '<p>Saving pages setup.</p>';
				self::setupPages();
			}

			submit_button( __('Update Pages','ppv-live-webcams') );
?>
Use this to setup pages on your site. Pages with main feature shortcodes are required to access main functionality. After setting up these pages you should add the feature pages to site menus for users to access.
A sample VideoWhisper menu will also be added when adding pages: can be configured to show in a menu section depending on theme.
<br>You can manage these anytime from backend: <a href="edit.php?post_type=page">pages</a> and <a href="nav-menus.php">menus</a>.
<BR><?php echo self::requirementRender('setup_pages') ?>

<h4>Setup Pages</h4>
<select name="disableSetupPages" id="disableSetupPages">
  <option value="0" <?php echo $options['disableSetupPages']?"":"selected"?>>Yes</option>
  <option value="1" <?php echo $options['disableSetupPages']?"selected":""?>>No</option>
</select>
<br>Create pages for main functionality. Also creates a menu with these pages (VideoWhisper) that can be added to themes.
<br>After login performers are redirected to the dashboard page and clients to webcams page.

<h3>Feature Pages</h3>
These pages are required for specific turnkey site solution functionality. If you edit pages with shortcodes to add extra content, make sure shortcodes remain present.
<?php

			$pages = self::setupPagesList();
			$content = self::setupPagesContent(); 

			$args = array(
				'sort_order' => 'asc',
				'sort_column' => 'post_title',
				'hierarchical' => 1,
				'post_type' => 'page',
			);
			$sPages = get_pages($args);

			foreach ($pages as $shortcode => $title)
			{
				$pid = sanitize_text_field( $options['p_' . $shortcode] ?? 0 );
				
				if ( $pid != '')
				{
					
					echo '<h4>' . esc_html( $title ) . '</h4>';
					echo '<select name="p_' . esc_attr( $shortcode ) . '" id="p_' . esc_attr( $shortcode ) . '">';
					echo '<option value="0">Undefined: Reset</option>';
					foreach ($sPages as $sPage) echo '<option value="' . intval($sPage->ID) . '" '. (($pid == $sPage->ID)?"selected":"") .'>' . esc_html( $sPage->ID ) . '. ' . esc_html( $sPage->post_title ) . ' - '.  esc_html( $sPage->post_status ) . '</option>' . "\r\n";
					echo '</select><br>';
					if ($pid) echo '<a href="' . get_permalink($pid) . '">view</a> | ';
					if ($pid) echo '<a href="post.php?post=' . esc_attr( $pid ) . '&action=edit">edit</a> | ';
					echo 'Default content: ' . ( array_key_exists($shortcode,$content) ? esc_html( $content[$shortcode] ) : esc_html( "[$shortcode]" )) . '';

				} else 
				{
					   echo '<h4>' . esc_html( $title ) . '</h4>';
					   echo esc_html( $shortcode ) . ' - not found. Default content: ' . ( array_key_exists($shortcode,$content) ? esc_html( $content[$shortcode] ) : esc_html( "[$shortcode]" )) . '';	
				}
			
			}


			break;

case 'import':
?>
<h3>Import Options</h3>
Quickly Import/Export plugin settings and options.
<?php


$importURL = sanitize_text_field( $_POST['importURL'] ?? '' );
if ($importURL) 
{
	echo '<br>Importing settings from URL: ' . esc_html( $importURL );
	$optionsImport = parse_ini_string( file_get_contents( $importURL ), false );

	//display parse error if any
	if ( $optionsImport === false )
	{
		echo '<br>Parse Error: ' . esc_html( error_get_last()['message'] );
	}

	if ($optionsImport ) foreach ( $optionsImport as $key => $value )
	{
		echo '<br>' . esc_html( " - $key = $value" );
		$options[ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
	}
	update_option( 'VWvideoChatOptions', $options );
}

			if ($importConfig = sanitize_textarea_field( $_POST['importConfig'] ?? '' ))
			{
				echo '<br>Importing: ' ;
				$optionsImport = parse_ini_string(stripslashes($importConfig), false);
				//var_dump($optionsImport);

				foreach ($optionsImport as $key => $value)
				{
					echo "<br>" . esc_html( " - $key = $value" );
					$options[sanitize_text_field( $key )] = sanitize_text_field( $value );
				}
				update_option('VWvideoChatOptions', $options);
			}
?>
<h4>Settings Import URL</h4>
<input name="importURL" type="text" id="importURL" size="120" maxlength="256" value=""/>
<br/>If you have an account with VideoWhisper go to <a href="https://consult.videowhisper.com/my-accounts/">My Accounts</a> and use Configure Apps button for the account you want to use. Copy and paste the Settings Import URL here. 
<br/>If you don't have a streaming plan, yet, get one from <a href="https://webrtchost.com/hosting-plans/#Streaming-Only">WebRTC Host</a>.
<br/>If you change your plan, import settings again as this also includes streaming plan limitations to avoid streams from being rejected.
<?php 
submit_button( "Import");
?>

<h4>Import Plugin Settings</h4>
<textarea name="importConfig" id="importConfig" cols="120" rows="12"></textarea>
<br>Quick fill settings as option = "value".

<h4>Export Current Plugin Settings</h4>
<textarea readonly cols="120" rows="12">[Plugin Settings]<?php
			foreach ($options as $key => $value) echo "\n" . esc_html( "$key = " ) . '"'. esc_html( htmlentities(stripslashes( strval($value) )) ) . '"';
			?></textarea>

<h4>Export Default Plugin Settings</h4>
<textarea readonly cols="120" rows="10">[Plugin Settings]<?php
			foreach ($optionsDefault as $key => $value) echo "\n". esc_html( "$key = " ) . '"'. esc_html( htmlentities(stripslashes( strval($value) )) ) . '"';
			?></textarea>

<h5>Warning: Saving will set settings provided in Import Plugin Settings box.</h5>
<?php

			break;


		case 'setup':
?>
<h3><?php _e('Setup Overview','ppv-live-webcams'); ?></h3>


 1. Requirements: Before setting up, make sure you have necessary hosting requirements, for HTML5 live video streaming. This plugin has <a href="https://videowhisper.com/?p=Requirements" title="Live Streaming Requirements" target="_requirements">requirements</a> beyond regular WordPress hosting specifications and needs specific HTML5 live streaming services and video tools. Skip requirements review if you have <a href="https://webrtchost.com/hosting-plans/">a turnkey live streaming hosting plan</a> from VideoWhisper as it provides all features.
<br> 2. Existing active site? This plugin is designed to setup a turnkey live streaming site, changing major WP blog features. Set it up on a development environment as it can alter functionality of existing sites. To be able to revert changes, before setting up, make a recovery backup using hosting control panel or other backup tool/plugin. You can skip backups if this is a new site.
<br> 3. Setup: To setup this plugin start from <a href="admin.php?page=videocalls&tab=support">Backend Documentation</a> and then review requirements checkpoints list on this page.
<br>If not sure about how to proceed or need clarifications, <a href="https://videowhisper.com/tickets_submit.php?topic=Install+VideoCalls+Plugin">contact plugin developers</a>.

<p><a class="button secondary" href="admin.php?page=videocalls&tab=support">Support</a></p>

<h3><?php _e('Setup Checkpoints','ppv-live-webcams'); ?></h3>

This section lists main requirements and checkpoints for setting up and using this solution.
<?php


			//handle item skips
			$unskip = sanitize_file_name( $_GET['unskip'] ?? false);
			if ($unskip) self::requirementUpdate($unskip, 0, 'skip');

			$skip = sanitize_file_name( $_GET['skip'] ?? false);
			if ($skip) self::requirementUpdate($skip, 1, 'skip');

			$check = sanitize_file_name( $_GET['check'] ?? false );
			if ($check) self::requirementUpdate($check, 0);

			$done = sanitize_file_name( $_GET['done'] ?? false);
			if ($done) self::requirementUpdate($done, 1);

			//accessed setup page: easy
			self::requirementMet('setup');

			//list requirements
			$requirements = self::requirementsGet();

			$rDone = 0;


			$htmlDone = '';
			$htmlSkip = '';
			$htmlPending = '';
			
			foreach ($requirements as $label => $requirement)
			{
				$html = self::requirementRender($label, 'overview', $requirement);

				$status = self::requirementStatus($requirement);
				$skip = self::requirementStatus($requirement, 'skip');


				if ($status) {$htmlDone .= $html; $rDone++;}
				elseif ($skip) $htmlSkip .= $html;
				else $htmlPending .= $html;
			}

			if ($htmlPending) echo '<h4>To Do:</h4>' . wp_kses_post($htmlPending);
			if ($htmlSkip) echo '<h4>Skipped:</h4>' . wp_kses_post($htmlSkip);
			if ($htmlDone) echo '<h4>Done ('. esc_html( $rDone ).'):</h4>' . wp_kses_post($htmlDone);
?>
* These requirements are updated with checks and checkpoints from certain pages, sections, scripts. Certain requirements may take longer to update (in example session control updates when there are live streams and streaming server calls the web server to notify). When plugin upgrades include more checks to assist in reviewing setup, these will initially show as required until checkpoint.
<?php
			break;
			
			
		case 'support':
			//! Support

			self::requirementMet('resources');

?>
<h3>Support Resources</h3>
Solution resources: documentation, tutorials, support. This plugin implements web based 2 way video calls based on <a href="https://paidvideochat.com/html5-videochat/">Videowhisper HTML5 Videochat</a> application. For a more advanced setup with pay per minute, providers/client registration, see <a href="https://paidvideochat.com">Turnkey PaidVideochat WP Site Platform</a>.
<p>  <a href="admin.php?page=videocalls&tab=setup" class="button primary">Setup Overview</a> | <a href="https://videowhisper.com/tickets_submit.php?topic=VideoCalls+Plugin" class="button primary" >Contact VideoWhisper</a></p>

<h3>Shortcodes</h3>
<UL>
<LI>[videowhisper_videochat_manage] Manage video call booths.</LI>
<LI>[videowhisper_videochat_random] Random videochat with next button.</LI>
<LI>[videowhisper_videochat_filters] Matchmaking filters for random videochat, automatically shown under random videocat if enabled.</LI>

</UL>
	
<h3>Hosting Requirements</h3>
<UL>
<LI><a href="https://videowhisper.com/?p=Requirements">Hosting Requirements</a> This advanced software requires web hosting and HTML5 live streaming hosting.</LI>
<LI><a href="https://webrtchost.com/hosting-plans/">Recommended Hosting</a> Turnkey, convenient and cost effective plans (compared to setting up own live streaming servers).</LI>
<LI><a href="https://videowhisper.com/?p=RTMP+Hosting">Estimate Hosting Needs</a> Evaluate hosting needs: volume and features.</LI>
</UL>

<a name="plugins"></a>

<h3>Feature Integration Plugins (Recommended)</h3>

<UL>
<LI><a href="https://wordpress.org/plugins/video-share-vod/">Video Share VOD</a> Add webcam videos, teaser video. Videos can be used to schedule stream while performer is offline, sell video on demand.</LI>
<li><a href="https://wordpress.org/plugins/rate-star-review/" title="Rate Star Review - AJAX Reviews for Content with Star Ratings">Rate Star Review – AJAX Reviews for Content with Star Ratings</a> plugin, integrated for webcam reviews and ratings.</li>
<LI><a href="https://wordpress.org/plugins/picture-gallery/">Picture Gallery</a> Add performer picture galleries, automated snapshots from shows.</LI>
<LI><a href="https://wordpress.org/plugins/paid-membership/">MicroPayments - Paid Content, Membership and Donations</a> Sell videos (per item) from frontend, sell membership subscriptions. Based on MyCred / TeraWallet WooCommerce tokens that can be purchased with real money gateways or earned on site.</LI>
<li><a href="https://wordpress.org/plugins/mycred/">myCRED</a> and/or <a href="https://wordpress.org/plugins/woo-wallet/">WooCommerce TeraWallet</a>, integrated for tips.  Configure as described in Tips settings tab.</li>
<LI><a href="https://wordpress.org/plugins/video-posts-webcam-recorder/">Webcam Video Recorder</a> Site users can record videos from webcam. Can also be used to setup reaction recording: record webcam while playing an Youtube video.</LI>
</UL>

<h3>Optimization Plugins (Recommended)</h3>
<UL>
<li><a href="https://wordpress.org/plugins/wp-super-cache/">WP Super Cache</a> configured ONLY for visitors, disabled for known users or requests with GET parameters, great for protecting against bot or crawlers eating up site resources)</li>
<li><a href="https://wordpress.org/plugins/wordfence/">WordFence</a> plugin with firewall. Configure to protect by limiting failed login attempts, bot attacks / flood request, scan for malware or vulnerabilities. In WordFence, enable reCAPTCHA from <a href="<?php echo get_admin_url(); ?>wp-admin/admin.php?page=WFLS#top#settings">WordFence Login Settings</a> after getting a <a href="https://www.google.com/recaptcha/admin/create">free reCaptcha v3 key</a>.</li>
<li>HTTPS redirection plugin like <a href="https://wordpress.org/plugins/really-simple-ssl/">Really Simple SSL</a>&nbsp;, if you have a SSL certificate and HTTPS configured (as on VideoWhisper plans). HTTPS is required to broadcast webcam, in latest browsers like Chrome. If you also use HTTP urls (not recommended), disable “Auto replace mixed content” option to avoid breaking external HTTP urls (like HLS).</li>
<li>A SMTP mailing plugin like <a href="https://wordpress.org/plugins/easy-wp-smtp/">Easy WP SMTP</a> and setup a real email account from your hosting backend (setup an email from CPanel) or external (Gmail or other provider), to send emails using SSL and all verifications. This should reduce incidents where users don’t find registration emails due to spam filter triggering. Also instruct users to check their spam folders if they don’t find registration emails. To prevent spam, an <a href="https://wordpress.org/plugins/search/user-verification/">user verification plugin</a> can be added.</li>
 	<li>For basic search engine indexing, make sure your site does not discourage search engine bots from Settings &gt; Reading   (discourage search bots box should not be checked).
Then install a plugin like <a href="https://wordpress.org/plugins/google-sitemap-generator/">Google XML Sitemaps</a> for search engines to quickly find main site pages.</li>
<li><a href="https://updraftplus.com/?afref=924">Updraft Plus</a> – Automated WordPress backup plugin. Free for local storage.
</UL>

<h3>Turnkey Features Plugins</h3>
<ul>
 	<li><a href="https://woocommerce.com/?aff=18336&amp;cid=2828082">WooCommerce</a> : <em>ecommerce</em> platform</li>
 	<li><a href="https://buddypress.org/">BuddyPress</a> : <em>community</em> (member profiles, activity streams, user groups, messaging)</li>
 	<li><a href="https://woocommerce.com/products/sensei/?aff=18336&amp;cid=2828082">Sensei LMS</a> : <em>learning</em> management system</li>
 	<li><a href="https://bbpress.org/">bbPress</a>: clean discussion <em>forums</em></li>
</ul>


<h3>Premium Plugins / Addons</h3>
<ul>
	<LI><a href="http://themeforest.net/popular_item/by_category?category=wordpress&ref=videowhisper">Premium Themes</a> Professional WordPress themes.</LI>
	<LI><a href="https://woocommerce.com/products/woocommerce-memberships/?aff=18336&amp;cid=2828082">WooCommerce Memberships</a> Setup paid membership as products. Leveraged with Subscriptions plugin allows membership subscriptions.</LI>

	<LI><a href="https://woocommerce.com/products/woocommerce-subscriptions/?aff=18336&amp;cid=2828082">WooCommerce Subscriptions</a> Setup subscription products, content. Leverages Membership plugin to setup membership subscriptions.</LI>

<li><a href="https://woocommerce.com/products/woocommerce-bookings/?aff=18336&amp;cid=2828082">WooCommerce Bookings</a> Setup booking products with calendar, <a href="https://woocommerce.com/products/bookings-availability/?aff=18336&amp;cid=2828082">availability</a>, <a href="https://woocommerce.com/products/woocommerce-deposits/?aff=18336&amp;cid=2828082">booking deposits</a>, confirmations for 1 on 1 or group bookings. Include performer room link.</li>

	<LI><a href="https://woocommerce.com/products/follow-up-emails/?aff=18336&amp;cid=2828082">WooCommerce Follow Up</a> Follow Up by emails and twitter automatically, drip campaigns.</LI>

		<LI><a href="https://woocommerce.com/products/product-vendors/?aff=18336&amp;cid=2828082">WooCommerce Product Vendors</a> Allow multiple vendors to sell via your site and in return take a commission on sales. Leverage with <a href="https://woocommerce.com/products/woocommerce-product-reviews-pro/?aff=18336&amp;cid=2828082">Product Reviews Pro</a>.</LI>

<li><a href="https://woocommerce.com/products/woocommerce-order-status-control/?aff=18336&amp;cid=2828082">Order Status Control</a> Control which Paid WooCommerce Orders are Automatically Completed so you don't have to manually Process payments. Order processing is required to get tokens allocated automatically when using TeraWallet and also to enable access for content purchased using the MicroPayments integration for selling content as WooCommerce products.</li>


	<LI><a href="https://updraftplus.com/?afref=924">Updraft Plus</a> Automated WordPress backup plugin. Free for local storage. For production sites external backups are recommended (premium).</LI>
</ul>

<h3>Contact and Feedback</h3>
<a href="https://videowhisper.com/tickets_submit.php?topic=VideoCalls+Plugin">Sumit a Ticket</a> with your questions, inquiries and VideoWhisper support staff will try to address these as soon as possible.
<br>Although the free license does not include any services (as installation and troubleshooting), VideoWhisper staff can clarify requirements, features, installation steps or suggest additional services like customisations, hosting you may need for your project.

<h3>Review and Discuss</h3>
You can publicly <a href="https://wordpress.org/support/view/plugin-reviews/webcam-2way-videochat">review this WP plugin</a> on the official WordPress site (after <a href="https://wordpress.org/support/register.php">registering</a>). You can describe how you use it and mention your site for visibility. You can also post on the <a href="https://wordpress.org/support/plugin/webcam-2way-videochat">WP support forums</a> - these are not monitored by support so use a <a href="https://videowhisper.com/tickets_submit.php?topic=VideoCalls+Plugin">ticket</a> if you want to contact VideoWhisper.
<BR>If you like this plugin and decide to order a commercial license or other services from <a href="https://videowhisper.com/">VideoWhisper</a>, use this coupon code for 5% discount: giveme5

<h3>News and Updates on Social Media</h3>
Follow updates using <a href="https://twitter.com/videowhisper"> Twitter </a>, <a href="https://www.facebook.com/VideoWhisper"> Facebook </a>.


				<?php
			break;

	case 'app':

			$options['appSetupConfig'] = htmlentities(stripslashes($options['appSetupConfig']));
			$options['appCSS'] = htmlentities(stripslashes($options['appCSS']));

?>
<h3>Apps</h3>
This section configures HTML5 Videochat app and external access (by external apps) using same API. Required when building external apps to work with solution.
<br>For live streaming features, HTML5 Videochat app requires Wowza SE as relay configured for secure WebRTC live streaming: <a href="admin.php?page=videocalls&tab=server">Configure HTML5 WebRTC</A>.


<h4>App Configuration</h4>
<textarea name="appSetupConfig" id="appSetupConfig" cols="120" rows="12"><?php echo esc_textarea( $options['appSetupConfig'] ) ?></textarea>
<BR>Application setup parameters are delivered to app when connecting to server. Config section refers to application parameters. Room section refers to default room options (configurable from app at runtime). User section refers to default room options configurable from app at runtime and setup on access.

Default:<br><textarea readonly cols="120" rows="6"><?php echo esc_textarea( $optionsDefault['appSetupConfig'] ) ?></textarea>

<BR>Parsed configuration (should be an array or arrays):<BR>
<?php

			var_dump($options['appSetup']);
?>
<BR>Serialized:<BR>
<?php

			echo esc_html(serialize($options['appSetup']));
?>

<h4>Site Menu in App</h4>
<select name="appSiteMenu" id="appSiteMenu">
	<option value="0" <?php echo (!$options['appSiteMenu']?"selected":"") ?>>None</option>
<?php
			$menus = get_terms( 'nav_menu', array( 'hide_empty' => true ) );

			foreach ($menus as $menu) echo '<option value="' . $menu->term_id . '" '. ($options['appSiteMenu'] == ($menu->term_id) || ($options['appSiteMenu'] == -1 && $menu->name == 'VideoWhisper' ) ?"selected":"") .'>' . esc_html( $menu->name ) . '</option>' . "\r\n";

?>
</select>
<br>A site menu is useful for chat users to access site features, especially when running app in full page.


<h4>App CSS</h4>
<textarea name="appCSS" id="appCSS" cols="100" rows="6"><?php echo esc_textarea( $options['appCSS'] ) ?></textarea>
<br>
CSS code to adjust or fix application styling if altered by site theme. Multiple interface elements are implemented by <a href="https://fomantic-ui.com">Fomantic UI</a> (a fork of <a href="https://semantic-ui.com">Semantic UI</a>). Editing interface and layout usually involves advanced CSS skills. For reference also see <a href="https://paidvideochat.com/html5-videochat/css/">Layout CSS</a>. Default:<br><textarea readonly cols="100" rows="3"><?php echo esc_textarea( $optionsDefault['appCSS'] ) ?></textarea>


<h4>Whitelabel Mode: Remove Author Attribution Notices (Explicit Permission Required)</h4>
<select name="whitelabel" id="whitelabel">
	<option value="0" <?php echo (!$options['whitelabel']?"selected":"") ?>>Disabled</option>
	<option value="1" <?php echo ($options['whitelabel']=='1'?"selected":"") ?>>Enabled</option>
</select>
<br>Embedded HTML5 Videochat application is branded with subtle attribution references to authors, similar to most software solutions in the world. Removing the default author attributions can be permitted by authors with a <a href="https://videowhisper.com/tickets_submit.php?topic=WhiteLabel+HTML5+Videochat">special licensing agreement</a>, in addition to full mode. Whitelabelling is an extra option that can be added to full mode.
<br>Warning: Application will not start if whitelabel mode is enabled and explicit licensing agreement from authors is not available, to remove attribution notices.

<h4>More Documentation</h4>
 - <a href="https://videochat-scripts.com/troubleshoot-html5-and-webrtc-streaming-in-videowhisper/">Troubleshoot HTML5 Streaming</a>: Tutorials, suggestions for troubleshooting streaming reliability and quality
<br> - <a href="https://paidvideochat.com/html5-videochat/">HTML5 Videochat Page</a>: Application features and product page in PaidVideochat
<br> - <a href="https://paidvideochat.com/html5-videochat/css/">HTML5 Videochat Layout CSS</a>
<br> - <a href="https://fomantic-ui.com">Fomantic UI</a>: Review interface element names for applying CSS
<br> - <a href="https://react.semantic-ui.com">Semantic UI React</a>: Review interface element names for applying CSS


<?php
			break;

						
			case 'calls':
				//! room setup & access
?>
<h3><?php _e('Room Setup & Access','vw2wvc'); ?></h3>
<h4><?php _e('Who can create rooms','vw2wvc'); ?></h4>
<select name="canBroadcast" id="canBroadcast">
  <option value="members" <?php echo $options['canBroadcast']=='members'?"selected":""?>><?php _e('All Members','vw2wvc'); ?></option>
  <option value="list" <?php echo $options['canBroadcast']=='list'?"selected":""?>><?php _e('Members in List','vw2wvc'); ?> *</option>
</select>
<h4>* <?php _e('Members in List: allowed to broadcast video (comma separated user names, roles, emails, IDs)','vw2wvc'); ?></h4>
<textarea name="broadcastList" cols="64" rows="3" id="broadcastList"><?php echo esc_textarea( $options['broadcastList'] ) ?>
</textarea>

<h4>Auto Create a Room for Each User</h4>
<select name="autoRoom" id="autoRoom">
  <option value="0" <?php echo $options['autoRoom']?"":"selected"?>><?php _e('Disabled','vw2wvc'); ?></option>
  <option value="register" <?php echo $options['autoRoom']=='register'?"selected":""?>><?php _e('On Registration','vw2wvc'); ?></option>
  <option value="login" <?php echo $options['autoRoom']=='login'?"selected":""?>><?php _e('On Login','vw2wvc'); ?></option>
  <option value="manage" <?php echo $options['autoRoom']=='manage'?"selected":""?>><?php _e('On Management Page','vw2wvc'); ?></option>
  <option value="always" <?php echo $options['autoRoom']=='always'?"selected":""?>><?php _e('Always','vw2wvc'); ?></option>
</select>
<br>Automatically creates a room for each user with same name as user. Only creates rooms for users with this ability configured above (even on registration).
<br>Recommended: Disabled or on management page to avoid creating many rooms for users that don't need this functionality.

<h4>Maximum Rooms</h4>
<input name="maxRooms" type="text" id="maxRooms" size="3" maxlength="6" value="<?php echo esc_attr( $options['maxRooms'] )?>"/>
<br>Maximum number of videochat rooms each user can create. Set 0 for unlimited.

<h3><?php _e('Participants','vw2wvc'); ?></h3>
<h4><?php _e('Who can enter videochat','vw2wvc'); ?></h4>
<select name="canWatch" id="canWatch">
  <option value="all" <?php echo $options['canWatch']=='all'?"selected":""?>><?php _e('Anybody','vw2wvc'); ?></option>
  <option value="members" <?php echo $options['canWatch']=='members'?"selected":""?>><?php _e('All Members','vw2wvc'); ?></option>
  <option value="list" <?php echo $options['canWatch']=='list'?"selected":""?>><?php _e('Members in List','vw2wvc'); ?> *</option>
</select>
<h4>* <?php _e('Members in List: Allowed to participate (comma separated user names, roles, emails, IDs)','vw2wvc'); ?></h4>
<textarea name="watchList" cols="64" rows="3" id="watchList"><?php echo esc_textarea( $options['watchList'] ) ?>
</textarea>
<?php
				break;

			case 'integration':
				//! Integration
				$options['welcome'] = htmlentities(stripslashes($options['welcome']));
				$options['layoutCode'] = htmlentities(stripslashes($options['layoutCode']));
				$options['parameters'] = htmlentities(stripslashes($options['parameters']));
				$options['translationCode'] = htmlentities(stripslashes($options['translationCode']));

?>
<h3>Integration Settings</h3>


<h4>Interface Class(es)</h4>
<input name="interfaceClass" type="text" id="interfaceClass" size="30" maxlength="128" value="<?php echo esc_attr( $options['interfaceClass'] ); ?>"/>
<br>Extra class to apply to interface (using Semantic UI). Use inverted when theme uses a dark mode (a dark background with white text) or for contrast. Ex: inverted
<br>Some common Semantic UI classes: inverted (dark mode or contrast), basic (minimal formatting), secondary/tertiary (greys), red/orange/yellow/olive/green/teal/blue/violet/purple/pink/brown/grey/black (colors). Multiple classes can be combined, divided by spaces. Ex: "inverted", "basic pink", "secondary green", "secondary basic", "inverted orange" 
<br>HTML5 interface elements can customized by extra CSS. A lot of core styling is done with Semantic UI and custom CSS can be used to alter elements.
In example <a href="https://semantic-ui.com/elements/button.html">Semantic UI Button</a> font can be edited with code like “.ui.button{font-family: verdana}”, added to custom CSS of specific sections (like performer dashboard or listings) or with theme to apply on all pages.

<h4>Redirect after Login</h4>
<select name="loginRedirect" id="loginRedirect">
   <option value="<?php echo esc_attr( $pid = intval($options['p_videowhisper_videochat_manage']) ); ?>" <?php echo ($options['loginRedirect'] == $pid)?"selected":""?>>Videochat Setup (#<?php echo esc_html( $pid )?>)</option>
    <option value="<?php echo esc_attr( $pid=get_option( 'page_on_front' ) ); ?>" <?php echo ($options['loginRedirect'] == $pid)?"selected":""?>>Front Page (#<?php echo esc_html( $pid )?>)</option>
    <option value="<?php echo esc_attr( $pid=get_option( 'page_for_posts' ) ); ?>" <?php echo ($options['loginRedirect'] == $pid)?"selected":""?>>Posts Page (#<?php echo esc_html( $pid )?>)</option>
  <option value="0" <?php echo (!$options['loginRedirect'])?"selected":""?>>Default (#0)</option>
</select>
<br>Redirect users (except admins), after login, to a frontend section.

<h4>Username</h4>
<select name="userName" id="userName">
  <option value="display_name" <?php echo $options['userName']=='display_name'?"selected":""?>>Display Name</option>
  <option value="user_login" <?php echo $options['userName']=='user_login'?"selected":""?>>Login (Username)</option>
  <option value="user_nicename" <?php echo $options['userName']=='user_nicename'?"selected":""?>>Nicename</option>
</select>

<h4>Page</h4>
<p>Add videochat management page (Page ID <a href='post.php?post=<?php echo esc_attr( get_option("vw_2vc_page_room") ); ?>&action=edit'><?php echo esc_html( get_option("vw_2vc_page_room") ); ?></a>) with shortcode [videowhisper_videochat_manage]</p>
<select name="disablePage" id="disablePage">
  <option value="0" <?php echo $options['disablePage']=='0'?"selected":""?>>Yes</option>
  <option value="1" <?php echo $options['disablePage']=='1'?"selected":""?>>No</option>
</select>


<h4>Room Link Type</h4>
<select name="roomLink" id="roomLink">
  <option value="rewrite" <?php echo $options['roomLink']=='rewrite'?"selected":""?>>Rewrite (/videochat/$Room)</option>
  <option value="plugin" <?php echo $options['roomLink']=='plugin'?"selected":""?>>Plugin (wp-content/plugins...)</option>
</select>
<BR>If rewrite doesn't work, try updating permalinks (<a href="options-permalink.php">Save Changes on Permalinks page</a>).

<h4>Welcome Message</h4>
<textarea name="welcome" id="welcome" cols="64" rows="8"><?php echo esc_textarea( $options['welcome'] ) ?></textarea>
<br>Shows in chatbox when entering video chat.

<h4>Session Expiration</h4>
<input name="sessionExpire" type="text" id="sessionExpire" size="3" maxlength="6" value="<?php echo esc_attr( $options['sessionExpire'] )?>"/>s
<br>Session expiration time. After this time of absence online session is deleted (user no longer online). Should be bigger that interval between status updates.

<h4>Show VideoWhisper Powered by</h4>
<select name="videowhisper" id="videowhisper">
  <option value="0" <?php echo $options['videowhisper']?"":"selected"?>>No</option>
  <option value="1" <?php echo $options['videowhisper']?"selected":""?>>Yes</option>
</select>
<br>Display references to plugin developers on main sections.

<h4>Uploads Path</h4>
<p>Path where logs and snapshots will be uploaded. You can use a location outside plugin folder to avoid losing logs on updates and plugin uninstallation.</p>
<input name="uploadsPath" type="text" id="uploadsPath" size="80" maxlength="256" value="<?php echo esc_attr( $options['uploadsPath'] )?>"/>
<?php
				echo '<br>WordPress Path: ' . get_home_path();
				if (!strstr($options['uploadsPath'], get_home_path() )) echo '<br><b>Warning: Uploaded files may not be accessible by web.</b>';
				echo '<br>WordPress URL: ' . get_site_url();
?>
<br>wp_upload_dir()['basedir'] : <?php $wud= wp_upload_dir(); echo esc_html( $wud['basedir'] ) ?>
<br>$_SERVER['DOCUMENT_ROOT'] : <?php echo esc_html( $_SERVER['DOCUMENT_ROOT'] ) ?>

<?php
				break;

			case 'mobile':
				// !App
				$options['eula_txt'] = htmlentities(stripslashes($options['eula_txt']));
				$options['crossdomain_xml'] = htmlentities(stripslashes($options['crossdomain_xml']));

				$options['layoutCodeMobile'] = htmlentities(stripslashes($options['layoutCodeMobile']));
				$options['parametersMobile'] = htmlentities(stripslashes($options['parametersMobile']));

				$eula_url = site_url() . '/eula.txt';
				$crossdomain_url = site_url() . '/crossdomain.xml';
?>
<h3>Application Settings</h3>
<p>As latest HTML5 Videochat works on PC and mobiles, mobile apps are no longer required but can be implemented using similar technology. This section is for configuring settings related to remote apps (iOS/Android/Desktop) that can be used in combination with web based solution. Such apps can be <a href="https://videowhisper.com/?p=iPhone-iPad-Apps">custom made</a> for each site.</p>


<h4><?php _e('End User License Agreement','vw2wvc'); ?></h4>
<textarea name="eula_txt" id="eula_txt" cols="100" rows="8"><?php echo esc_textarea( $options['eula_txt'] ) ?></textarea>
<br>Users are required to accept this agreement before registering from app.
<br>After updating permalinks (<a href="options-permalink.php">Save Changes on Permalinks page</a>) this should become available as <a href="<?php echo $eula_url ?>"><?php echo $eula_url ?></a>. This works if file doesn't already exist. You can also create the file for faster serving.

<h4><?php _e('Cross Domain Policy','vw2wvc'); ?></h4>
<textarea name="crossdomain_xml" id="crossdomain_xml" cols="100" rows="4"><?php echo esc_textarea( $options['crossdomain_xml'] ) ?></textarea>
<br>This is required for applications to access interface and scripts on site.
<br>After updating permalinks (<a href="options-permalink.php">Save Changes on Permalinks page</a>) this should become available as <a href="<?php echo $crossdomain_url ?>"><?php echo $crossdomain_url ?></a>. This works if file doesn't already exist. You can also create the file for faster serving.
<?php
				break;



			}

		if (!in_array($active_tab, array( 'setup','support', 'reset', 'requirements', 'billing', 'tips', 'appearance')) ) submit_button();
		
		echo '</form><style>
.vwInfo
{
background-color: #fffffa;
padding: 8px;
margin: 8px;
border-radius: 4px;
display:block;
border: #999 1px solid;
box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}
</style>';

?>


</form>


	 <?php
		}

}