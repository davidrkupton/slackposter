<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 6/1/2017
 * Time: 9:41 PM
 */

namespace Drupal\slackposter\Integrate;


class SlackAttachmentField {

  public $title;
  public $value;
  public $short;

  /**
   * SlackAttachmentField constructor.
   * @param null string $title
   * @param null string $value
   * @param null string $short
   */
  function __construct($title = null, $value=null, $short=null) {
	if($title) $this->title = $title;
	if($value) $this->value = $value;
	if($short) $this->short = $short;
	return $this->toArray();
  }

  /**
   * Create an array from this class object
   * @return array
   */
  public function toArray(){
    return [
      'title'=>$this->title,
      'value'=>$this->value,
      'short'=>$this->short,
	];
  }

}