<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 6/1/2017
 * Time: 11:54 AM
 */

namespace Drupal\slackposter\Test;

use Drupal\slackposter\Integrate\SlackPost;

class SlackposterTester {

  static public function main($what){

	$out = '';

    switch($what){
		case 'a':
		    break;
		default:
			$slack = new SlackPost();
			$out = $slack->post('posting test','#test');
			break;
	}
	return $out;
  }

	static public function openmain($what){

	  $out = '';
		global $base_url;

	  switch($what){
		default:
		  $slack = new SlackPost();
		  $out = $slack->post('posting test','#test');
		  break;
	  }
	  return $out;

	}
}
