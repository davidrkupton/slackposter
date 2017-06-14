<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 5/31/2017
 * Time: 9:32 AM
 */

namespace Drupal\slackposter\Integrate;

class SlackAttachment {

  public $fallback;
  public $color;
  public $pretext;
  public $author_name;
  public $author_link;
  public $author_icon;
  public $title;
  public $titlelink;
  public $text;
  public $image_url;
  public $thumb_url;
  public $footer;
  public $footer_icon;
  public $ts;
  public $fields;
  public $mrkdwn_in = array("text", "pretext", "fields");

  /**
   * SlackAttachment constructor.
   */
  function __construct(){

	$config = \Drupal::config('system.site');
	global $base_url;
	$this->footer = "Origin: ".$config->get('name') . " (" . $base_url . ")";
	$this->ts = date("U");
	try {
		$this->footer_icon = function_exists("theme_get_setting") ? theme_get_setting('favicon') : '';
	}
//	catch (\Exception $e){}
	catch (\Error $e){}
  }

  /**
   * Add a field to the slack attachment object
   * @param string $_title
   * @param string $_value
   * @param string $_short
   * @return array
   */
  public function addfield($title, $value, $short){

    // initialise the fields array if not yet done
	if(!$this->fields) $this->fields = array();

	// make the field and  save it
	$this->fields[] =  $field = new SlackAttachmentField($title, $value, $short);

	// return it
	return $field->toArray();
  }

  /**
   * Build the object: basically return it as an array ...
   * @return array
   */
  public function toArray(){
    return [
      "fallback" => $this->fallback,
      "color" => $this->color,
      "pretext" => $this->pretext,
      "author_name" => $this->author_name,
      "author_link" => $this->author_link,
      "author_icon" => $this->author_icon,
      "title" => $this->title,
      "titlelink" => $this->titlelink,
      "text" => $this->text,
      "image_url" => $this->image_url,
      "thumb_url" => $this->thumb_url,
      "footer" => $this->footer,
      "ts" => $this->ts,
      "fields" => $this->fields,
	];
  }

}