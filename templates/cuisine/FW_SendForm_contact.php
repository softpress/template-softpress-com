<?php

global $errorStrings;
global $errors;

// CONFIG
// Redirect pages
$successPage = "success.html"; // Relative path for page to redirect to on success
$errorPage = "error.html"; // Relative path for page to redirect to on error, error numbers will be in a GET variable

// E-Mail
$recipient = "your@email.com"; // Address to deliver form to
$subject = ""; // Subject of the E-Mail
$from = ""; // From address if server requires it or if E-Mail address is optional
$name = ""; // The name of the sender, if required

$useRecipientList = "0"; // Should the recipient be matched against options?
$recipientList = array(

);

// Server
$allowsOtherDomains = TRUE;

// Variables
$input_vars = array(
	'Name1' => array(
		'title' => 'Name1',
		'required' => '1'
	),
	'Email' => array(
		'title' => 'Email',
		'required' => '1'
	),
	'Telephone' => array(
		'title' => 'Telephone',
		'required' => '1'
	),
	'Subject' => array(
		'title' => 'Subject',
		'required' => '1'
	),
	'item1' => array(
		'title' => 'item1',
		'required' => '0'
	)
);

// Error strings
$errorStrings = array(
	0 => 'Undefined error',
	1 => 'No form submitted',
	2 => 'Invalid E-Mail address',
	3 => 'E-Mail could not be delivered',
	4 => 'sendForm18231', // No real error message for this
);

// FUNCTIONS
// void appendError(int $errorNum [, string $errorString])
// Append error for processing at the end
function appendError($errorNum, $errorString = NULL)
{
	global $errorStrings;
	global $errors;
	global $customErrorNum;
	if (!$errors)
		$errors = array();
	if (!$customErrorNum)
		$customErrorNum = 0;
	
	if ($errorNum > 0 && array_key_exists($errorNum, $errorStrings))
		$message = $errorStrings[$errorNum];
	elseif ($errorString)
		$message = $errorString;
	else
		$message = $errorStrings[0];
	
	if ($errorNum == 0)
	{
		$errors["c$customErrorNum"] = $message;
		$customErrorNum++;
	}
	else
	{
		$errors[$errorNum] = $message;
	}
}

// PROCESSING
// Input
// Determine if a form has been submitted and whether it was via POST or GET
$input_type = INPUT_POST;
if ($_SERVER['REQUEST_METHOD'] === 'POST')
	$input_type = INPUT_POST;
elseif ($_SERVER['REQUEST_METHOD'] === 'GET')
	$input_type = INPUT_GET;
else
	appendError(1);

// Are we in safe mode?
$safeMode = ini_get('safe_mode');
// safe_mode can be 'On' or true
$safeMode = ($safeMode == 'On' || $safeMode == true);

$recipientId = 0;

// Before we go anywhere, was the form submitted by a human?
if (($input_type == INPUT_POST && !empty($_POST['sendForm18231']) ||
	($input_type == INPUT_GET && !empty($_GET['sendForm18231']))))
{
	// Probably not
	appendError(4);
}

if (!$errors)
{
	$firstName = false;
	$surname = false;
	$parameters = "";
	
	// A form has been submitted, iterate over the expected fields to
	// produce a message body
	$emailBody = '';
	foreach($input_vars as $key => $var)
	{
		$field = NULL;
		if (filter_has_var($input_type, $key) && ($input_type == INPUT_POST ? !empty($_POST[$key]) : !empty($_GET[$key])))
		{
			// If the field exists and isn't empty, sanitize the contents for security
			if (array_key_exists('filter', $var))
			{
				switch ($var['filter'])
				{
					case 'email':
						$sanitized = filter_input($input_type, $key, FILTER_SANITIZE_EMAIL);
						if (filter_input($input_type, $key, FILTER_VALIDATE_EMAIL))
						{
							$field = $sanitized;
							if ($var['type'] == 'recipient')
							{
								$recipient = $field;
								$field = '';
							}
							elseif ($var['type'] == 'from' && $allowsOtherDomains)
								$from = $field;
						}
						else
							appendError(2);
						break;
					case 'integer':
						$sanitized = filter_input($input_type, $key, FILTER_SANITIZE_NUMBER_INT);
						if (!empty($sanitized))
						{
							if ($var['type'] == 'recipient')
									$recipientId = $sanitized;
							else
								$field = $sanitized;
						}
						break;
					default:
						$field = filter_input($input_type, $key, FILTER_SANITIZE_MAGIC_QUOTES);
				}
			}
			else
			{
				$group = filter_input(INPUT_POST, $key, FILTER_SANITIZE_MAGIC_QUOTES, FILTER_REQUIRE_ARRAY);
				if(is_Array($group))
				{
					for($i = 0; $i < count($group); $i++)
					{
						$field .= "$group[$i]";
						if($group[$i+1])
							$field .= ", ";
					}
					if(count($group) > 1)
						$field = "[$field]";
				}
				else
					$field = filter_input($input_type, "$key", FILTER_SANITIZE_MAGIC_QUOTES);
			}
			
			if ($field && array_key_exists('type', $var))
			{
				if ($var['type'] == 'firstName') 
				{
					$firstName = $field;
				}
				elseif ($var['type'] == 'surname')
				{
					$surname = $field;
				}
				elseif ($var['type'] == 'subject')
				{
					$subject = $field;
					continue;
				}
			}
		}
		elseif (array_key_exists('required', $var) && $var['required'])
		{
			// The field doesn't exist or is empty but is required
			appendError(0, "$key is a required field");
		}
		
		if ($field)
		{
			// Add the field to the message body
			$emailBody .= $var['title'] . ": $field\n";
		}
	}
}

// Sending
if (!$errors)
{
	// If we haven't had any errors up to this point, try to send the E-Mail
	if ($firstName || $surname)
	{
		if ($firstName && $surname)
			$name = $firstName . " " . $surname;
		elseif ($firstName)
			$name = $firstName;
		else
			$name = $surname;
	}
	
	if ($useRecipientList && isset($recipientList))
		if (count($recipientList) > $recipientId && $recipientId >= 0)
			$recipient = $recipientList[$recipientId];
	
	$headers = 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/plain; charset=UTF-8' . "\r\n";
	if ($name)
		$fromHeader = "From: \"$name\" <$from>\r\n";
	else
		$fromHeader = "From: $from\r\n";
	
	if (!$allowsOtherDomains)
		$parameters = "-f$from";
	
	if ($safeMode)
		$mailSuccess = mail($recipient, $subject, "$emailBody", $headers . $fromHeader);
	else
		$mailSuccess = mail($recipient, $subject, "$emailBody", $headers . $fromHeader, $parameters);
	
	if (!$mailSuccess) 
	{
		// Attempt to send from an address of the same domain as the server
		if ($name)
			$fromHeader = "From: \"$name\" <no-reply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
		else
			$fromHeader = "From: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n";
			
		if ($safeMode)
			$mailSuccess = mail($recipient, $subject, "$emailBody", $headers . $fromHeader);
		else
			$mailSuccess = mail($recipient, $subject, "$emailBody", $headers . $fromHeader, $parameters);
		
		if(!$mailSuccess)
			appendError(3);
	}
}

// Finishing up
$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

if ((substr($errorPage, 0, 7) != 'http://') && (substr($errorPage, 0, 8) != 'https://'))
{
	if (strpos($errorPage, "/") === 0)
		$errorPage = "http://$host$errorPage";
	else
		$errorPage = "http://$host$uri/$errorPage";
}

if ((substr($successPage, 0, 7) != 'http://') && (substr($successPage, 0, 8) != 'https://'))
{
	if (strpos($successPage, "/") === 0)
		$successPage = "http://$host$successPage";
	else
		$successPage = "http://$host$uri/$successPage";
}

// If we have errors but the spam trap error is present, we pretend that we succeeded
if ($errors && !array_key_exists(4, $errors))
{
	// We encountered errors so the E-Mail must not have been sent
	$errorsUrlString = urlencode(implode(",", $errors));
	header("Location: $errorPage?$errorsUrlString");
}
else
{
	// E-Mail has been successfully accepted for delivery. This doesn't mean it will reach the
	// destination but that is out of our control now so all we can do is hope for the best
	header("Location: $successPage");
}

?>