<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 5/31/2017
 * Time: 12:48 AM
 */

namespace Drupal\slackposter\Logger;

use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\slackposter\Controller\SlackController;
use Drupal\slackposter\Integrate\SlackPost;
use Psr\Log\LoggerInterface;
use Drupal\user\Entity\User;

class SlackLogger implements LoggerInterface{

  use RfcLoggerTrait;
  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = array()) {

    global $base_url;

	$controller = new SlackController();
	$settings = $controller->config('watchdog');

	if($settings['enabled']) {


	  $body = htmlspecialchars_decode(strip_tags(t($message, $context), "<a><br>"));

	  // first do the necessary filtering
	  if(!empty($settings['severity']) && !in_array($level, $settings['severity'])) return;
	  if(!empty($settings['filterOut'])) {
		foreach (explode(',', $settings['filterOut']) as $filter) {
		  if ($context['channel'] == trim($filter)) {
			return;
		  }
		}
	  }
	  if(!empty($settings['keywords'])) {
		foreach (explode(',', $settings['keywords']) as $filter) {
		  if (stripos($body, trim($filter)) !== FALSE) {
			return;
		  }
		}
	  }

	  // remove extra spaces
	  $body = preg_replace('/[ ]{2,}|[\t]/'," ",$body);
	  $body = str_replace(array('<br>','<br />','<br/>'),"\n",$body);

	  try {

		$severity = RfcLogLevel::getLevels();

		if (empty($level)) $level = RfcLogLevel::DEBUG;
		elseif ($context['channel'] == 'debug') $level = RfcLogLevel::DEBUG;

		if (isset($context['user'])) $account = $context['user'];
		elseif (isset($context['uid'])) $account = User::load($context['uid']);

		//todo see what this is there for...
		//if (is_numeric($context['variables'])) $context['variables'] = array($context['variables']);

		$slack = new SlackPost('watchdog', $settings['integration']);

		$attachment = $slack->attachment();
		$attachment->fallback = $body;
		$attachment->title = 'Watchdog ' . $context['channel'] . " : " . ucwords((string) $severity[$level]);
		$attachment->titlelink = $base_url . '/admin/reports/dblog';
		$attachment->text = $body;
		$attachment->addfield("Referer:", (empty($context['referer']) ? '' : $context['referer']), true);
		$attachment->addfield("User:", ((isset($account->name)) ? $account->name : 'Anonymous'). '<br>ip:'.$_SERVER['REMOTE_ADDR'], true)  ;
		$attachment->addfield("Request:", (empty($context['request_uri']) ? '' : $context['request_uri']), true);
		$attachment->addfield("Link:", (empty($context['link']) ? '' : str_ireplace('"/', '"' . $base_url . '/', $context['link'])), true);

		$attachment->color = 'warning';
		if ($level == RfcLogLevel::EMERGENCY
		  || $level == RfcLogLevel::CRITICAL
		  || $level == RfcLogLevel::ERROR
		) $attachment->color = 'danger';
		if ($level == RfcLogLevel::INFO
		  || $level == RfcLogLevel::NOTICE
		) $attachment->color = 'good';
		if ($level == RfcLogLevel::DEBUG)
		  $attachment->color = '#7D26CD';
		$slack->attachment($attachment);

		$slack->channel = ($settings['channel'] ? $settings['channel'] : '#fxnav-dev-log-tail');

		$slack->post("New entry in SysLog for website");

	  } catch (Exception $e) {}
	}
  }

}