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
 File: pi.text.php
-----------------------------------------------------
 Purpose: This class contains a number of useful text
 manipulation functions
=====================================================




*/



$plugin_info = array(
						'pi_name'			=> 'XML Encode',
						'pi_version'		=> '1.2',
						'pi_author'			=> 'Rick Ellis',
						'pi_author_url'		=> 'http://expressionengine.com/',
						'pi_description'	=> 'XML Encoding plugin.',
						'pi_usage'			=> Xml_encode::usage()
					);



class Xml_encode {

    var $return_data;    
    
    /** ----------------------------------------
    /**  XML Encoding function
    /** ----------------------------------------*/

    function Xml_encode($str = '')
    {
        global $TMPL, $REGX;
        
		$protect_all = ($TMPL->fetch_param('protect_entities') === 'yes') ? TRUE : FALSE;
		
        $this->return_data = '';
        
        if ($str == '')
        	$str = $TMPL->tagdata;
                
        $str = $REGX->xml_convert(strip_tags($str), $protect_all);
        $this->return_data = trim(str_replace(array('&#47;', '&nbsp;'), array("/", '&#160;'), $str));
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
This plugin converts reserved XML characters to entities.  It is used in the RSS templates.

To use this plugin, wrap anything you want to be processed by it between these tag pairs:

{exp:xml_encode}

text you want processed

{/exp:xml_encode}

Note: Because quotes are converted into &quot; by this plugin, you cannot use
ExpressionEngine conditionals inside of this plugin tag.

If you have existing entities in the text that you do not wish to be converted, you may use
the parameter protect_entities="yes", e.g.:

{exp:xml_encode}Text &amp; Entities{/exp:xml_encode}

results in: Text &amp;amp; Entities

{exp:xml_encode protect_entities="yes"}Text &amp; Entities{/exp:xml_encode}

results in: Text &amp; Entities
<?php
$buffer = ob_get_contents();
	
ob_end_clean(); 

return $buffer;
}
/* END */
}
// END CLASS
?>