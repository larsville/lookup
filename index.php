<?php

// Extract the important values from the slash command.

$command = $_POST['command'];
$text = $_POST['text'];
$token = $_POST['token'];

// Make sure we have the proper token.

$tokenRequired = file_get_contents('token.txt');
$tokenRequired = ConfigValue("auth-token");

if ($token != $tokenRequired) // token from slash command config page
{
  $msg = "Authorization failed.";
  $msg = ConfigValue("msg-auth-failed");
  exit($msg);
  echo $msg;
}

echo Response($text);

/////////////////

function Debug($scope, $name, $value)
{
  //echo "\n-->".$scope.": <".$name."> = <".$value.">";
}

/////////////////

function ConfigValue($key)
{
  static $configValues;
  if ($configValues == NULL)
  {
  	$configValues = parse_ini_file("config.ini.php");
  }
  //print_r($configValues);
  return $configValues[$key];
}

/////////////////

// Return a response for the given input, including help or failure messages.

function Response($input)
{
  $result = "";
  $input = strtolower($input);

  if ($input == "?" or $input == "" or $input == "help")
  {
    // Here if the user seems to need help.
    $result = ConfigValue("msg-help");
    //$result = 'If you want to know about a conference room in San Francisco, type: *_/? room-name_*';

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
      $result = "Sorry, I don't recognize ".$quote.$input.$quote.".";
    }
  }

  return $result;

} // end Response

/////////////////

// Return whatever our dataset contains for the given term.

function Lookup($term)
{
  $result = "";
  $termWithSep = strtolower($term).chr(9); // append tab char
    Debug("Lookup", "termWithSep", $termWithSep);

  static $DataLines;
  if ($DataLines == NULL)
  {
    $DataLines = file("data.txt");
    sort($DataLines);
  }

  foreach($DataLines as $line)
  {
    //if strlen((trim($line)) >= 0)
    {
      Debug("Lookup", "stripos($line, $termWithSep)", stripos($line, $termWithSep));
      if (stripos($line, $termWithSep) === 0) // note strict comparison operator
      {
        $result = substr($line, strlen($termWithSep));
        $result = str_ireplace("\\n", chr(13), $result);
        # $canonicalTerm = substr($line, strlen($term));
        # $result = str_ireplace($term, "*".$canonicalTerm."*", $result); // bold the target
        break; // one match is all we need
        // hack; should accumulate all matches in an array
      }
    }
  }

  Debug("Lookup", "result", $result);
  return $result;

} // end Lookup

?>
