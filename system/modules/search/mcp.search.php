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
 File: mcp.search.php
-----------------------------------------------------
 Purpose: Search class - CP
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Search_CP {

    var $version = '1.2';
    


    /** --------------------------------
    /**  Module installer
    /** --------------------------------*/

    function search_module_install()
    {
        global $DB;        
        
        $sql[] = "INSERT INTO exp_modules (module_id, module_name, module_version, has_cp_backend) VALUES ('', 'Search', '$this->version', 'n')";
        $sql[] = "INSERT INTO exp_actions (action_id, class, method) VALUES ('', 'Search', 'do_search')";
		$sql[] = "CREATE TABLE IF NOT EXISTS exp_search (
		 search_id varchar(32) NOT NULL,
		 site_id INT(4) NOT NULL DEFAULT 1,
		 search_date int(10) NOT NULL,
		 keywords varchar(60) NOT NULL,
		 member_id int(10) unsigned NOT NULL,
		 ip_address varchar(16) NOT NULL,
		 total_results int(6) NOT NULL,
		 per_page tinyint(3) unsigned NOT NULL,
		 query text NOT NULL,
		 custom_fields text NOT NULL,
		 result_page varchar(70) NOT NULL,
		 PRIMARY KEY (search_id)
		)";
    
    
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

    function search_module_deinstall()
    {
        global $DB;   
        
        $query = $DB->query("SELECT module_id FROM exp_modules WHERE module_name = 'Search'"); 
                
        $sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row['module_id']."'";
        $sql[] = "DELETE FROM exp_modules WHERE module_name = 'Search'";
        $sql[] = "DELETE FROM exp_actions WHERE class = 'Search'";
        $sql[] = "DELETE FROM exp_actions WHERE class = 'Search_CP'";
        
    
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