<?php

namespace Drupal\slackposter\Test;

use Drupal\slackposter\Integrate\SlackPost;

/**
 * Testing for slackposter.
 */
class SlackposterTester {

  /**
   * Creates a test post to slack.
   *
   * @inheritDoc.
   */
  public static function main($what) {

    $out = '';

    switch ($what) {
      case 'a':
        break;

      default:
        $slack = new SlackPost();
        $out = $slack->post('posting test', '#test');
        break;
    }
    return $out;
  }

  /**
   * Creates a test post to slack.
   *
   * @inheritDoc.
   */
  public static function openmain($what) {

    $out = '';
    global $base_url;

    switch ($what) {
      case "":
      default:
        $slack = new SlackPost();
        $out = $slack->post('posting test', '#test');
        break;
    }
    return $out;

  }

}
