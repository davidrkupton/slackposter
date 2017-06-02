<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 5/30/2017
 * Time: 7:34 PM
 */

namespace Drupal\slackposter\Controller;


use Drupal\Core\Controller\ControllerBase;

class SlackController extends ControllerBase {

  public function adminOverview(){
	$form['admin'] = array(
	  'title' => array(
		'#markup' => '<h2>Slack Admin</h2>',
	  )
	);
	return $form;
  }
  public function config($name) {
	$config = parent::config("slackposter.settings");
	if(isset($name)) return $config->get($name);
	return $config->getRawData();
  }

  public function helpPage(){
    $form = array(
      'help_page'=>array(
        '#tree'=>true,
		'#type'=>'fieldset',
		'#title'=>"About Slack Poster",
		'#markup'=>"<p>The slackposter module provides Slack Integration, allowing:.<ul>
			<li>Logger (syslog / watchdog) reporting to post to Slack</li>
			<li>A REST API to allow external posting to slack channels</li>
			<li>A popup window to collect information to post to slack (e.g. support dialog)</li>
			<li>Server side PHP library (API) to use in code</li>
			<li>Client side JS library (API) to use in code</li>
			</ul></p>"
	  )
	);
	return $form;
  }

}