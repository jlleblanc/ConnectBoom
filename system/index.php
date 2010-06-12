<?php

error_reporting(0);

$pathinfo = pathinfo(__FILE__);
$ext = '.'.$pathinfo['extension'];

require './core/core.system'.$ext;

?>