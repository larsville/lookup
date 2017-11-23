<?php

// from https://mixpanel.com/help/reference/php:
//
// import dependencies (using composer's autoload)
// if not using Composer, you'll want to require the
// lib/Mixpanel.php file here
require "vendor/autoload.php";

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

function Response($input_raw)
{
  // Instantiate a Mixpanel object using a token
  // stashed in a (Heroku) environment variable,
  // and track our activity with it.

  $result = "";
  $input = strtolower($input_raw);

  $RequiredMixpanelToken = getenv('MIXPANEL_TOKEN');
  $mp = Mixpanel::getInstance($RequiredMixpanelToken);

  if ($input == "?" or $input == "" or $input == "help")
  {
    // Here if the user seems to need help. We could simply make
    // this part of the data file. But that could potentially return
    // multiple unrelated results as well. Probably better to check
    // for the existence of msg-help, and proceed appropriately.
    $result = ConfigValue("msg-help");
    $mp->track("helped  <".$input_raw.">");
  }
  else
  {
    // Get the definition for our term.
    $result = Lookup($input);

    if ($result == "")
    {
      // If there is no definition, admit defeat and put up a help message.
      $result = str_ireplace("{input}", $input_raw, ConfigValue("msg-unlisted-term"));
      $mp->track("FAILED <".$input_raw.">");
    }
    else
    {
      $mp->track("found <".$input_raw.">");
    }
  }

  return trim($result);

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

					//break;	// uncomment this to limit the result to only one item
				}
			}
		}
	}

	return trim($Result);

} // end Lookup

?>
