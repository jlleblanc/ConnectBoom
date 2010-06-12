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
 File: actions.php
-----------------------------------------------------
 Purpose: Action handler class
=====================================================

Actions are events that require processing.

Normally when you use ExpressionEngine, either a web page (template), or the control panel is displayed.

There are times, however, when we need to process user-submitted data. Examples of these include:

- Logging in
- Logging out
- Receiving trackbacks
- New member registration
  etc...

In these examples, information submitted from a user needs to be received and processed.  Since
ExpressionEngine uses only one execution file (index.php) we need a way to know that an
action is being requested.

The way actions work is this: 

Anytime a GET or POST request contains the ACT variable, ExpressionEngine will run the Actions class and 
process the requested action.

Note: The database contains a table called "exp_actions".  This table contains a list
of every available action (and the associated class and method).  When an action is requested,
the database is queried to get the information needed to process the action.

When a new module is installed, ExpressionEngine will update the action table.  When a module
is de-installed, the actions are deleted.

*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Action {


    function Action()
    {
        global $IN, $OUT, $PREFS, $LANG, $DB;
        
        /** -----------------------------------------
        /**  Define special actions
        /** -----------------------------------------*/
        
        // These are actions that are triggered manually
        // rather than doing a lookup in the actions table.
            
        $specials = array(
                            'trackback' 		=> array('Trackback_CP', 'receive_trackback')
                         );
        
        
        /** -----------------------------------------
        /**  Make sure the ACT variable is set
        /** -----------------------------------------*/
        
        if ( ! $action_id = $IN->GBL('ACT', 'GP'))
        {
            return false;
        }
    
        /** -----------------------------------------
        /**  Fetch the class and method name 
        /** -----------------------------------------*/
    
        // If the ID is numeric we need to do an SQL lookup
    
        if (is_numeric($action_id))
        {                    
            $query = $DB->query("SELECT class, method FROM exp_actions WHERE action_id = '".$DB->escape_str($action_id)."'");
            
            if ($query->num_rows == 0)
            {
                if ($PREFS->ini('debug') >= 1)
                {
                    $OUT->fatal_error($LANG->line('invalid_action'));
                }
                else
                    return false;
            }
            
            $class  = ucfirst($query->row['class']);
            $method = strtolower($query->row['method']);
        }
        else
        {
            // If the ID is not numeric we'll invoke the class/method manually
        
            if ( ! isset($specials[$action_id]))
            {
                return false;
            }
        
            $class  = $specials[$action_id]['0'];
            $method = $specials[$action_id]['1'];
        }
        
        
        /** -----------------------------------------
        /**  What type of module is being requested?
        /** -----------------------------------------*/
        
        if (substr($class, -3) == '_CP')
        {
            $type = 'mcp'; 
            
            $base_class = strtolower(substr($class, 0, -3));
        }
        else
        {
            $type = 'mod';
        
            $base_class = strtolower($class);
        }
        
        /** -----------------------------------------
        /**  Assign the path
        /** -----------------------------------------*/
        
        $path = PATH_MOD.$base_class.'/'.$type.'.'.$base_class.EXT;
        
        
        /** -----------------------------------------
        /**  Does the path exist?
        /** -----------------------------------------*/
        
        if ( ! file_exists($path))
        {
            if ($PREFS->ini('debug') >= 1)
            {                        
                $OUT->fatal_error($LANG->line('invalid_action'));
            }
            else
                return false;
        }
        
        /** -----------------------------------------
        /**  Require the class file
        /** -----------------------------------------*/
        
        if ( ! class_exists($class))
        {
            require $path;
        }
        
        /** -----------------------------------------
        /**  Instantiate the class/method
        /** -----------------------------------------*/
        
        $ACT = new $class(0);
        
        if ($method != '')
        {
            if ( ! method_exists($ACT, $method))
            {
                if ($PREFS->ini('debug') >= 1)
                {                        
                    $OUT->fatal_error($LANG->line('invalid_action'));
                }
                else
                    return false;
            }
        
            $ACT->$method();
        }
    }
    /* END */

}
// END CLASS
?>