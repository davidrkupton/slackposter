<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 5/31/2017
 * Time: 9:22 AM
 */

namespace Drupal\slackposter\Integrate;

use Masterminds\HTML5\Exception;

class SlackPost {

  /**
   * A url to include which is clickable in the post
   * @var string
   */
  public $url;

  /**
   * The text to be posted.
   * @var string	this could be an attachment array
   */
  public $comment;

  /**
   * Channel to post to.
   * @var string	should be prefixed with #
   */
  public $channel;

  /**
   * Username for posting credit.
   * @var string	A (random) username to attribute the slack post to
   */
  public $username;

  /**
   * An icon to be placed next to the post
   * @var string	Needs to be a path relative to the module or absolute and complete
   */
  private $icon_url;

  /**
   * Default set of Images to use as posting icons
   * The outside key is a module name, its value is a key/value pair which is a default image for posting severities.
   * @var array		array of relative-path images
   */
  private $arrImages = array(
	"default"=>array(
	  "default"=>"images/tradermade-user.png",
	  "info"=>"images/tradermade-user.png",
	  "warning"=>"images/tradermade-user.png",
	  "error"=>"images/tradermade-user.png"
	),
	"scheduler"=>array(
	  "default"=>"images/schedule-user.png",
	  "info"=>"images/schedule-user-green.png",
	  "warning"=>"images/schedule-user-yellow.png",
	  "error"=>"images/schedule-user-red.png"
	),
	"payments"=>array(
	  "default"=>"images/payment-user-green.png",
	  "info"=>"images/payment-user-green.png",
	  "warning"=>"images/payment-user-yellow.png",
	  "error"=>"images/payment-user-red.png"
	),
	"support"=>array(
	  "default"=>"images/support-user-green.png",
	  "info"=>"images/support-user-green.png",
	  "warning"=>"images/support-user-yellow.png",
	  "error"=>"images/support-user-red.png"
	),
	"tradermade"=>array(
	  "default"=>"images/tradermade-user.png",
	  "info"=>"images/tradermade-user.png",
	  "warning"=>"images/tradermade-user.png",
	  "error"=>"images/tradermade-user.png"
	),
	"web"=>array(
	  "default"=>"images/web-user.png",
	  "info"=>"images/web-user-green.png",
	  "warning"=>"images/web-user-yellow.png",
	  "error"=>"images/web-user-red.png"
	),
  );

  /**
   * The referring module so that an icon set and attribution can be set
   * @var null|string this is set during config
   */
  private $_referring_module;

  /**
   * Collection of SlackAttachments
   * @var array		an array collection of SlackAttachment objects
   */
  private $attachments;

  /**
   * Class settings which have been set by slackposter admin/config pages
   * @var array		settings from slackposter.settings
   */
  private $_settings = array();

  /**
   * SlackPost constructor.
   * @param string $referring_module
   * @param null string $_webhook_url
   */
  function __construct($referring_module = "default", $_webhook_url = null){

	// get the admin settings
	$config = \Drupal::config('slackposter.settings');
	$this->_settings = $config->getRawData();
	$config = \Drupal::config('system.site');
	$this->_settings['general']['app_id'] = $config->get('name');

	$this->channel = $this->_settings['channels']['default'];
	$this->username_suffix = $this->_settings['general']['app_id'];

	// process arguments
	$this->_referring_module = $referring_module;
	$this->url = (isset($_webhook_url))  ? $_webhook_url : $this->_settings['integration'];           //allow the slack webhook URL to be set during initalisation

	// initialise variables
	$this->username = "Website User";
	$this->icon_url = $this->selectIcon($referring_module,'default');

  }

  /**
   * Set or get the icon to be used for the posting.
   * @param null $iconURL
   * @return null
   */
  public function icon($iconURL = null){
	if(!empty($iconURL)) $this->icon_url = $iconURL;
	return $this->icon_url;
  }

  /**
   * Utility which reformats a string that will work for Slack
   * (This is intended mainly to parse HTML into slack markup/(down?))
   * @param string $string
   * @return string
   */
  private static function reformat($string){

	// replace HTML line breaks
	$string = str_ireplace(array("<br>","<br />","<br/>"), "\n", $string);

	// replace HTML bolds and add slack bold formats
	$patterns = array(
	  '/<(\/?)b\b((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>/',         // finds bold tags
	  '/<(\/?)i((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>/',         // finds bold tags
	);
	$replace = array(
	  '*',
	  "_"
	);
	$string = preg_replace($patterns, $replace, $string);

	// replace any tabbed columns, keep only the first tab
	$string = preg_replace("/(\t)\\1+/", "\t", $string);

	// strip any tags out, leave only anchors
	$string = strip_tags($string, "<a>");

	// replace HTML anchors
	$whatbits = explode("<", $string);
	foreach($whatbits as $key=>&$tag){
	  if(stripos($tag, "a href")===0){
		$tag = str_ireplace(array('a href="',"a href='"),"<", $tag);
		$tag =  str_ireplace(array("'>",'">'),"|", $tag);
		$tag .= ">";
	  }
	  elseif(substr($tag,0,3)=="/a>") {
		$tag = str_ireplace("/a>", "", $tag);
		if(trim($tag=="")) unset($whatbits[$key]);
	  }
	  elseif($key!=0) $tag = '<'.$tag;
	}
	$string = implode("", $whatbits);

	// encode to UTF8 for slack
	$string = mb_convert_encoding($string, "UTF-8", "HTML-ENTITIES");

	return $string;
  }

  // create an attachment object
  public function attachment(SlackAttachment $attachment = null){

	if(!$attachment) return new SlackAttachment();

	// make sure the message elements of the attachment are slack mark-up compliant
	if($attachment->text) $attachment->text = $this->reformat($attachment->text);
	if($attachment->pretext) $attachment->pretext = $this->reformat($attachment->pretext);
	foreach($attachment->fields as &$field) {
	  $field->value = $this->reformat($field->value);
	}

	// now save the attahment to this class
	$this->attachments[] = $attachment;

	return true;
  }

  /**
   * Add a new module and icon-set at runtime
   * @param string $modulename
   * @param array $defaultIcons
   */
  public function addModule(string $modulename, array $defaultIcons){
    $this->_referring_module[$modulename] = $defaultIcons;
  }

  /**
   * Return the requested icon, or the default icon
   * @param null $referring_module
   * @param $type
   * @return string
   */
  protected function selectIcon($referring_module = null, $type = null){

    global $base_url;

    // use the default modules default icon so we are sure to have something.
	$icon_url =  $this->arrImages['default']['default'];

    // logic tree to find the correct icon to use
    if($referring_module && $type) {		// have requested a specific module and type,
      //  so get that icon
	  if(isset($this->arrImages[$referring_module][$type])) $icon_url = $this->arrImages[$referring_module][$type];
	}

    elseif($referring_module && !$type) {		// requested a module but no type,
      // so get default for that module
	  if(isset($this->arrImages[$referring_module]['default'])) $icon_url =  $this->arrImages[$referring_module]['default'];
	}

    elseif(!$referring_module && $type) {		// requested type but no module
	  // if there is a module set from instatiation, use that, otherwise use the default module icon set
      if($this->_referring_module) {
		if(isset($this->arrImages[$this->_referring_module][$type])) $icon_url =  $this->arrImages[$this->_referring_module][$type];
	  }
      else {
        if(isset($this->arrImages['default'][$type])) $icon_url =  $this->arrImages['default'][$type];
	  }
	}

    elseif(!$referring_module && !$type) {		// there is no module or type
      // so use any con already manually set, or use the default type from the module set
	  // during initialisation, or else use the default from the default ...
	  if($this->icon_url) {
	    $icon_url =  $this->icon_url;
	  }
	  elseif($this->_referring_module) {
	    if(isset($this->arrImages[$this->_referring_module]['default'])) $icon_url =  $this->arrImages[$this->_referring_module]['default'];
	  }
	  else {
	    $icon_url =  $this->arrImages['default']['default'];
	  }
	}

	// now make sure its got a full path
	if(stripos(strtolower($icon_url),"http")===false && substr($icon_url,0,2)!="//"){

	  if(substr($icon_url,0,2) != "/"){
		// assumed link is relative to slack module
		$icon_url = $base_url."/".drupal_get_path("module","slackposter")."/".$icon_url;
	  }
	  else {
		//if not assumed link is off the servername
		$icon_url = $base_url.$icon_url;
	  }
	}

	return $icon_url;

  }

  /**
   * Post helper function.  This is called externally to make the post to slack
   * @param string $comment
   * @param string $channel
   * @param string $usern
   * @param string $postType
   * @param string $sanitize
   * @return array
   */
  public function post($comment=null, $channel=null, $usern=null, $postType="default", $sanitize = false){

	// handle inputs
	if(!empty($comment)) $this->comment = $comment;
	if(!empty($channel)) $this->channel = $channel;
	if(!empty($usern)) $this->username = $usern;

	// process & sanitize the message
	if(!empty($sanitize)){
	  if(is_array($this->comment)) $this->comment['text'] = $this->reformat($this->comment['text']);
	  else $this->comment = $this->reformat($this->comment);
	}

	// process the username
	if(isset($this->username_suffix)) $this->username.=" -".$this->username_suffix;

	// process the icons
	if(!isset($this->icon)) $this->icon_url = $this->selectIcon(null, $postType);

	// process the URL and channel
	if(!isset($this->channel)) $this->channel = $this->_settings['channels']['default'];

	// validate if can set this slack message or not.
	$response = new SlackRestResponse();
	if(!isset($this->comment)) return $response->setResult(false,"Bad Request - Nothing to post")->toArray();

	// now build the array to pass to slack
	$text = array(
	  'username' => $this->username,
	  'channel' => $this->channel,
	  'icon_url' => $this->icon_url,
	  'mrkdwn' => true
	);

	// for attachments see notes at https://api.slack.com/docs/attachments
	if(empty($this->attachments) && !is_array($this->comment)) {
	  $this->comment.= "\n_Origin: ".$this->_settings['general']['app_id']. " (".$_SERVER['SERVER_NAME'].")_";
	}
	else {$text['attachments'] = $this->attachments;}

	if(is_array($this->comment)) {
	  if(empty($this->comment['footer'])) {
		$tmp = new slackAttachment();                            // create attachment to get the template
		$this->comment = $tmp->toArray() + $this->comment;    	 // now merge the template with whatever we have ...
	  }
	  $text['attachments'][] = $this->comment;
	}
	else  {
	  $text['text'] = $this->comment;
	}

	// do the actual posting and return the output.
	return $this->_curl_post($this->url, $text);

  }

  /**
   * This is the CURL to
   * @param $url
   * @param $payload
   * @return array
   */
  protected function _curl_post($url, $payload){

	// create a standard rest response object
	$response = new SlackRestResponse($payload);

	// do the posting using curl
	try{
	  if(!($ch = curl_init())) return $response->setResult( false,"Bad Request - Cannot create CURL object")->toArray();

	  curl_setopt($ch, CURLOPT_POST, true);
	  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	  curl_setopt($ch, CURLOPT_HEADER, FALSE);
	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
	  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)');
	  $header[] = 'Content-Type:application/x-www-form-urlencoded';
	  curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

	  //set the url, number of POST vars, POST data
	  curl_setopt($ch,CURLOPT_URL, $url);
	  curl_setopt($ch,CURLOPT_POST, count($payload));
	  curl_setopt($ch,CURLOPT_POSTFIELDS, "payload=".urlencode(json_encode($payload)));

	  $result = curl_exec($ch);
	  curl_close($ch);

	  if($result=="ok") return $response->setResult(true)->toArray();
	  return $response->setResult(false,$result)->toArray();

	}
	catch (Exception $e) {
	  return $response->setResult(false, $e->getMessage())->toArray();
	}

  }

}