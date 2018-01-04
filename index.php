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

  $Org = ConfigValue("org");

  if ($Input == "?" or $Input == "" or $Input == "help")
  {
    // Here if the user seems to need help. We could simply make
    // this part of the data file. But that could potentially return
    // multiple unrelated results as well. Probably better to check
    // for the existence of msg-help, and proceed appropriately.
    $Result = ConfigValue("msg-help");
    $Mp->track($Org.": helped  <".$InputRaw.">");
  }
  else
  {
    // Get the definition for our term.
    $Result = Lookup($Input);

    if ($Result == "")
    {
      // If there is no definition, admit defeat and put up a help message.
      $Result = str_ireplace("{input}", $InputRaw, ConfigValue("msg-unlisted-term"));
      $Mp->track($Org.": FAILED <".$InputRaw.">");
    }
    else
    {
      // Track our count of results, so analytics can reveal if things blow up.
      $Mp->track($Org.": found <".$InputRaw."> (".(count(explode(chr(13), $Result))-2).")");
    }
  }

  return trim($Result);

} // end Response

/////////////////

// Return whatever our dataset contains for the given input.

function Lookup($Input)
{
  $Result = "";
  $InputTrimmed = trim($Input); // hack; strip spaces and corp name too
  $InputNormalized = strtolower($InputTrimmed);

  $MatchesAsName = array();
  $MatchesInName = array();
  $MatchesInDefinition = array();
  $MatchesByWordInName = array();
  $MatchesByWordInDefinition = array();

  $DataLines = array();
  if ($DataLines == NULL)
  {
    $DataFiles = ConfigValue("data-files");
    foreach ($DataFiles as $DataFile)
    {
      $Lines = file($DataFile);
      foreach ($Lines as $Line)
      {
        array_push($DataLines, $Line);
      }
    }
  }

  foreach ($DataLines as $Line)
  {
    $Line = trim($Line);
    $SeparatorPos = stripos($Line, chr(9));
    if (($SeparatorPos !== false) and (strlen($Line) > $SeparatorPos)) // ignore lines w/no definition
    {
      $PosFound = stripos($Line, $InputNormalized); // is the input string within the line at all?
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
            // If the input string is in the name but not in the definition, then insert
            // the input string as a prefix, so the user won't wonder why that definition
            // was returned.
            
            if (stripos($DefinitionFound, $InputNormalized) === false)
            {
              $DefinitionFound = substr($Line, 0, $SeparatorPos).": ".$DefinitionFound;
            }
            
            // hack; need to split by pipes and spaces (with parens stripped)
            if (strlen($InputNormalized) >= $SeparatorPos-3) // did we match the name closely?
            {
              // hack; need to consider absolute length of word as well
              array_push($MatchesAsName, $DefinitionFound);
            }
            else // we matched in the name, but not closely
            {
              array_push($MatchesInName, $DefinitionFound);
            }
          }
		  else // we didn't match in the name, so it must be in the defix``nition.
		  {
		    array_push($MatchesInDefinition, $DefinitionFound);
		  }

        }
      }
      else // here if the term does not occur verbatim in the line
      {
      	// If the term has more than one word, don't give up yet!
      	// Count the number of target words that occur in the name
      	// or in the definition.
      	
      	$InputWords = str_word_count($InputNormalized, 1);
      	$InputWordCount = count($InputWords);
      	if ($InputWordCount > 1)
      	{
      	  $InputWordsFoundInNameCount = 0; // how many words have we found in the name?
      	  $InputWordsFoundInDefinitionCount = 0; // how many words have we found in the definition?

      	  foreach ($InputWords as $InputWord)
      	  {
      		$PosFound = stripos($Line, $InputWord); // is the word within the line at all?
      		if ($PosFound !== false) // if so...
      		{
			  if ($PosFound < $SeparatorPos) // did we find the word within the name?
			  {
				$InputWordsFoundInNameCount = $InputWordsFoundInNameCount + 1;
			  }
			  else
			  {
				$InputWordsFoundInDefinitionCount = $InputWordsFoundInDefinitionCount + 1;
			  }
			}
		  }

		  // If either word count exceeds our threshold (currently zero),
		  // accumulate the definition into the appropriate array.

		  if ($InputWordsFoundInNameCount === $InputWordCount)
		  {
			// We have words in the name. Get the corresponding definition.

			$DefinitionFound = substr($Line, $SeparatorPos);
			$DefinitionFound = str_ireplace("\\n", chr(13), $DefinitionFound); // support escaped line breaks
			$DefinitionFound = trim($DefinitionFound);

       		// If not all the input words are in the definition, then insert the
      		// name as a prefix, so the user won't wonder why that definition was returned.
            
        	if ($InputWordsFoundInDefinitionCount < $InputWordCount)
     		{
			  $DefinitionFound = substr($Line, 0, $SeparatorPos).": ".$DefinitionFound;
        	}

			if (strlen($DefinitionFound) > 0) // ignore empty definitions
			{
			  array_push($MatchesByWordInName, $DefinitionFound);
			}
		  }
		  elseif ($InputWordsFoundInDefinitionCount === $InputWordCount)
		  {
			// We have words in the definition. Get the corresponding definition.

			$DefinitionFound = substr($Line, $SeparatorPos);
			$DefinitionFound = str_ireplace("\\n", chr(13), $DefinitionFound); // support escaped line breaks
			$DefinitionFound = trim($DefinitionFound);

			if (strlen($DefinitionFound) > 0) // ignore empty definitions
			{
			  array_push($MatchesByWordInDefinition, $DefinitionFound);
			}
		  }

      	}
      }
    }
  }

  if (count($MatchesAsName) > 0)
    $Result = $Result.Formatted($MatchesAsName, $InputTrimmed);
    
  elseif (count($MatchesInName) > 0)
    $Result = $Result.Formatted($MatchesInName, $InputTrimmed);
    
  elseif (count($MatchesByWordInName) > 0)
    $Result = $Result.Formatted($MatchesByWordInName, $InputTrimmed);
    
  elseif (count($MatchesInDefinition) > 0)
    $Result = $Result.Formatted($MatchesInDefinition, $InputTrimmed);

  elseif (count($MatchesByWordInDefinition) > 0)
    $Result = $Result.Formatted($MatchesByWordInDefinition, $InputTrimmed);
    
  return trim($Result);

} // end Lookup

/////////////////

// Return the given array of lines, formatted appropriately for output.
// The original input can be included as well, if provided.

function Formatted($Lines, $Input = NULL)
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

  // If there are multiple return lines, then repeat the input. It seems to help.

  if ((count($Lines) > 1) && ($Input !== NULL))
  {
    $Result = count($Lines).' results for "'.$Input.'":'.chr(13).$Result;
  }

  // Hack; note that in the case of mulitple results, there are two extra lines,
  // and our Response function relies on this when calculating result counts.
  
  return chr(13).trim($Result);

} // end Formatted

?>
