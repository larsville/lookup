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

echo Response($text);

/////////////////

function Response($input = "") {

  $result = "";
  $input = strtolower($input);

  if ($input == "?" or $input == "" or $input == "help") {
    // Here if the user seems to need help.
    $environment = "";
    $result = 'Do you want to know who, what, or where a thing is? Just type: *_/? [thing]_*'
      ."\nThe thing can be a staff member, acronym, business term, or conference room.";

  } else if ($input == "lars") { 
    // Return the string corresponding to the result.
    $result = "*Lars* is my creator.";

  } else if ($input == "mendeleev") { 
    // Return the string corresponding to the result.
    $result = "The *Mendeleev* conference room is near Lars's desk.\n\nDmitri Ivanovich *Mendeleev* (1834-1907) created the modern periodic table of elements, among many other scientific achievements. wikipedia.org/wiki/Dmitri_Mendeleev";

  } else {
    // Admit defeat.
    $quote = '"';
    $result = "Sorry, I don't know about ".$quote.$input.$quote."."; //$reply;
  
  }

  return $result;

} // end Response

?>
