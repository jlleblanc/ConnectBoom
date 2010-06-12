<?php
	global $IN;
	
	$mimes = array(
					'psd'	=>	'application/octet-stream',
					'pdf'	=>	'application/pdf',
					'swf'	=>	'application/x-shockwave-flash',
					'sit'	=>	'application/x-stuffit',
					'tar'	=>	'application/x-tar',
					'tgz'	=>	'application/x-tar',
					'zip'	=>	'application/zip',
					'gzip'	=>	'application/x-gzip',
					'bmp'	=>	'image/bmp',
					'gif'	=>	'image/gif',
					'jpeg'	=>	'image/jpeg',
					'jpg'	=>	'image/jpeg',
					'jpe'	=>	'image/jpeg',
					'png'	=>	'image/png',
					'txt'	=>	'text/plain',
					'html'	=>	'text/html',
					'doc'	=>	'application/msword',
					'docx'	=>	'application/msword',
					'xl'	=>	'application/excel',
					'xls'	=>	'application/excel',
					'flv'	=>	'video/x-flv',
					'mov'	=>	'video/quicktime',
					'qt'	=>	'video/quicktime',
					'mpg'	=>	'video/mpeg',
					'mpeg'	=>	'video/mpeg',
					'mp3'	=>	'audio/mpeg',
					'aiff'	=>	'audio/x-aiff',
					'aif'	=>	'audio/x-aiff',
					'aac'	=>	'audio/aac'
				);
	
	// shakes fist at IE
	if (isset($IN->AGENT) && stristr($IN->AGENT, 'MSIE') !== FALSE)
	{
		$mimes['png'] = 'image/x-png';
	}
?>