<?php

/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2003 EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/docs/license.html
=====================================================
 File: pi.word_limit.php
-----------------------------------------------------
 Purpose: Character limiting plugin
=====================================================

*/


$plugin_info = array(
						'pi_name'			=> 'Character Limiter',
						'pi_version'		=> '1.0',
						'pi_author'			=> 'Rick Ellis',
						'pi_author_url'		=> 'http://expressionengine.com/',
						'pi_description'	=> 'Permits you to limit the number of characters in some text',
						'pi_usage'			=> Char_limit::usage()
					);


class Char_limit {

    var $return_data;

    
    /** ----------------------------------------
    /**  Character Limiter
    /** ----------------------------------------*/

    function Char_limit($str = '')
    {
        global $TMPL, $FNS;
                        
		$total = ( ! $TMPL->fetch_param('total')) ? '500' :  $TMPL->fetch_param('total');
		
		if ( ! is_numeric($total))
			$total = 500;
        
        if ($str == '')
        	$str = $TMPL->tagdata;
                
 		$this->return_data = $FNS->char_limiter($str, $total);
    }
    /* END */
    
// ----------------------------------------
//  Plugin Usage
// ----------------------------------------

// This function describes how the plugin is used.
//  Make sure and use output buffering

function usage()
{
ob_start(); 
?>
Wrap anything you want to be processed between the tag pairs.

{exp:char_limit total="100"}

text you want processed

{/exp:char_limit}

The "total" parameter lets you specify the number of characters.

Note: This tag will always leave entire words intact so you may get a few additional characters than what you specify.  

<?php
$buffer = ob_get_contents();
	
ob_end_clean(); 

return $buffer;
}
/* END */


}
// END CLASS
?>