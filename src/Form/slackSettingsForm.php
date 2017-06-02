<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 5/30/2017
 * Time: 7:36 PM
 */

namespace Drupal\slackposter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;

class slackSettingsForm extends ConfigFormBase {

  public function getFormId() {
	return 'slackposter_admin_settings';
  }

  protected function getEditableConfigNames() {
	return ["slackposter.settings"];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
	
    $config = $this->config('slackposter.settings');

    $form = array(
      '#tree'=>true,
	  '#validate' => ['slackposter_form_slackposter_admin_settings_validate'],

	  'slackposter'=>array(

	    '#type' => 'details',
		'#title' => t('General Configuration'),
		'#description' => t('General Configuration'),
		'#open' => TRUE,


		'integration' => array(
		  '#type' => 'textfield',
		  '#title' => t('Incoming Webhook URL'),
		  '#default_value' => (strlen($config->get('integration'))>0) ? $config->get('integration') : "",
		  '#description' => t("This is the Webhook URL for the 'Incoming Webhook' config in slack (https://tradermade.slack.com/apps/A0F7XDUAZ-incoming-webhooks).<br/>It has a default channel that it publishes to if one is not specified in the tm_slack API calls."),
		  '#required' => TRUE,
		),
		'channels' => array(

		  '#type' => 'details',
		  '#title' => t('Slack Channels'),
		  '#description' => t('Confgure the channels that the system will post to.<ul><li>These channels must exist in Slack already.</li><li>Prefix with "#".</li></ul>'),
		  '#open' => FALSE,

		  'channels.default' => array(
			'#type' => 'textfield',
			'#title' => t('Default channel to post to'),
			'#default_value' => (strlen($config->get('channels.default'))>0) ? $config->get('channels.default') : "test",
			'#description' => t("This channel will be used by default when no other channel is specified (or overriden by a default in another module)<br/>Note this will override the default channel specified by the slack 'incoming webhook' at all times.."),
			'#required' => TRUE,
		  ),
		  'channels.support' => array(
			'#type' => 'textfield',
			'#title' => t('Support Call Postings'),
			'#default_value' => (strlen($config->get('channels.support'))>0) ? $config->get('channels.support') : "test",
			'#description' => t("Channel to post support calls to."),
			'#required' => TRUE,
		  ),
		),
		'salesforce' => array(

		  '#type' => 'details',
		  '#title' => t('Salesforce Integration'),
		  '#description' => t('Configure posting to SalesForce'),
		  '#open' => FALSE,

		  'salesforce.enable' => array(
			'#type' => 'checkbox',
			'#title' => t('Enable SalesForce integration for support calls posted to slack'),
			'#default_value' => (strlen($config->get('salesforce.enable'))>0) ? $config->get('salesforce.enable') : 0,
			'#description' => t("This module can be configured to post to salesforce at the same time support postings are made to slack."),
		  ),
		),
		'watchdog' => array(

		  '#type' => 'details',
		  '#title' => t('Capture syslog entries (watchdog)'),
		  '#description' => t('Cross-post syslog entries into a slack channel.'),
		  '#open' => FALSE,

		  'watchdog.enabled' => array(
			'#type' => 'checkbox',
			'#title' => t('Post to Watchdog'),
			'#default_value' => (strlen($config->get('watchdog.enabled'))>0) ? $config->get('watchdog.enabled') : 0,
			'#description' => t("Enable watchdog (drupal syslog) postings to Slack in real-time."),
		  ),
		  'watchdog.integration' => array(
			'#type' => 'textfield',
			'#title' => t('Incoming Webhook URL (watchdog)'),
			'#default_value' => (!empty($config->get('watchdog.integration'))) ? $config->get('watchdog.integration') : "",
			'#description' => t("This is the Webhook URL for the 'Incoming Webhook' config in slack (https://tradermade.slack.com/apps/A0F7XDUAZ-incoming-webhooks).<br/>It has a default channel that it publishes to if one is not specified in the tm_slack API calls.<br/>This Webhook can be the same as the default above, or different."),
		  ),
		  'watchdog.channel' => array(
			'#type' => 'textfield',
			'#title' => t('Posting Channel'),
			'#default_value' => !(empty($config->get('watchdog.channel'))) ? $config->get('watchdog.channel') : "test",
			'#description' => t("Which Slack channel to post to."),
		  ),
		  'watchdog.severity' => array(
			'#type' => 'select',
			'#options' => RfcLogLevel::getLevels(),
//			'#options' => watchdog_severity_levels(),
			'#title' => t('Severity Filter'),
			'#required' => false,
			'#multiple' => true,
			'#default_value' => (!empty($config->get('watchdog.severity'))) ? $config->get('watchdog.severity') : array(),
			'#description' => t("Select severity levels to include"),
		  ),
		  'watchdog.filterOut' => array(
			'#type' => 'textfield',
			'#title' => t('Log Type Filter'),
			'#default_value' => (!empty($config->get('watchdog.filterOut'))) ? $config->get('watchdog.filterOut') : "",
			'#description' => t("Provide a list of log types to EXCLUDE from posting to Slack.<br>A commas separated list of log types (e.g. php,debug)"),
		  ),
		  'watchdog.keywords' => array(
			'#type' => 'textfield',
			'#title' => t('Keyword Filter'),
			'#default_value' => (!empty($config->get('watchdog.keywords'))) ? $config->get('watchdog.keywords') : "",
			'#description' => t("Provide a list of keywords in the log body to EXCLUDE from posting to Slack.<br>A commas separated list (e.g. tuesday, test.inc)"),
		  ),
		),
	  ),
	);
	return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $settings = $form_state->getValue('slackposter');

    // validation
	$settings['channels']['channels.default'] = '#'.trim( $settings['channels']['channels.default'],'#');
	$settings['channels']['channels.support'] = '#'.trim( $settings['channels']['channels.support'],'#');
	$settings['watchdog']['watchdog.channel'] = '#'.trim( $settings['watchdog']['watchdog.channel'],'#');
	if($settings['watchdog']['watchdog.enabled'] && !$settings['watchdog']['watchdog.channel'])
	  $settings['watchdog']['watchdog.channel'] = $settings['channels']['channels.default'];

	\Drupal::logger('slackposter')->info('Hi');

	$this->config('slackposter.settings')
	  ->set('integration', $settings['integration'])
	  ->set('channels.default', $settings['channels']['channels.default'])
	  ->set('channels.support', $settings['channels']['channels.support'])
	  ->set('salesforce.enable', $settings['salesforce']['salesforce.enable'])
	  ->set('watchdog.enabled', $settings['watchdog']['watchdog.enabled'])
	  ->set('watchdog.integration', $settings['watchdog']['watchdog.integration'])
	  ->set('watchdog.channel', $settings['watchdog']['watchdog.channel'])
	  ->set('watchdog.severity', $settings['watchdog']['watchdog.severity'])
	  ->set('watchdog.filterOut', $settings['watchdog']['watchdog.filterOut'])
	  ->set('watchdog.keywords', $settings['watchdog']['watchdog.keywords'])
	  ->save();
	parent::submitForm($form, $form_state);
  }

}