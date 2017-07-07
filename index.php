<?php

// Extract the important values from the slash command.

$command = $_POST['command'];
$text = $_POST['text'];
$token = $_POST['token'];

// Make sure we have the proper token.

if ($token != 'yGmqYtpolYQE7j2x9E3vx3YQ') // token from slash command config page
{
  $msg = "Authorization failure.";
  die($msg);
  echo $msg;
}

$DataLines = file("data.txt");
sort($DataLines);

echo "\n".Response($text);

/////////////////

function Response($input)
{
  $result = "";
  $input = strtolower($input);

  if ($input == "?" or $input == "" or $input == "help")
  {
    // Here if the user seems to need help.
    $result = 'Do you want to know who, what, or where a thing is? Just type: *_/? [thing]_*'
      ."\nThe thing can be a staff member, acronym, business term, or conference room.";

  }
  else
  {
    // Get the definition for our term.
    $result = Lookup($input);
    echo "\n-> Response: input=".$input;

    if ($result == "")
    {
      // If there is no definition, admit defeat.
      $quote = '"';
      $result = "Sorry, I don't know about ".$quote.$input.$quote.". Try a staff member, acronym, business term, or conference room.";
    }
  }

  return $result;

} // end Response

/////////////////

function Lookup($term) {

  $result = "";
  $term = strtolower($term).chr(9); // append tab char
  echo "\n-> Lookup: term=".$term;

  global $DataLines;
  foreach($DataLines as $line)
  {
    echo "\n-> Lookup: line=".$line;
    if (stripos($line, $term) == 0)
    {
      $result = $line;
      break; // take the first match we find
    }
  }

  echo "\n-> Lookup: result=".$result;
  return $result;

} // end Lookup

?>
