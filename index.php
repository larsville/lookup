<?php
# Grab some of the values from the slash command, create vars for post back to Slack
$command = $_POST['command'];
$text = $_POST['text'];
$token = $_POST['token'];
# Check the token and make sure the request is from our team
if ($token != 'yGmqYtpolYQE7j2x9E3vx3YQ') { #replace this with the token from your slash command configuration page
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
if ($text == "?" or $text == "" or $text == "help") {
  // Here if the user typed in no argument, or a question mark, or "help". Give a helpful message.
  $environment = "";
  $msg = 'To find out about X, type "/? X"';
  echo $msg;
  echo Response($text);

} else if ($text == "lars") { 
  echo "*Lars* is my creator.";

} else if ($text == "mendeleev") { 
  echo "The *Mendeleev* conference room is near Lars's desk.\n\nDmitri Ivanovich *Mendeleev* (1834-1907) created the modern periodic table of elements, among many other scientific achievements. wikipedia.org/wiki/Dmitri_Mendeleev";

} else {
  // look up the thing that was typed
  echo "Sorry, I don't know about ".$quote.$text.$quote."."; //$reply;
}

function Response($input = "") {
  $result = "";
  $quote = '"';
  $result = "input = ".$quote.$input.$quote;

  return $result;

} // end Response

?>
