<?php

// from https://mixpanel.com/help/reference/php:
//
// import dependencies (using composer's autoload)
// if not using Composer, you'll want to require the
// lib/Mixpanel.php file here
require "vendor/autoload.php";

// Instantiate a Mixpanel object using a token
// stashed in a (Heroku) environment variable.

$RequiredMixpanelToken = getenv('MIXPANEL_TOKEN');
$mp = Mixpanel::getInstance($RequiredMixpanelToken);

// Extract the important values from the slash command.

$command = $_POST['command'];
$text = $_POST['text'];
$SlackToken = $_POST['token'];

// Make sure we have the proper token.

$RequiredSlackToken = getenv('SLACK_TOKEN');
if ($SlackToken != $RequiredSlackToken) // token from slash command config page
{
  $msg = ConfigValue("msg-authorization-failed");
  exit($msg);
  echo $msg;
}

$mp->track("response", array("input" => $text));

echo Response($text);

/////////////////

function Debug($scope, $name, $value)
{
  echo "\n-->".$scope.": <".$name."> = <".$value.">";
}

/////////////////

function ConfigValue($key)
{
  static $configValues;
  if ($configValues == NULL)
  {
  	$configValues = parse_ini_file("config.ini.php");
  }
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
  }
  else
  {
    // Get the definition for our term.
    $result = Lookup($input);

    if ($result == "")
    {
      // If there is no definition, admit defeat and put up a help message.
      $result = str_ireplace("{input}", $input, ConfigValue("msg-unrecognized-term"));
    }
  }

  return $result;

} // end Response

/////////////////

// Return whatever our dataset contains for the given term.

function Lookup($term)
{
  $result = "";
  $termWithSeparator = strtolower($term).chr(9); // append tab char

  static $DataLines;
  if ($DataLines == NULL)
  {
    $filename = ConfigValue("data-file-name");
    $DataLines = file($filename);
    sort($DataLines);
  }

  foreach($DataLines as $line)
  {
    //if strlen((trim($line)) >= 0)
    {
      if (stripos($line, $termWithSeparator) === 0) // note strict comparison operator
      {
        $result = substr($line, strlen($termWithSeparator));
        $result = str_ireplace("\\n", chr(13), $result);
        break; // one match is all we need
        // hack; should accumulate all matches in an array
      }
    }
  }

  return $result;

} // end Lookup

?>
