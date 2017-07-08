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

echo "---\n".Response($text);

/////////////////

function Debug($scope, $name, $value)
{
  //echo "\n-->".$scope.": <".$name."> = <".$value.">";
}

/////////////////

function Response($input)
{
  $result = "";
  $input = strtolower($input);

  if ($input == "?" or $input == "" or $input == "help")
  {
    // Here if the user seems to need help.
    $result = 'If you want to know who, what, or where a thing is, just type: *_/? [thing]_*'
      ."\nTry it with acronyms, business terms, code names, staff members, even conference rooms.";

  }
  else
  {
    // Get the definition for our term.
    $result = Lookup($input);
    Debug("Response", "input", $input);

    if ($result == "")
    {
      // If there is no definition, admit defeat.
      $quote = '"';
      $result = "Sorry, I don't know about ".$quote.$input.$quote.". Try an acronym, business term, code name, staff member, or conference room.";
    }
  }

  return $result;

} // end Response

/////////////////

function Lookup($term) {

  $result = "";
  $termWithSep = strtolower($term).chr(9); // append tab char
    Debug("Lookup", "termWithSep", $termWithSep);

  global $DataLines;
  foreach($DataLines as $line)
  {
    Debug("Lookup", "stripos($line, $termWithSep)", stripos($line, $termWithSep));
    if (stripos($line, $termWithSep) === 0) // note strict comparison operator
    {
      $result = substr($line, strlen($termWithSep));
      $result = str_ireplace("\\n", chr(13), $result);
      $canonicalTerm = substr($line, strlen($term));
      $result = str_ireplace($term, "*".$canonicalTerm."*", $result); // bold the target
      break; // one match is all we need
    }
  }

  Debug("Lookup", "result", $result);
  return $result;

} // end Lookup

?>
