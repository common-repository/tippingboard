<?php
/*
  Plugin Name: Tippingboard
  Plugin URI: https://tippingboard.com/
  Description: Create run and tip on horse racing, greyhounds and harness racing competitions using live events/data.
  Version: 1.0
  Author: Tippingboard.com
  Author URI: https://tippingboard.com
  License: GPLv2+
  Text Domain: Tippingboard
*/
require 'vendor/autoload.php';
use GuzzleHttp\Client;


add_shortcode('tippingboard_competition', 'tippingboard_competition');

function tippingboard_addWinners($content, $event, $position){
	foreach ($event->Contestants as $contestant){
		if ($contestant->Finished == $position){
			$content .= '<p style="font-size:0.8rem;">'.$contestant->Number.' '.$contestant->Name.'</p>';
		}
	}
	return $content;
}

function tippingboard_competition($atts) {
	
	$a = shortcode_atts( array(
		'id' => 'Not Set'		 
	), $atts );

	
	$client = new Client([
		// Base URI is used with relative requests
		'base_uri' => 'https://api.tippingboard.com/',
		// You can set any number of default request options.
		'timeout'  => 30.0,
		'verify' => false
	]);
	$response = $client->request('GET', 'api/competition/web/'.$a['id']);	
	$tipsresponse =  $client->request('GET', 'api/tips/?competitionId='.$a['id']);

	if ($response->getStatusCode()==200) {
		 
		$body = $response->getBody();
		$obj = json_decode($body);
		$tips = json_decode($tipsresponse->getBody());

		$content = '<h2>'.$obj->competition->meeting->Location.' '. $obj->competition->meeting->Type.'
		 - Run by '.$obj->competition->user->firstName.' - Get The App To Tip <a href="https://play.google.com/store/apps/details?id=com.tippingboard.client&pcampaignid=MKT-Other-global-all-co-prtnr-py-PartBadge-Mar2515-1">
		<img width="140px" src="'.plugin_dir_url( __FILE__ ).'google-play-badge.png"></a></h2>';
		$content .= '<table>';
		$content .= '<tr><th></th>';
		foreach($obj->competition->meeting->Events as $event) {
			$start = new DateTime();
			$end = new DateTime($event->StartTime);
			$diff = $start->diff($end);
			$minutes ='';
			if ($event->Status === 'Open'){
				$minutes = $diff->days * 24 * 60;
				$minutes += $diff->h * 60;
				$minutes += $diff->i;
				$minutes .= ' mins';
			}
			$content .= '<th nowrap>';
			$content .= 'Race '. $event->RaceNumber.'<br>'.$event->Status.'<br>'.$minutes;			
			$content .= '</th>';			
		}
		
		$content .= '<th nowrap>Pts<br></th>';
		$content .= '</tr>';
		$content .= '<tr>';
		$content .= '<td>';
			$content .= '';			
			$content .= '</td>';
		foreach($obj->competition->meeting->Events as $event) {
			
			if ($event->Status == 'Final'){
				
				$content .= '<td>';
				$content = tippingboard_addWinners($content, $event, 1);
				$content = tippingboard_addWinners($content, $event, 2);
				$content = tippingboard_addWinners($content, $event, 3);
				$content .= '</td>';
			}else{
				$content .= '<td>';
				$content .= '-';			
				$content .= '</td>';
			}
			
		}
		$content .= '<td>';
		$content .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';			
		$content .= '</td>';
		$content .= '</tr>';
		foreach ($tips->tips as $tip){
			$content .= "<tr>";
			$content .= "<td nowrap>";
			$content .= "<img width='50px' src='".$tip->user->picture."'/><br>".$tip->user->firstName;
			$content .= "</td>";

			foreach($obj->competition->meeting->Events as $event) {
				$found = false;
				foreach ($tip->selection as $selected){
					if ($selected->eventNumber == $event->RaceNumber){
						$content .="<td>".$selected->contestantNumber."</td>";
						$found = true;
					} 
				}
				if (!$found){
					$content .="<td>-</td>";
				}			
			}
			$content .= "<td>".$tip->totalPoints."  </td>";
			$content .= "</tr>";
		}
		$content .= '</table>';
	} else {
		$content = "ERROR";
	}
	
	return $content;
}
?>