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
 File: admin.php
-----------------------------------------------------
 Purpose: Enables the Control panel to be accessed
 from any directory.
 Put this file (and a copy of path.php) in any
 directory and the control panel will be accessible
 from there.  This allows people into the CP without
 knowing the location of your "system" directory
=====================================================
*/

//  DO NOT ALTER THIS FILE IN ANY WAY!!

error_reporting(0);

$pathinfo = pathinfo(__FILE__);

$ext  = ( ! isset($pathinfo['extension'])) ? '.php' : '.'.$pathinfo['extension'];

$self = ( ! isset($pathinfo['basename'])) ? 'index'.$ext : $pathinfo['basename'];
	
unset($system_path);
unset($config_file);

define('MASKED_CP', TRUE);

require 'path'.$ext;

$system_path = rtrim($system_path, '/').'/';

require $system_path.'core/core.system'.$ext;

?>