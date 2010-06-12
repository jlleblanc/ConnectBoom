<?php
/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2003 - 2010 EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/docs/license.html
=====================================================
 File: index.php
-----------------------------------------------------
 Purpose: Triggers the main engine
=====================================================
*/

// URI Type
// This variable allows you to hard-code the URI type.
// For most servers, 0 works fine.
// 0 = auto  
// 1 = path_info  
// 2 = query_string

$qtype = 0; 


// DO NOT EDIT BELOW THIS!!! 

error_reporting(0);

if (isset($_GET['URL'])) 
{ 
	/** ---------------------------------
	/**  URL Redirect for CP and Links in Comments
	/** ---------------------------------*/

	$_GET['URL'] = str_replace(array("\r", "\r\n", "\n", '%3A','%3a','%2F','%2f', '%0D', '%0A', '%09', 'document.cookie'), 
							   array('', '', '', ':', ':', '/', '/', '', '', '', ''), 
							   $_GET['URL']);
	
	if (substr($_GET['URL'], 0, 4) != "http" AND ! stristr($_GET['URL'], '://') AND substr($_GET['URL'], 0, 1) != '/') 
		$_GET['URL'] = "http://".$_GET['URL']; 
		
	$_GET['URL'] = str_replace( array('"', "'", ')', '(', ';', '}', '{', 'script%', 'script&', '&#40', '&#41', '<'), 
								'', 
								strip_tags($_GET['URL']));
	
	$host = ( ! isset($_SERVER['HTTP_HOST'])) ? '' : (substr($_SERVER['HTTP_HOST'],0,4) == 'www.' ? substr($_SERVER['HTTP_HOST'], 4) : $_SERVER['HTTP_HOST']);
	
	if ( ! isset($_SERVER['HTTP_REFERER']) OR ! stristr($_SERVER['HTTP_REFERER'], $host))
	{
		// Possibly not from our site, so we give the user the option
		// Of clicking the link or not
		
		$str = "<html>\n<head>\n<title>Redirect</title>\n</head>\n<body>".
				"<p>To proceed to the URL you have requested, click the link below:</p>".
				"<p><a href='".$_GET['URL']."'>".$_GET['URL']."</a></p>\n

</body>\n</html>";
	}
	else
	{
		$str = "<html>\n<head>\n<title>Redirect</title>\n".
			   '<meta http-equiv="refresh" content="0; URL='.$_GET['URL'].'">'.
			   "\n</head>\n<body>\n</body>\n</html>";
	}
	
	exit($str);
}

$uri  = '';
$pathinfo = pathinfo(__FILE__);
$ext  = ( ! isset($pathinfo['extension'])) ? '.php' : '.'.$pathinfo['extension'];
$self = ( ! isset($pathinfo['basename'])) ? 'index'.$ext : $pathinfo['basename'];

$path_info = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO');
$query_str = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');

switch ($qtype)
{
	case 0 :	$uri = ($path_info != '' AND $path_info != "/".$self) ? $path_info : $query_str;
		break;
	case 1 :	$uri = $path_info; 	
		break;
	case 2 :	$uri = $query_str; 
		break;
}

unset($system_path);
unset($config_file);
unset($path_info);
unset($query_str);
unset($qstr);

require 'path'.$ext;

if ((isset($template_group) AND isset($template)) && $uri != '' && $uri != '/')
{
	$template_group = '';
	$template = '';
}

if ( ! isset($system_path))
{
	if (file_exists('install'.$ext))
	{
		header("location: install".$ext); 
		exit;
	}
	else
	{
        exit("The system does not appear to be installed. Click <a href='install.php'>here</a> to install it.");	
	}
}

$system_path = rtrim($system_path, '/').'/';

if ( ! @include($system_path.'core/core.system'.$ext))
{
	exit("The system path does not appear to be set correctly.  Please open your path.php file and correct the path.");	
}

?>