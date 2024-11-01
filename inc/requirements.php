<?php
namespace VideoWhisper\VideoCalls;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//ini_set('display_errors', 1);

trait Requirements {
	//define and check requirements

	static function requirementsDefinitions()
	{
		
		$adminSettings = 'admin.php?page=videocalls&tab=';

		$options = self::getOptions();

		//ordered
		return array(
			'setup' => array
			(
				'title' => 'Start Setup',
				'warning' => 'Plugin requires setup to configure and activate features.',
				'info' => 'Setting up features. Setup involves multiple steps for configuring and activating videochat features.',
				'fix' => 'Start from Setup Overview page: see backend documentation, setup tutorial',
				'url' => $adminSettings .'setup',
			),

			'setup_pages' => array
			(
				'title' => 'Setup Pages',
				'warning' => 'Pages to access functionality are not setup, yet.',
				'info' => 'Accessing main features: broadcast live channels, list channels.',
				'fix' => 'Setup feature pages and menu from Pages tab in settings.',
				'url' => $adminSettings . 'pages',
				'type' => 'option_configured',
				'option' => 'p_videowhisper_videochat_manage'
			),

			'vwsSocket' => array(
				'type'    => 'option_configured',
				'option'  => 'vwsSocket',
				'title'   => 'Configure P2P WebRTC using VideoWhisper WebRTC',
				'warning' => 'P2P was not configured, yet. ',
				'info'    => 'P2P in HTML5 Videochat, using VideoWhisper WebRTC',
				'fix'     => 'Get a FREE or paid account from <a href="https://webrtchost.com/hosting-plans/#WebRTC-Only" target="_blank">WebRTC Host: P2P</a> and configure VideoWhisper WebRTC Adress & Token in settings, or install your own <a href="https://github.com/videowhisper/videowhisper-webrtc">VideoWhisper WebRTC</a> and own STUN/TURN servers. Or skip if you use Wowza SE.',
				'url'     => $adminSettings . 'server',

			),
			'wsURLWebRTC_configure' => array(
				'type'    => 'option_configured',
				'option'  => 'wsURLWebRTC',
				'title'   => 'Configure WebRTC relay using Wowza SE',
				'warning' => 'A WebRTC relay address was not configured, yet.',
				'info'    => 'HTML5 Videochat',
				'fix'     => 'Deploy solution on <a href="https://webrtchost.com/hosting-plans/#Complete-Hosting" target="_blank">Complete Turnkey Streaming Hosting from WebRTChost.com</A> (recommended) for full capabilities or add only WebRTC relay streaming service using <A href="https://webrtchost.com/hosting-plans/#Streaming-Only" target="_blank">remote streaming service with RTMP/WebRTC/HLS/DASH</A>. For more details see <a href="https://videowhisper.com/?p=Requirements" target="_vwrequirements">requirements</a>. Skip if you use P2P with VideoWhisper WebRTC. Only 1 WebRTC server type is required.',
				'url'     => $adminSettings . 'server',
			),

		'resources' => array
			(
				'title' => 'Review Suggested Plugins',
				'warning' => 'You did not check suggested plugins and support resources, yet.',
				'info' => 'Extend solution functionality and optimize security, reliability.',
				'fix' => 'Review suggested plugins and support options on Support Resources section.',
				'url' => $adminSettings .'support#plugins',
			),
						
		'review' => array
			(
				'title' => 'Support Developers with a Review',
				'warning' => 'You did not review plugin, yet.',
				'info' => 'If you have nice ideas, suggestions for further development or just want to share your experience or tips for other website owners, leave a review on WP repository. Skip this if you do not want to support the developers.',
				'fix' => 'Leave a good review on WP repository to support plugin developers.',
				'url' => 'https://wordpress.org/plugins/webcam-2way-videochat/reviews/#new-post',
				'manual' => 1,
			),
	
			'brave' => array
			(
				'title' => 'Try HTML5 Brave Browser',
				'warning' => 'You did not try Brave, yet.',
				'info' => 'Test site in a privacy & ad blocking browser, add an extra income source, support developers. <a href="https://brave.com/pai553">Brave</a> is a special build of the popular Chrome browser, focused on privacy & speed & ad blocking and is already used by millions. You can easily test if certain site features are disabled by privacy features, cookie restrictions or common ad blocking rules. Brave users get airdrops and rewards from ads they are willing to watch and content creators (publishers) like site owners get tips and automated revenue from visitors. See the <a href="' . $adminSettings .'tips#brave">Tips options section</a> for receiving Brave tips on your own site.',
				'fix' => 'Try the Brave Browser. Skip if you do not want to try this option, yet.',
				'url' => 'https://brave.com/pai553',
				'manual' => 1,
			),

		);
	}
	static function requirements_plugins_loaded()
	{
		$remind = get_option( __CLASS__ . '_requirementsRemind');

		if ($remind < time() && ( !isset($_GET['tab']) || $_GET['tab'] != 'setup'))
		{
			add_action( 'admin_notices', array( __CLASS__, 'requirements_admin_notices'));
			add_action( 'wp_ajax_vws_notice', array( __CLASS__, 'vws_notice') );
		}
	}

	static function requirementsStatus()
	{
		return get_option(__CLASS__ . '_requirements');
	}

	static function requirementsGet()
	{
		$defs = self::requirementsDefinitions();
		$status = self::requirementsStatus();

		if (!$status) return $defs;
		if (!is_array($status)) return $defs;

		$merged = array();
		foreach ($defs as $key=>$value)
		{
			if (array_key_exists($key, $status))
			{
				$r_merged = array_merge((array) $value, (array) $status[$key]);
				$merged[$key] = $r_merged;
			} else $merged[$key] = $value;

			$merged[$key]['label'] = $key;

		}

		return $merged;
	}

	static function requirements_admin_notices()
	{

		$adminPage = 'admin.php?page=videocalls&tab=';
		
		$requirement = self::nextRequirement();

		if (!$requirement) return; //nothing to show

		$ajaxurl = get_admin_url() . 'admin-ajax.php';
?>
    <div id="vwNotice" class="notice notice-success is-dismissible">
        <h4>VideoCalls Plugin: What to do next?</h4>Turnkey Site Setup Wizard with Requirement Checkpoints and Suggestions

        <?php echo self::requirementRender($requirement['label'], 'overview', $requirement); ?>
        <a href="<?php echo esc_url( $adminPage ); ?>setup" >Setup Overview</a>
        | <a href="<?php echo esc_url( $adminPage ); ?>setup&skip=<?php echo esc_attr( $requirement['label'] )?>">Skip "<?php echo esc_html( $requirement['title'] )?>"</a>

        | <a href="<?php echo esc_url( $adminPage ); ?>support" >Support Resources</a>
        | <a target="_videowhisper" href="https://videowhisper.com/tickets_submit.php" >Contact Developers</a>
        | <a  href="#" onclick="noticeAction('remind', '<?php echo esc_attr( $requirement['label'] )?>')" >Remind me Tomorrow</a>

    </div>

<style>
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
</style>

    <script>

	static function noticeAction(task, label)
	    {
		    		var data = {
					'action': 'vws_notice',
					'task': task,
					'label': label,
					};

		  jQuery.post('<?php echo esc_url($ajaxurl)?>', data, function() {});

		  vwNotice = document.getElementById("vwNotice");
		  if (vwNotice) vwNotice.style.display = "none";
	    }
	</script>
    <?php
	}

	static function vws_notice() {
		//update_option( 'my_dismiss_notice', true );

		$task = sanitize_file_name( $_POST['task'] );

		switch ($task)
		{
		case 'remind':
			update_option( __CLASS__ . '_requirementsRemind',  time() + 86400);
			break;

		case 'skip':
			$label = sanitize_file_name( $_POST['label']);
			self::requirementUpdate($label, 1, 'skip');
			break;
		}

		ob_clean();

		exit;
	}


	//item handling

	static function requirementStatus($requirement, $meta = 'status')
	{
		if (!$requirement) return 0;
		if (!is_array($requirement)) return 0;
		if (!array_key_exists($meta, $requirement)) return 0;

		return $requirement[$meta];
	}


	static function requirementUpdate($label, $value, $meta = 'status')
	{
		//echo "requirementUpdate($label, $value, $meta = 'status')";

		$status = self::requirementsStatus();
		if (!is_array($status)) $status = array();

		if (array_key_exists($label, $status)) $metas = $status[$label];
		else $metas = array();

		if ($meta == 'status' && $metas['status'] != $value) $metas['updated'] = time(); //mark as update only if changed
		$metas[$meta] = $value;

		$status[$label] = $metas;
		update_option( __CLASS__ . '_requirements',  $status);
	}

	static function requirementMet($label)
	{
		if (!self::requirementStatus($label))
			self::requirementUpdate($label, 1);
	}


	static function nextRequirement()
	{

		$requirements = self::requirementsGet();

		foreach ($requirements as $label => $requirement)
			if (!self::requirementStatus($requirement) && !self::requirementStatus($requirement, 'skip'))
			{
				$requirement['label'] = $label;
				return $requirement;
			}

	}


	static function requirementDisabled($label)
	{

		if (self::requirementCheck($label)) return '';
		else return 'disabled';
	}

	static function requirementCheck($label, $force = false)
	{
		$requirements = self::requirementsGet();

		if (!array_key_exists($label, $requirements)) return 0; //not defined

		$requirement = $requirements[$label];

		//already checked and valid
		if (!$force || !in_array($requirement['type'], array('option_configured') )) //force only for possible checks
			if ($requirement['updated'] ?? false)
				if ($requirement['status']) return $requirement['status'];

				//check now if possible
				switch ($requirement['type'] ?? '' )
				{
				case 'option_configured':
					//not configured
					$options = self::getOptions();
					$optionsDefault = self::adminOptionsDefault();

					$requirementOption = $requirement['option'] ?? 0;

					$status = ( ( $options[$requirementOption] ?? '')  != ( $optionsDefault[$requirementOption] ?? '' ) );

					self::requirementUpdate($label, $status);
					
					return $status;

				case 'option_defined':

					$option = get_option($requirement['option']);
					if ($option) $status = 1; else $status = 0;
					self::requirementUpdate($label, $status);
					return $status;

					break;
				}

			//otherwise manual
			return 0;
	}

	static function requirementRender($label, $view = 'check', $requirement = null)
	{
		$isPresent = self::requirementCheck($label, $view == 'check'); //force when check

		switch ($view)
		{
		case 'check':

			if (!$requirement)
			{
				$requirements = self::requirementsDefinitions();
				$requirement = $requirements[$label];
			}


			$htmlCode = 'Requirement check: ' . $requirement['title'];

			if ($isPresent) $htmlCode .= ' = Checked.';
			else
			{

				$htmlCode .= '<div class="vwInfo"><b>' . $requirement['warning'] . '</b> Required for: ' . $requirement['info'] .
					'<br>Quick Fix: ' . $requirement['fix'] . '</div>';
			}
			break;

		case 'overview':

			$htmlButton = '<br><a class="button" href="' . $requirement['url'] . '">' .($isPresent?'Review':'Proceed'). '</a>';
			
			$adminPage = 'admin.php?page=videocalls&tab=';


			if (self::requirementStatus($requirement, 'skip')) $htmlButton .=  ' <a class="button" href="' . $adminPage . 'setup&unskip=' . $requirement['label'] . '">UnSkip</a>';
			//elseif (!$isPresent) $htmlButton .=  ' <a class="button" href="' . $adminPage . 'setup&skip=' . $requirement['label'] . '">Skip</a>';
			
			if (!$isPresent && ( $requirement['manual'] ?? false ) ) $htmlButton .=  ' <a class="button" href="' . $adminPage . 'setup&done=' . $requirement['label'] . '">Done</a>';
			
			if ($isPresent) $htmlButton .=  ' <a class="button" href="' . $adminPage . 'setup&check=' . $requirement['label'] . '">Check Again</a>';

			if ($requirement['updated'] ?? false)  $htmlButton .=  ' <small style="float:right"> Status: ' .($requirement['status']?'Done':'Required'). ' Updated: ' . date("F j, Y, g:i a", $requirement['updated']) . '</small>';

			$htmlCode = '<div class="vwInfo"><b>' . $requirement['title'] . '</b>: ' . ($isPresent?'Checked. ':'<b>' . $requirement['warning'] . '</b> ') .  'Required for: ' . $requirement['info'] .
				'<br>Quick Fix: ' . $requirement['fix'] . $htmlButton . '</div>';

			break;
		}
		
		return $htmlCode;
	}

}