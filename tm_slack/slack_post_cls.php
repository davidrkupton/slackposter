<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 1/13/2015
 * Time: 3:07 PM
 *
 * Creates a class which can post to a slack channel using the Inward webooks integration
 * usage: $slack = new slack_post($url)         ->  $url is optional and is a slack Webhook URL
 *                                              ->  if no URL passed then the default from the admin settings
 *                                                  for the tm_slack module will be used.
 * Can then set properties for:
 *      url:        as per the $url passed during instantiation
 *      comment:    the comment to be posted to slack
 *      channel:    the channel to post into
 *      username:   the name to be shown as the poster
 *      icon:       [optional] the icon to be shown
 *
 * then call the post method:
 *      $slack->post(comment, channel, username)
 *      (definitions as above)
 *
 * will return an array containing fields for error and the returned value from slack posting (usually just "ok")
 */

/**
 * Attachment fields
 * "attachments": [
{
"fallback": "Required plain-text summary of the attachment.",

"color": "#36a64f",

"pretext": "Optional text that appears above the attachment block",

"author_name": "Bobby Tables",
"author_link": "http://flickr.com/bobby/",
"author_icon": "http://flickr.com/icons/bobby.jpg",

"title": "Slack API Documentation",
"title_link": "https://api.slack.com/",

"text": "Optional text that appears within the attachment",

"fields": [
    {
        "title": "Priority",
        "value": "High",
        "short": false
    }
],

"image_url": "http://my-website.com/path/to/image.jpg",
"thumb_url": "http://example.com/path/to/thumb.png",
 *
"footer": "Slack API",
"footer_icon": "https://platform.slack-edge.com/img/default_application_icon.png",
"ts": 123456789
}
]
 */

class attachment{

    private $a = array();

    function __construct(){

        global $tmu_var;

        if(!isset($tmu_var)){
            if(function_exists("variable_get")) $tmu_var = variable_get('tm_utilities');
            else{
                $db=new tm_database();
                $result = $db->doQuery("SELECT value FROM {variable} WHERE name='tm_utilities'");
                if($settings = $result->fetch_object())  return unserialize($settings->value);
            }
        }

        $this->a["mrkdwn_in"] = array("text", "pretext", "fields");
        $this->a["footer"] = "Origin: ".$tmu_var['general']['app_id']. " (".$_SERVER['SERVER_NAME'].")";
        $this->a["footer_icon"] = function_exists("theme_get_setting") ? theme_get_setting('favicon') : '';
        $this->a["ts"] = date("U");
    }

    function fallback($_fallback){
        if(isset($_fallback)) $this->a["fallback"] = $_fallback;
        return $this->a["fallback"];
    }
    function color($_color){
        if(isset($_color)) $this->a["color"] = $_color;
        return $this->a["color"];
    }
    function pretext($_pretext){
        if(isset($_pretext)) $this->a["pretext"] = $_pretext;
        return $this->a["pretext"];
    }
    function authorname($_authorname){
        if(isset($_authorname)) $this->a["author_name"] = $_authorname;
        return $this->a["author_name"];
    }
    function authorlink($_authorlink){
        if(isset($_authorlink)) $this->a["author_link"] = $_authorlink;
        return $this->a["author_link"];
    }
    function authoricon($_authoricon){
        if(isset($_authoricon)) $this->a["author_icon"] = $_authoricon;
        return $this->a["author_icon"];
    }
    function title($_title){
        if(isset($_title)) $this->a["title"] = $_title;
        return $this->a["title"];
    }
    function titlelink($_titlelink){
        if(isset($_titlelink)) $this->a["title_link"] = $_titlelink;
        return $this->a["title_link"];
    }
    function text($_text){
        if(isset($_text)) $this->a["text"] = $_text;
        return $this->a["text"];
    }
    function addfield($_title, $_value, $_short){
        $field = array();
        if(isset($_title)) $field['title'] = $_title;
        if(isset($_value)) $field['value'] = $_value;
        if(isset($_short)) $field['short'] = $_short;
        return $this->a["fields"][] = $field;
    }
    function imageurl($_imageurl){
        if(isset($_imageurl)) $this->a["image_url"] = $_imageurl;
        return $this->a["image_url"];
    }
    function thumburl($_thumburl){
        if(isset($_thumburl)) $this->a["thumb_url"] = $_thumburl;
        return $this->a["thumb_url"];
    }
    function footer($_pretext){
        if(isset($_footer)) $this->a["footer"] = $_footer;
        return $this->a["footer"];
    }
    function footericon($_footericon){
        if(isset($_footericon)) $this->a["footer_icon"] = $_footericon;
        return $this->a["footer_icon"];
    }
    function ts($_ts){
        if(isset($_ts)) $this->a["ts"] = $_ts;
        return $this->a["ts"];
    }

    function build(){
        return $this->a;
    }
}

class slack_post {

    public $url = null;
    public $comment = null;
    public $channel = null;
    public $username;
    public $_icon;
    private $attachment;
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
    private $_referring_module = null;
    private $username_suffix = null;
    private $attachments = array();
    private $_settings = array();

    function __construct($referring_module = "default", $_webhook_url = null){

        if(!class_exists("tm_database")) {
            // probably not running from drupal framework ...
            include_once($_SERVER["DOCUMENT_ROOT"]."/sites/all/modules/tm_utilities/tm_utilities.module");
            tm_utilities_init();
        }

        $this->attachment = new attachment();

        global $tmu_var;

        // get the admin settings
        $settings = (isset($tmu_var['general']['app_id']) ? $tmu_var : $this->_getDrupalSettings('tm_utilities'));
        $this->_settings = $settings + $this->_getDrupalSettings('tm_slack_settings');
        $this->channel = $this->_settings['channels']['default'];
        $this->username_suffix = $this->_settings['general']['app_id'];

        // process arguments
        $this->_referring_module = $referring_module;
        if(isset($_webhook_url)) $this->url = $_webhook_url;           //allow the slack webhook URL to be set during initalisation
        else $this->url = $this->_settings['integration'];

        // initialise variables
        $this->username = "Website User";
        $this->_icon = $this->_getIcon();

    }
    function __destruct(){}

    function reformat($comment){

        // replace line breaks
        $comment = str_ireplace(array("<br>","<br />","<br/>"), "\n", $comment);

        // replace bolds and add slack bold formats
        $patterns = array(
            '/<(\/?)b\b((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>/',         // finds bold tags
            '/<(\/?)i((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>/',         // finds bold tags
        );
        $replace = array(
            '*',
            "_"
        );
        $comment = preg_replace($patterns, $replace, $comment);

        // replace any tabbed columns, keep only the first tab
        $comment = preg_replace("/(\t)\\1+/", "\t", $comment);

        // strip any tags out, leave only anchors
        $comment = strip_tags($comment, "<a>");

        // replace html anchors
        $whatbits = explode("<", $comment);
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

        $comment = implode("", $whatbits);
        $comment = mb_convert_encoding($comment, "UTF-8", "HTML-ENTITIES");


        return $comment;


    }

    function channel($c){
        if(isset($c)) $this->channel = $c;
        return $this->channel;
    }

    function post($comment=null, $channel=null, $usern=null, $postType="default", $sanitize = false){

        // handle inputs
        if(isset($comment)) $this->comment = $comment;
        if(isset($channel)) $this->channel = $channel;
        if(isset($usern)) $this->username = $usern;

        // process & sanitize the message
        if(isset($sanitize)){
            if(is_array($this->comment)) $this->comment['text'] = $this->reformat($this->comment['text']);
            else $this->comment = $this->reformat($this->comment);
        }

        // process the username
        if(isset($this->username_suffix)) $this->username.=" -".$this->username_suffix;

        // process the icons
        if(!isset($this->icon)) $this->_icon = $this->_getIcon($postType);

        // process the URL and channel
        if(!isset($this->channel)) {
            if($settings = $this->_getDrupalSettings('tm_slack_settings')){
                if (!isset($this->channel)) $this->channel = $settings['channels']['default'];
            }
        }

        // validate if can set this slack message or not.
        if(!isset($this->comment)) return array('error'=>true, 'result'=>"Bad Request - Nothing to post");

        // now create the array to pass to slack
        $text = array(
            'username' => $this->username,
            'channel' => $this->channel,
            'icon_url' => $this->_icon,
            'mrkdwn' => true
        );

        // for attachments see notes at https://api.slack.com/docs/attachments
        if(empty($this->attachments) && !is_array($this->comment)) {$this->comment.= "\n_Origin: ".$this->_settings['general']['app_id']. " (".$_SERVER['SERVER_NAME'].")_";}
        else {$text['attachments'] = $this->attachments;}

        if(is_array($this->comment)) {
            if(empty($this->comment['footer'])) {
                $tmp = new attachment();                            // create attachment to get the template
                $this->comment = $tmp->build() + $this->comment;    // now merge the template with whatever we have ...
            }
            $text['attachments'][] = $this->comment;
        }
        else  {
            $text['text'] = $this->comment;
        }

        return $this->_curl_post($this->url, $text);

    }

    function isConnected(){
        return ($this->ch)?true:false;
    }

    function attachment($a = null){

        if(!isset($a)) return new attachment();

        if(isset($a['text'])) $a['text'] = $this->reformat($a["text"]);
        if(isset($a['pretext'])) $a['pretext'] = $this->reformat($a["pretext"]);
        foreach($a['fields'] as &$field) {
            $field['value'] = $this->reformat($field['value']);
        }

        $this->attachments[] = $a;

        return true;
    }

    /**
     * Return the requested icon, or the default icon
     * @param null $referring_module
     * @param $type
     * @return string
     */
    function selectIcon($referring_module = "default", $type){

        // temporarily replace the $_referring_module so we can get the right icon
        $rem = $this->_referring_module;
        $this->_referring_module = $referring_module;

        // get the icon
        $icon = $this->_getIcon($type);

        // reset $_referring_module
        $this->_referring_module = $rem;

        // return the icon
        return $icon;
    }

    /**
     * Set or get the icon to be used for the posting.
     * @param null $iconURL
     * @return null
     */
    function icon($iconURL = null){
        if(!empty($iconURL)) $this->_icon = $iconURL;
        return $this->_icon;
    }

    /**
     * Get the icon to be used for the posting
     * @param null $type
     * @return string
     */
    protected function _getIcon($type = "default"){

        $icon = null;

        // First of all, if the icon has been manually set using this->icon, then return it.
        if(!empty($this->_icon)) $icon = $this->_icon;

        // if we have a referring_module and a type, then return that
        elseif(!empty($this->_referring_module) && isset($this->arrImages[$this->_referring_module][$type]))
            $icon = $this->arrImages[$this->_referring_module][$type];

        //so, we have no referring module, use the default module
        if(empty($icon)) $icon = $this->arrImages['default'][$type];

        // check the url is absolute
        if(stripos(strtolower($icon),"http")===false && substr($icon,0,2)!="//"){

            if(substr($icon,0,2) != "/"){
                // assumed link is relative to slack module
                $path = "/sites/all/modules/tm_slack/";
                if(function_exists("drupal_get_path")) $path = "/".drupal_get_path("module","tm_slack")."/";
                $icon = "http://". $_SERVER['SERVER_NAME'].$path.$icon;
            }
            else {
                //if not assumed link is off the servername
                $icon = "http://" . $_SERVER['SERVER_NAME'] . $icon;
            }
        }
        return $icon;
    }

    protected function _getDrupalSettings($name){
        $db=new tm_database();
        $result = $db->doQuery("SELECT value FROM {variable} WHERE name='".$name."'");
        if($settings = $result->fetch_object())  return unserialize($settings->value);
        return false;
    }

    protected function _curl_post($url, $payload){

        //execute post
        try{
            if(!($ch = curl_init())) {
                return array(
                    'error'=>true,
                    'result'=>"Bad Request - Cannot create CURL object"
                );
            }

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

            return array(
                'error' => ($result != "ok"),       // $result = "ok" if post made
                'result' => $result
            );
        }
        catch (Exception $e) {
            return array(
                'error'=>true,
                'result'=>$e->getMessage()
            );
        }

    }

} 