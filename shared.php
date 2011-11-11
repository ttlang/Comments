<?php

//Database and service parameters
require_once("config.php");

mysql_connect($db_host, $db_username, $db_password);
mysql_select_db($db_database) or die(mysql_error());
mysql_query("SET NAMES 'utf8'") or die(mysql_error());

//Page URL checks and normalization
function GetSiteConstants()
{
	//SiteID
	if(isset($_GET['sid']) === FALSE)
	{
		define("urlError", 'Missing ?sid=...');
		return;
	}
	$sid = intval($_GET['sid']);

	//Page URL
	if(isset($_SERVER['HTTP_REFERER']))
		$url = $_SERVER['HTTP_REFERER'];
	else
	{
		define("urlError", 'Missing referer');
		return;
	}
	if(isset($_GET['url']))
		$url = $_GET['url'];
	if(filter_var($url, FILTER_VALIDATE_URL) === FALSE)
	{
		define("urlError", 'Invalid url: '.htmlentities($url));
		return;
	}

	//Verify pageUrl and referer
	$res = @mysql_query('SELECT * FROM Sites WHERE SiteID='.$sid)
		or die(mysql_error());
	$row = mysql_fetch_assoc($res);
	if($row === FALSE)
	{
		define("urlError", 'No site with sid: '.$sid);
		return;
	}
	$siteUrl = parse_url(rtrim($row['SiteUrl'], "/"));
	$pageUrl = parse_url(rtrim($url, "/"));
	if($pageUrl['host'] != $siteUrl['host'] || strpos($pageUrl['path'], $siteUrl['path']) !== 0)
	{
		define("urlError", 'Wrong url of page: '.htmlentities($url).' expected: '.htmlentities($row['SiteUrl']));
		return;
	}
	$refUrl = parse_url($_SERVER['HTTP_REFERER']);
	if($refUrl['host'] != $siteUrl['host'] || strpos($refUrl['path'], $siteUrl['path']) !== 0)
	{
		define("urlError", 'Wrong referer: '.htmlentities($_SERVER['HTTP_REFERER']).' expected: '.htmlentities($row['SiteUrl']));
		return;
	}
	define("urlError", FALSE);
	define("siteID", $sid);
	define("pagePath", substr($pageUrl['path'], strlen($siteUrl['path'])));
	define("siteAdminEmail", $row['AdminEmail']);
	define("siteUrl", $row['SiteUrl']);
}



function GenerateAndSendVerificationCode($email, $url)
{
	$code = substr(sha1(time().rand().$email.$_SERVER['REMOTE_ADDR']), 0, 10);
	@mysql_query('REPLACE Authors SET Email=\''.mysql_real_escape_String($email).'\', VerifyDate=NOW(), VerifyCode=\''.mysql_real_escape_String($code).'\'')
	 or die('<div class="commentError">'.mysql_error().'</div>');

	//Email verification link
	$mailed = mail($email,
		'Verify your comment',
		'To verify the comment you made on '.$url.'

Click here to login and review your comments:
'.service_url.'/authorize/?email='.urlencode($email).'&code='.$code,
		'From: '.service_email)
	or die('<div class="commentError">Failed to send verification email, try again</div>');
}

//Return the email address verified by session cookie
function GetSessionConstants()
{
	$email = isset($_COOKIE['email'])? $_COOKIE['email'] : null;
	$session = isset($_COOKIE['session'])? $_COOKIE['session'] : null;
	$res = @mysql_query('SELECT * FROM Authors
		WHERE Email=\''.mysql_real_escape_string($email).'\'
		AND Session=\''.mysql_real_escape_string($session).'\'')
		or die('<div class="commentError">'.mysql_error().'</div>');
	if(mysql_num_rows($res) !== 1)
	{
		setcookie("session", null, time()-24*3600);
		define('sessionEmail', null);
		return NULL;
	}
	define('sessionEmail', $email);
	return $email;
}

