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
    // Here if the user seems to need help.
    $result = ConfigValue("msg-help");
    $mp->track("?????  <".$input_raw.">");//, array("input" => $input_raw));
  }
  else
  {
    // Get the definition for our term.
    $result = Lookup($input);

    if ($result == "")
    {
      // If there is no definition, admit defeat and put up a help message.
      $result = str_ireplace("{input}", $input_raw, ConfigValue("msg-unlisted-term"));
      $mp->track("XXXXX <".$input_raw.">");//, array("input" => $input_raw));
    }
    else
    {
      $mp->track("+++++ <".$input_raw.">");//, array("input" => $input_raw));
    }
  }

  return trim($result);

} // end Response

/////////////////

// Return whatever our dataset contains for the given term.

function Lookup($Term)
{
  $result = "";

  static $DataLines;
  if ($DataLines == NULL)
  {
    $filename = ConfigValue("data-file-name");
    if (strlen($filename) > 0)
    {
    	$DataLines = file($filename);
    	//sort($DataLines);
    }

    $filename2 = ConfigValue("data-file-name2");
    if (strlen($filename2) > 0)
    {
    	$DataLines2 = file($filename2);
    	//sort($DataLines2);
  		foreach($DataLines2 as $line)
  		{
  			array_push($DataLines, $line);
  		}
    }

    $filename3 = ConfigValue("data-file-name3");
    if (strlen($filename3) > 0)
    {
    	$DataLines3 = file($filename3);
    	//sort($DataLines3);
  		foreach($DataLines3 as $line)
  		{
  			array_push($DataLines, $line);
  		}
    }

    $filename4 = ConfigValue("data-file-name4");
    if (strlen($filename4) > 0)
    {
    	$DataLines4 = file($filename4);
    	//sort($DataLines4);
  		foreach($DataLines4 as $line)
  		{
  			array_push($DataLines, $line);
  		}
    }

    $filename5 = ConfigValue("data-file-name5");
    if (strlen($filename5) > 0)
    {
    	$DataLines5 = file($filename5);
    	//sort($DataLines5);
  		foreach($DataLines5 as $line)
  		{
  			array_push($DataLines, $line);
  		}
    }

    $filename6 = ConfigValue("data-file-name6");
    if (strlen($filename6) > 0)
    {
    	$DataLines6 = file($filename6);
    	//sort($DataLines6);
  		foreach($DataLines6 as $line)
  		{
  			array_push($DataLines, $line);
  		}
    }
  }

  $Term = trim(strtolower($Term)); // hack; strip spaces and corp name too

  foreach($DataLines as $Line)
  {
  	$Line = trim($Line);
  	$PosFound = stripos($Line, $Term);
	if ($PosFound !== false) // does the line contain the search term?
	{
		$SeparatorPos = stripos($Line, chr(9));
		if (($SeparatorPos !== false) and (strlen($Line) > $SeparatorPos)) // does the line contain a definition?
		{
			// We have a definition. Accumulate it!
			$Found = substr($Line, $SeparatorPos);
			$Found = trim($Found);

			if (strlen($Found) > 0) // ignore empty definitions
			{
				$Found = str_ireplace("\\n", chr(13), $Found); // support escaped line breaks
				$result = $result.chr(13).$Found;

				//break;	// uncomment this to limit the result to only one item
			}
		}
	}
  }

  return trim($result);

} // end Lookup

?>
