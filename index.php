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

// hack; should we sanitize above inputs?

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
  $Result = "";
  static $ConfigValues;
  if ($ConfigValues == NULL)
  {
    $ConfigValues = parse_ini_file("config.ini.php");
  }
  $Result = str_ireplace("\\n", chr(13), $ConfigValues[$Key]); // support escaped line breaks
  return $Result;
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
    $Mp->track("helped  <".$InputRaw.">");
  }
  else
  {
    // Get the definition for our term.
    $Result = Lookup($Input);

    if ($Result == "")
    {
      // If there is no definition, admit defeat and put up a help message.
      $Result = str_ireplace("{input}", $InputRaw, ConfigValue("msg-unlisted-term"));
      $Mp->track("FAILED <".$InputRaw.">");
    }
    else
    {
      $Mp->track("found <".$InputRaw.">");
    }
  }

  return trim($Result);

} // end Response

/////////////////

// Return whatever our dataset contains for the given term.

function Lookup($Term)
{
  $Result = "";
  $TermNormalized = trim(strtolower($Term)); // hack; strip spaces and corp name too

  $MatchesAsName = array();
  $MatchesInName = array();
  $MatchesInDefinition = array();

  $DataLines = array();
  if ($DataLines == NULL)
  {
    $DataFiles = ConfigValue("data-files");
    foreach ($DataFiles as $DataFile)
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
      $PosFound = stripos($Line, $TermNormalized); // is the term within the line at all?
      if ($PosFound !== false) // if so...
      {
        // We have a match. Get the corresponding definition.

        $DefinitionFound = substr($Line, $SeparatorPos);
        $DefinitionFound = str_ireplace("\\n", chr(13), $DefinitionFound); // support escaped line breaks
        $DefinitionFound = trim($DefinitionFound);

        if (strlen($DefinitionFound) > 0) // ignore empty definitions
        {
          if ($PosFound < $SeparatorPos) // did we match within the name?
          {
            // If the term is in the name but not in the definition, then insert the
            // name as a prefix, so the user won't wonder why that definition is found.
            
            if (stripos($DefinitionFound, $TermNormalized) === false)
            {
              $DefinitionFound = substr($Line, 0, $SeparatorPos).": ".$DefinitionFound;
            }
            
            // hack; need to split by pipes and spaces (with parens stripped)
            if (strlen($TermNormalized) >= $SeparatorPos-3) // did we match the name closely?
            {
              // hack; need to consider absolute length of word as well
              array_push($MatchesAsName, $DefinitionFound);
            }
            else // we matched in the name, but not closely
            {
              array_push($MatchesInName, $DefinitionFound);
            }
          }
		  else // we didn't match in the name, so it must be in the definition.
		  {
		    array_push($MatchesInDefinition, $DefinitionFound);
		  }

        }
      }
    }
  }

  if (count($MatchesAsName) > 0)
    $Result = $Result.Formatted($MatchesAsName);
    
  elseif (count($MatchesInName) > 0)
    $Result = $Result.Formatted($MatchesInName);
    
  elseif (count($MatchesInDefinition) > 0)
    $Result = $Result.Formatted($MatchesInDefinition);

  return trim($Result);

} // end Lookup

/////////////////

// Return the given array of lines, formatted appropriately for output.

function Formatted($Lines)
{
  $Result = "";
  
  foreach($Lines as $Line)
  {
    if (count($Lines) > 1)
    {
      $Result = $Result.chr(13)."- ".$Line;
    }
    else
    {
      $Result = $Result.chr(13).$Line;
    }
  }

  return chr(13).trim($Result);

} // end Formatted

?>
