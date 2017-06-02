<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 1/13/2015
 * Time: 2:56 PM
 */
include_once $_SERVER["DOCUMENT_ROOT"]."/sites/all/modules/tm_slack/slack_post_cls.php";  // posting class

if($payload=$_POST["payload"]) {
    $payload=(array) json_decode($payload);
    if($slack = new slack_post()) {
        $comment = ($payload["text"] ? $payload["text"] : null);
        $channel = ($payload["channel"] ? $payload["channel"] : null);
        $usern = ($payload["username"] ? $payload["username"] : null);
        if($payload["icon_url"]) $slack->icon= $payload["icon_url"];
        if($payload["url"])  $slack->url= $payload["url"];
        $result =  $slack->post($comment, $channel, $usern);
        unset($slack);
        if(!$result["error"]) echo "ok";
        else echo $result["result"];
    }
}
else{
    echo "Bad Request";
}