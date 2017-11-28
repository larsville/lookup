<?php

// from https://mixpanel.com/help/reference/php:
//
// import dependencies (using composer's autoload)
// if not using Composer, you'll want to require the
// lib/Mixpanel.php file here
require "vendor/autoload.php";

// Extract the important values from the slash command.

//$Command = $_POST['command'];
$Text = $_POST['text'];
$SlackToken = $_POST['token'];

// Make sure we have the proper token.

$RequiredSlackToken = getenv('SLACK_TOKEN'); // from slash command config page
if ($SlackToken != $RequiredSlackToken)
{
  $Msg = ConfigValue("msg-authorization-failed");
  exit($Msg);
  echo $Msg;
}

echo Response($Text);

/////////////////

function Debug($Scope, $Name, $Value)
{
  echo "\n-->".$Scope.": <".$Name."> = <".$Value.">";
}

/////////////////

function ConfigValue($Key)
{
  static $ConfigValues;
  if ($ConfigValues == NULL)
  {
    $ConfigValues = parse_ini_file("config.ini.php");
  }
  return $ConfigValues[$Key];
}

/////////////////

// Return a response for the given input, including help or failure messages.

function Response($InputRaw)
{
  // Instantiate a Mixpanel object using a token
  // stashed in a (Heroku) environment variable,
  // and track our activity with it.

  $Result = "";
  $Input = strtolower($InputRaw);

  $RequiredMixpanelToken = getenv('MIXPANEL_TOKEN');
  $Mp = Mixpanel::getInstance($RequiredMixpanelToken);

  if ($Input == "?" or $Input == "" or $Input == "help")
  {
    // Here if the user seems to need help. We could simply make
    // this part of the data file. But that could potentially return
    // multiple unrelated results as well. Probably better to check
    // for the existence of msg-help, and proceed appropriately.
    $Result = ConfigValue("msg-help");
    $Mp->track("helped  <".$input_raw.">");
  }
  else
  {
    // Get the definition for our term.
    $Result = Lookup($Input);

    if ($Result == "")
    {
      // If there is no definition, admit defeat and put up a help message.
      $Result = str_ireplace("{input}", $InputRaw, ConfigValue("msg-unlisted-term"));
      $Mp->track("FAILED <".$input_raw.">");
    }
    else
    {
      $Mp->track("found <".$input_raw.">");
    }
  }

  return trim($Result);

} // end Response

/////////////////

// Return whatever our dataset contains for the given term.

function Lookup($Term)
{
  $Result = "";
  $Term = trim(strtolower($Term)); // hack; strip spaces and corp name too

  static $DataLines;
  if ($DataLines == NULL)
  {
    $DataLines[] = ""; // array_push doesn't work without this
    $DataFiles = ConfigValue("data-files");
    foreach ($DataFiles as &$DataFile)
    {
      $InputLines = file($DataFile);
      foreach($InputLines as $InputLine)
      {
        array_push($DataLines, $InputLine);
      }
    }
  }

  foreach($DataLines as $Line)
  {
    $Line = trim($Line);
    $SeparatorPos = stripos($Line, chr(9));
    if (($SeparatorPos !== false) and (strlen($Line) > $SeparatorPos)) // ignore lines w/no definition
    {
      $PosFound = stripos($Line, $Term);
      if ($PosFound !== false and ($PosFound < $SeparatorPos)) // does the item name contain the search term?
      {
        // We have a definition. Accumulate it!
        $Found = substr($Line, $SeparatorPos);
        $Found = trim($Found);

        if (strlen($Found) > 0) // ignore empty definitions
        {
          $Found = str_ireplace("\\n", chr(13), $Found); // support escaped line breaks
          $Result = $Result.chr(13).$Found;

          //break;  // uncomment this to limit the result to only one item
        }
      }
    }
  }

  return trim($Result);

} // end Lookup

?>
