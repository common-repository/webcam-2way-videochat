=== 2Way VideoCalls and Random Chat - HTML5 Webcam Videochat ===
Contributors: videowhisper
Author: VideoWhisper.com
Author URI: https://videowhisper.com
Plugin Name: 2Way VideoCalls and Random Chat - HTML5 Webcam Videochat
Plugin URI: https://paidvideochat.com/html5-videochat/
Donate link: https://videowhisper.com/?p=Invest#level4
Tags: videocall, videochat, webcam, random, chatroulette
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 5.1
Tested up to: 6.6
Stable tag: trunk

Setup video call rooms or random videochat from WordPress frontend. Fully web based, 100% HTML5, no Flash, no downloads. Just setup the call room and share the room link. Integrates HTML5 Videochat application by VideoWhisper.

== Description ==

This plugin implements 2 videochat modes: 
1. Private 2 Way Video Call Rooms
2. Random Videochat with Country/Gender Matchmaking

New: P2P WebRTC is now supported with the new VideoWhisper WebRTC server + STUN/TURN, recommended for optimal latency and quality in private calls.
Get a Free "Developers" account to test P2P WebRTC & STUN/TURN, from [WebRTC Host](https://webrtchost.com/hosting-plans/#WebRTC-Only "WebRTC Host / P2P WebRTC with STUN/TURN"). Limited availability.

VideoCall rooms can be managed by users from frontend and shared by access link.
Random videochat is accessible on a custom page, where users are randomly matched with other users that access that page. 
Videochat pages should be added to cache exceptions. 

= Live Site / Demo =
[2Way Videochat - Random Chat](https://2wayvideochat.com/random-videochat/  "2Way Videochat Random Chat") - random videochat
[2Way Videochat - Calls](https://2wayvideochat.com/video-calls/  "2Way Videochat Calls") - web/mobile video calls, registration required to setup


= HTML5 2 Way Videochat : Video Calls =
* 100% web based HTML5, no downloads, no app store approval required
* 1 to 1, 2 way private video chat
* Send video/audio/screen recordings, emoticons in text chat
* Next button in Random Videochat to move to next match without page reload

= Random Videochat (Roulette) =
* Random videochat page, to get matched with other user online
* Next button in app to move to next match, without reloading page
* Matchmaking by Gender, Country (example: I am Male from US looking for Female from any country.)
* Custom Gender options (plugin settings)
* AJAX filter dropdows, updating without page reload
* Automatically select own country by GeoIP if available

= WordPress 2Way VideoCalls and Random Chat - HTML5 Webcam Videocha Plugin =
* Quickly add 2 way video call rooms and random chat to WP site
* Access permissions (everybody, members, list)
* Members can create and manage rooms
* Menus in admin bar
* Access list per room
* Widget with active rooms list and entry
* Pages for room management, random videochat
* Option to redirect user to videochat setup page after login
* Option to automatically create a video call room for each user
* Easy translation with .po file and settings
* Integrates Mobile App (if available for iOS/Android)

Setup web based HTML5 videocalls on a WordPress site using [VideoWhisper HTML5 Videochat](https://paidvideochat.com/html5-videochat/ "100% Web Based HTML5 Videochat"). 
A Video Chat page is added to the website where members can create and manage their rooms and also a page for random videochat. Can be disabled from settings. Functionality can be implemented as shortcode.

There is a settings page with multiple parameters and permissions. Who can setup rooms and access application can be configured (everybody, members, list of members/roles).

For a more advanced setup with monetization including pay per minute & gifts, see [PaidVideochat Pay Per Minute Webcams Turnkey Site Platform](https://paidvideochat.com "PaidVideochat - WP Pay Per Minute Webcams Turnkey Site Platform"), [Random Matchmaking Videochat - Speed Video Dating with Match Criteria & Monetization](https://paidvideochat.com/random-videochat-match/).

= Hosting Requirements =

This plugin has requirements beyond regular WordPress hosting specifications: specific live streaming servers, certificates, licensing, tools and configuration for HTML5 live camera streaming.

This implementation supports 2 options for WebRTC live streaming servers: 

* P2P via [VideoWhisper WebRTC](https://github.com/videowhisper/videowhisper-webrtc "VideoWhisper WebRTC signaling server on GitHub") with STUN/TURN - recommend for optimal latency
* Relayed streaming via [Wowza SE live streaming](https://videowhisper.com/?p=Requirements "HTML5 Live Streaming Requirements")

Get a Free "Developers" account to test P2P WebRTC with STUN/TURN included, from [WebRTC Host](https://webrtchost.com/hosting-plans/#WebRTC-Only "WebRTC Host / P2P WebRTC with STUN/TURN"). Limited resources available.

== Installation ==

* This plugin requires a specific HTML5 WebRTC live streaming relay for the live streaming features. Before installing this make sure all hosting requirements are met: 
https://videowhisper.com/?p=Requirements
* Enable the plugin from Wordpress admin area and fill the "Settings", including live streaming configuration
* Setup Video Calls page from Pages settings tab and add to your menu.
* Optionally, enable the widget to show online rooms.

== Screenshots ==
1. Random Videochat (Match Roulette) with Filters
2. HTML5 Videochat Call in Dark Mode
3. HTML5 Videochat Call in Light Mode
4. Audio/Video recorder to also insert recordings in text chat (when user connection does not permit live streaming)
5. WP user page to manage video chat rooms
6. Legacy 2 Way Video Chat Flash App (included)

== Effects ==
On PC, add effects to videochat with [SnapCamera](https://snapcamera.snapchat.com "SnapCamera Effects") from SnapChat.

== Documentation ==
* Application Homepage: [VideoWhisper HTML5 Videochat](https://paidvideochat.com/html5-videochat/ "100% Web Based HTML5 Videochat")

== Demo ==
* Videochat website including mobile app:
http://2wayvideochat.com/


== Extra ==
More information, the latest updates, other plugins and non-WordPress editions can be found at https://videowhisper.com/ .

== Changelog ==

= 5.4 = 
* P2P WebRTC is now supported with the new VideoWhisper WebRTC server + STUN/TURN: [VideoWhisper WebRTC Signaling Server](https://github.com/videowhisper/videowhisper-webrtc "VideoWhisper WebRTC Signaling Server")

= 5.3 = 
* Matchmaking Country / Gender for Random Videochat

= 5.2 = 
* Random Videochat mode with shortcode and next button
* Removed Flash 
* PHP8 support

= 5.1 =
* Integrates HTML5 Videochat instead of Flash 2 Way Videochat

= 4.41.19 =
* Auto Room: Automatically create a room for each user on registration or when user accesses management page

= 4.41.3 =
* WP plugin translation with .po files in languages.
* Application translation with setting.
* New settings for extra parameters.

= 4.41 =
* Integrates VideoWhisper 2 Way Video Chat 4.41
* Video Chat room management page in frontend: users can create room, access, obtain link, delete
* Plugin settings including permissions for videochat room setup, videochat access