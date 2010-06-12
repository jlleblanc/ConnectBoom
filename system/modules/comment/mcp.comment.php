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
 File: mcp.comment.php
-----------------------------------------------------
 Purpose: Commenting class - CP
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Comment_CP {

    var $version = '1.2';
    


    /** --------------------------------
    /**  Delete comment notification
    /** --------------------------------*/

    function delete_comment_notification()
    {
        global $IN, $DB, $OUT, $PREFS, $LANG;
        
        if ( ! $id = $IN->GBL('id'))
        {
            return false;
        }
        
        if ( ! is_numeric($id))
        {
        	return false;
        }
        
        $LANG->fetch_language_file('comment');
        
        $query = $DB->query("SELECT entry_id, email FROM exp_comments WHERE comment_id = '".$DB->escape_str($id)."'");
        
        if ($query->num_rows != 1)
        {
        	return false;
        }
        
        if ($query->num_rows == 1)
        { 
			$DB->query("UPDATE exp_comments SET notify = 'n' WHERE entry_id = '".$DB->escape_str($query->row['entry_id'])."' AND email = '".$DB->escape_str($query->row['email'])."'");
		}
                
        $data = array(	'title' 	=> $LANG->line('cmt_notification_removal'),
        				'heading'	=> $LANG->line('thank_you'),
        				'content'	=> $LANG->line('cmt_you_have_been_removed'),
        				'redirect'	=> '',
        				'link'		=> array($PREFS->ini('site_url'), stripslashes($PREFS->ini('site_name')))
        			 );
        
		$OUT->show_message($data);
    }
    /* END */



    /** --------------------------------
    /**  Module installer
    /** --------------------------------*/

    function comment_module_install()
    {
        global $DB;        
        
        $sql[] = "INSERT INTO exp_modules (module_id, module_name, module_version, has_cp_backend) VALUES ('', 'Comment', '$this->version', 'n')";
        $sql[] = "INSERT INTO exp_actions (action_id, class, method) VALUES ('', 'Comment', 'insert_new_comment')";
        $sql[] = "INSERT INTO exp_actions (action_id, class, method) VALUES ('', 'Comment_CP', 'delete_comment_notification')";
    
    
        foreach ($sql as $query)
        {
            $DB->query($query);
        }
        
        return true;
    }
    /* END */
    
    
    /** -------------------------
    /**  Module de-installer
    /** -------------------------*/

    function comment_module_deinstall()
    {
        global $DB;   
        
        $query = $DB->query("SELECT module_id FROM exp_modules WHERE module_name = 'Comment'"); 
                
        $sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row['module_id']."'";
        $sql[] = "DELETE FROM exp_modules WHERE module_name = 'Comment'";
        $sql[] = "DELETE FROM exp_actions WHERE class = 'Comment'";
        $sql[] = "DELETE FROM exp_actions WHERE class = 'Comment_CP'";
        
    
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