<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 5/31/2017
 * Time: 4:11 PM
 */

namespace Drupal\slackposter\Integrate;

class SlackAPI extends SlackPost {

  /**
   * This will import and translate the payload into the object, then validate it.
   * @param string $payload
   * @param string $format
   * @return bool
   * @throws \Exception
   */
  public function setPayload($payload, $format){

	switch($format){
	  case 'json':
	  case 'hal_json':
		$payload = (array) json_decode($payload);
		break;
	  case 'xml':
	    $payload = (array) simplexml_load_string($payload);
	}

	if($payload['text']) $this->comment = $payload['text'];
	if($payload['channel']) $this->channel = $payload['channel'];
	if($payload['username']) $this->username = $payload['username'];
	//if($payload['icon_url']) $this->icon_url = $payload['icon_url'];
	if($payload['url']) $this->url = $payload['url'];

	if(!$this->validatePayload()) throw new \Exception('Malformed Payload');

	return true;

  }

  /**
   * Verify the uploaded payload is valid
   * @return bool
   */
  private function validatePayload(){

	  // must have, at a minimum the text to be published.
	  if(!empty($this->comment)) return true;

	  // make sure the chennel is propoerly prefixed
	  $this->channel = '#' . trim($this->channel, '#');

	  return false;
	}

}

