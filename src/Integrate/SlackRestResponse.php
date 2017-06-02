<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 6/1/2017
 * Time: 9:43 PM
 */

namespace Drupal\slackposter\Integrate;


class SlackRestResponse {

  /**
   * @var string
   */
  protected $error;
  /**
   * @var string
   */
  protected $result;
  /**
   * @var array
   */
  protected $message;

  public function __construct(array $message = array(), bool $success = true, string $errMessage = "") {
	$this->setResult($success, $errMessage);
	$this->setMessage($message);
	return $this;
  }

  public function setResult(bool $success, string $errMessage = ""){
	$this->result = ($success ? "OK" : "error");
	if(!$success && $errMessage!="") $this->setError($errMessage);
	return $this;
  }
  public function setError(string $errorMessage){
    $this->error = $errorMessage;
    return $this;
  }
  public function setMessage(array $message){
    $this->message = $message;
    return $this;
  }

  public function toArray(){
	$arr = [
	  'result'=>$this->result,
	  'message'=>$this->message,
	];
	if($this->result=='error') $arr['error'] = $this->error;

    return $arr;
  }

}