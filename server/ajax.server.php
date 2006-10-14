<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/* Because this is accessed via Ajax we are going to allow the session_id 
 * as part of the get request
 */

$no_session = true;
require_once('../lib/init.php');

/* Verify the existance of the Session they passed in */
if (!session_exists($_REQUEST['sessid'])) { exit(); }

$GLOBALS['user'] = new User($_REQUEST['user_id']);
$action = scrub_in($_REQUEST['action']);

/* Set the correct headers */
header("Content-type: application/xhtml+xml");

switch ($action) { 
	case 'localplay':
		init_preferences();
		$localplay = init_localplay();
		$localplay->connect();
		$function 	= scrub_in($_GET['cmd']);
		$value		= scrub_in($_GET['value']);
		/* Return information based on function */
		switch($function) { 
			case 'play':
			case 'stop':
			case 'pause':
				$results['lp_state'] 	= $localplay->get_user_state($function);	
				$results['lp_playing']	= $localplay->get_user_playing();
			break;
			case 'next':
			case 'prev':
				$results['lp_state']	= $localplay->get_user_state('play');
				$results['lp_playing'] 	= $localplay->get_user_playing();
			break;
			case 'volume_up':
			case 'volume_down':
			case 'volume_mute':
				$status = $localplay->status();
				$results['lp_volume']	= $status['volume'];
			break;
			default:
				$results = array();	
			break;
		} // end switch on cmd
		$localplay->$function($value); 
		$xml_doc = xml_from_array($results);
		echo $xml_doc;
	break;
	case 'change_play_type':
		init_preferences();
		session_id(scrub_in($_REQUEST['sessid']));
		session_start(); 
		$_SESSION['data']['old_play_type'] = conf('play_type'); 
		$pref_id = get_preference_id('play_type');
		$GLOBALS['user']->update_preference($pref_id,$_GET['type']);

		/* Now Replace the text as you should */
		$ajax_url       = conf('ajax_url');
		$required_info  = conf('ajax_info');
		${$_GET['type']} = 'id="pt_active"';
		ob_start();	
		require_once(conf('prefix') . '/templates/show_localplay_switch.inc.php'); 
		$results['play_type'] = ob_get_contents();
		ob_end_clean();
		$xml_doc = xml_from_array($results);
		echo $xml_doc;
	break;
	case 'reloadnp':
		ob_start();
		show_now_playing();	
		$results['np_data'] = ob_get_contents();
		ob_end_clean();
		$xml_doc = xml_from_array($results);
		echo $xml_doc;
	break;
	case 'set_rating':
		ob_start(); 
		$rating = new Rating($_REQUEST['object_id'],$_REQUEST['rating_type']);
		$rating->set_rating($_REQUEST['rating']);
		show_rating($_REQUEST['object_id'],$_REQUEST['rating_type']);
		$key = "rating_" . $_REQUEST['object_id'] . "_" . $_REQUEST['rating_type'];
		$results[$key] = ob_get_contents();
		ob_end_clean();
		$xml_doc = xml_from_array($results);
		echo $xml_doc;
	break;
	default:
		echo "Default Action";
	break;
} // end switch action
?>
