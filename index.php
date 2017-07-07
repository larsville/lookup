<?php

# Grab some of the values from the slash command, create vars for post back to Slack
$command = $_POST['command'];
$text = $_POST['text'];
$token = $_POST['token'];

# Check the token and make sure the request is from our team
if($token != 'yGmqYtpolYQE7j2x9E3vx3YQ'){ #replace this with the token from your slash command configuration page
  $msg = "Unauthorized.";
  exit($msg);
  echo $msg;
}

echo Response($text)
//echo "you typed ".$text;
/*
if($text == "?" or $text == "" or $text == "help"){
  // Here if the user typed in no argument, or a question mark, or "help". Give a helpful message.
  $msg = 'To find out about X, type "/? X"';
  //exit($msg);
  echo $msg;
} else if($text == "lars"){ 
  echo "*Lars* created me.";
} else if($text == "mendeleev"){ 
  echo "The *Mendeleev* conference room is near Lars's desk.\n\nDmitri Ivanovich *Mendeleev* (1834-1907) created the modern periodic table of elements, among many other scientific achievements. wikipedia.org/wiki/Dmitri_Mendeleev";
} else{
  // look up the thing that was typed
  echo "Sorry, I don't know about ".$quote.$text.$quote."."; //$reply;
}
*/

function Response($term="") {

  $result = ""
  $quote = '"';
  $term = strtolower($term);

  if ($term == "" or $term == "?" or $term == "help")
  {
    // Here if the user typed in no argument, or a question mark, or "help". Give a helpful message.
    $result = 'To find out about X, type "/? X"';
  }
  else if ($term == "lars")
  { 
    $result = "*Lars* created me.";
  }
  else if ($term == "mendeleev")
  { 
    $result = "The *Mendeleev* conference room is near Lars's desk.\n\nDmitri Ivanovich *Mendeleev* (1834-1907) created the modern periodic table of elements, among many other scientific achievements. wikipedia.org/wiki/Dmitri_Mendeleev";
  } 
  else 
  {
    // look up the thing that was typed
    $result = "Sorry, I don't know about ".$quote.$term.$quote."."; //$reply;

  }

  return $result;

} // end Response

?>
