<?php
# Grab some of the values from the slash command, create vars for post back to Slack
$command = $_POST['command'];
$text = $_POST['text'];
$token = $_POST['token'];
# Check the token and make sure the request is from our team
if($token != 'yGmqYtpolYQE7j2x9E3vx3YQ'){ #replace this with the token from your slash command configuration page
  $msg = "Unauthorized.";
  die($msg);
  echo $msg;
}
$text = strtolower($text);
$quote = '"';
/*
$environment = "";
if($text == "prod"){
  $environment = "";
} else if ($text == "dev" or $text == "qa"){
  $environment = "-".$text;
} else if ($text == "stage" or $text == "staging"){
  $environment = "-staging";
} else {
  $msg = "The environment specified does not exist. Please specify dev, qa, stage or prod.";
  die($msg);
  echo $msg;
}
*/
if($text == "?" or $text == "" or $text == "help"){
  // Here if the user typed in no argument, or a question mark, or "help". Give a helpful message.
  $environment = "";
  $msg = 'To find out about X, type "/? X"';
  //exit($msg);
  echo $msg;
} else if($text == "lars"){ 
  echo "*Lars* is my creator.";
} else if($text == "mendeleev"){ 
  echo "The *Mendeleev* conference room is near Lars's desk.\n\nDmitri Ivanovich *Mendeleev* (1834-1907) created the modern periodic table of elements, among many other scientific achievements. wikipedia.org/wiki/Dmitri_Mendeleev";
} else{
  // look up the thing that was typed
  echo "Sorry, I don't know about ".$quote.$text.$quote."."; //$reply;
}
/*
$user_agent = "BIM360IQSlackHealth/1.0 (https://git.autodesk.com/murithn/iq-health-slash-command; nathan.murith@autodesk.com)";
$environment_url = "https://bim360iq".$environment.".autodesk.com";
$url_to_check = $environment_url."/health";
$ch = curl_init($url_to_check);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
$ch_response = curl_exec($ch);
curl_close($ch);
$response_array = json_decode($ch_response,true);
$current_time = $response_array["time"];
$last_deploy_time = $response_array["deployment_time"];
$deploy_status = strtolower($response_array["status"]["overall"]);
$revision = $response_array["revision"];
$revision_url = "https://git.autodesk.com/BIM360/BIM360IQ/commit/".$revision;
$revision_url = trim($revision_url);
$defaultTimeZone='UTC';
if(date_default_timezone_get()!=$defaultTimeZone){
  date_default_timezone_set($defaultTimeZone);
}
function _date($format="r", $timestamp=false, $timezone=false)
{
    $userTimezone = new DateTimeZone(!empty($timezone) ? $timezone : 'GMT');
    $gmtTimezone = new DateTimeZone('GMT');
    $myDateTime = new DateTime(($timestamp!=false?date("r",(int)$timestamp):date("r")), $gmtTimezone);
    $offset = $userTimezone->getOffset($myDateTime);
    return date($format, ($timestamp!=false?(int)$timestamp:$myDateTime->format('U')) + $offset);
}
$date_format = "l F j, Y, g:i a";
$pretty_current_date = _date($date_format,strtotime($current_time),"America/Los_Angeles");
$pretty_last_deploy_date = _date($date_format,strtotime($last_deploy_time),"America/Los_Angeles");
$response_text = "*<".$environment_url."| BIM 360 IQ ".ucwords($text).">*\n*Current Time:* ".$pretty_current_date."\n*Last Deployed:* ".$pretty_last_deploy_date."\n*<".$revision_url."| Revision Deployed>*";
if($ch_response === FALSE){
  $reply = "BIM 360 IQ ".$text." could not be reached.";
}else{
  if($deploy_status == "good"){
    $reply = ":green_circle: ".$response_text;
  } else{
    $reply = ":red_circle: ".$response_text;
  }
}
*/
//echo "you typed ".$text; //$reply;
