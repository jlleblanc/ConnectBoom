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
 File: mcp.emoticon.php
-----------------------------------------------------
 Purpose: Emoticon class - CP
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Emoticon_CP {

    var $version = '1.0';


    /** -------------------------
    /**  Constructor
    /** -------------------------*/
    
    function Emoticon_CP( $switch = TRUE )
    {
        global $IN, $DB;
                
		/** -------------------------------
		/**  Is the module installed?
		/** -------------------------------*/
        
        $query = $DB->query("SELECT COUNT(*) AS count FROM exp_modules WHERE module_name = 'Emoticon'");
        
        if ($query->row['count'] == 0)
        {
        	return;
        }

		/** -------------------------------
		/**  Onward!
		/** -------------------------------*/
                
        if ($switch)
        {
            switch($IN->GBL('M'))
            {
                default :   $this->show_simileys();
                    break;
            }
        }
    }
    /* END */
    

    /** -------------------------
    /**  Show installed smileys
    /** -------------------------*/
    
    // This function is in progress
    
    function show_simileys($message = '')
    {
        global $DSP, $LANG, $PREFS;
        
        $path = $PREFS->ini('emoticon_path', 1);
        
        $title = $LANG->line('emoticon_heading');
        
        $r = $DSP->heading($title);
        
        $r .= $message;
                
        $r .= $DSP->table('tableBorder', '0', '', '100%').
              $DSP->tr().
              $DSP->table_qcell('tableHeading', 
                                array(  NBS,
                                        $LANG->line('emoticon_glyph'),
                                        $LANG->line('emoticon_image'),
                                        $LANG->line('emoticon_width'),
                                        $LANG->line('emoticon_height'),
                                        $LANG->line('emoticon_alt')
                                     )
                                ).
              $DSP->tr_c();
        
        
        require PATH_MOD.'emoticon/emoticons'.EXT;
        
        $i = 0;
        
        foreach ($smileys as $key => $val)
        {
                
            $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
            
            $r .= $DSP->tr();
            
            $img = "<img src=\"".$path.$val['0']."\" width=\"".$val['1']."\" height=\"".$val['2']."\" alt=\"".$val['3']."\" border=\"0\" />";
            
            $r .= $DSP->table_qcell($style, $img);
            $r .= $DSP->table_qcell($style, $key);
            $r .= $DSP->table_qcell($style, $val['0']);
            $r .= $DSP->table_qcell($style, $val['1']);
            $r .= $DSP->table_qcell($style, $val['2']);
            $r .= $DSP->table_qcell($style, $val['3']);
        
            $r .= $DSP->tr_c();
        }
        
        $r .= $DSP->table_c();

        $DSP->body = $r;
    }
    /* END */
    



    /** ----------------------------------------
    /**  Module installer
    /** ----------------------------------------*/

    function emoticon_module_install()
    {
        global $DB;        
        
        $sql[] = "INSERT INTO exp_modules (module_id, module_name, module_version, has_cp_backend) VALUES ('', 'Emoticon', '$this->version', 'n')";
    
        foreach ($sql as $query)
        {
            $DB->query($query);
        }
        
        return true;
    }
    /* END */
    
    
    /** ----------------------------------------
    /**  Module de-installer
    /** ----------------------------------------*/

    function emoticon_module_deinstall()
    {
        global $DB;    

        $query = $DB->query("SELECT module_id FROM exp_modules WHERE module_name = 'Emoticon'"); 
                
        $sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row['module_id']."'";        
        $sql[] = "DELETE FROM exp_modules WHERE module_name = 'Emoticon'";
        $sql[] = "DELETE FROM exp_actions WHERE class = 'Emoticon'";
        $sql[] = "DELETE FROM exp_actions WHERE class = 'Emoticon_CP'";
        
        foreach ($sql as $query)
        {
            $DB->query($query);
        }

        return true;
    }
    /* END */



}
// END CLASS
?>