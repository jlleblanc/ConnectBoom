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
 File: core.emoticon.php
-----------------------------------------------------
 Purpose: Emoticon (smiley) class
=====================================================
*/


if ( ! defined('EXT'))
{
    exit('Invalid file request');
}



class Emoticon {

    var $smileys     = FALSE;
    var $return_data = '';


    /** -------------------------------------
    /**  Constructor
    /** -------------------------------------*/

    function Emoticon()
    {
        if (is_file(PATH_MOD.'emoticon/emoticons'.EXT))
        {
            require PATH_MOD.'emoticon/emoticons'.EXT;
            
            if (is_array($smileys))
            {
                $this->smileys = $smileys;
            }
        }
        
        $this->table_layout();
    }
    /* END */

    
    /** -------------------------------------
    /**  Table-based emoticon layout
    /** -------------------------------------*/

    function table_layout()
    {
        global $PREFS, $TMPL;
        
        if ($this->smileys == FALSE)
        {
            return FALSE;
        }
        
        if ($PREFS->ini('enable_emoticons') == 'n')
        {
            return FALSE;
        }
        
        
        $path = $PREFS->ini('emoticon_path', 1);
        
        $columns  = ( ! $TMPL->fetch_param('columns'))  ? '4' : $TMPL->fetch_param('columns');
        
        $tagdata = $TMPL->tagdata; 
        

        /** ---------------------------------------------
        /**  Extract the relevant stuff from the tag
        /** ---------------------------------------------*/
           
        if ( ! preg_match("/<tr(.*?)<td/si", $tagdata, $match))
        {
            $tr = "<tr>\n";
        }
        else
        {
            $tr = '<tr'.$match['1'];
        }

        if ( ! preg_match("/<td(.*?)<".SLASH."tr>/si", $tagdata, $match))
        {
            $td = "<td>";
        }
        else
        {
            $td = '<td'.$match['1'];
        }


        $i = 1;
        
        $dups = array();
        
        foreach ($this->smileys as $key => $val)
        {
            if ($i == 1)
            {
                $this->return_data .= $tr;                
            }
            
            if (in_array($this->smileys[$key]['0'], $dups))
            	continue;
            
            $link = "<a href=\"javascript:void(0);\" onclick=\"add_smiley('".$key."')\"><img src=\"".$path.$this->smileys[$key]['0']."\" width=\"".$this->smileys[$key]['1']."\" height=\"".$this->smileys[$key]['2']."\" alt=\"".$this->smileys[$key]['3']."\" style=\"border:0;\" /></a>";
            
            $dups[] = $this->smileys[$key]['0'];
            
            
            $cell = $td;            
            
            $this->return_data .= str_replace("{smiley}", $link, $cell);

            if ($i == $columns)
            {
                $this->return_data .= "</tr>\n";                
                
                $i = 1;
            }
            else
            {
                $i++;
            }      
        }
        
        $this->return_data = rtrim($this->return_data);
                
        if (substr($this->return_data, -5) != "</tr>")
        {
            $this->return_data .= "</tr>";
        }
    
    }
    /* END */
}
// END CLASS
?>