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
 File: mod.stats.php
-----------------------------------------------------
 Purpose: Statistics module
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Stats {

	var $return_data 	= '';

	/** -----------------------------
	/**  Constructor
	/** -----------------------------*/

	function Stats()
	{
		global $TMPL, $LOC, $STAT, $SESS, $DB, $FNS, $REGX, $PREFS;
		
		if (! isset($STAT->stats) OR empty($STAT->stats))
		{
			$STAT->load_stats();
		}	
		
        /** -----------------------------------------
        /**  Limit stats by weblog
        /** -----------------------------------------*/
                    
	    // You can limit the stats by any combination of weblogs
	    // - but only if it is not a user blogs request
	    
        if (USER_BLOG === FALSE)
        {	    
            if ($blog_name = $TMPL->fetch_param('weblog'))
            {
            
                $sql = "SELECT	total_entries, 
                				total_comments,
                				total_trackbacks,
                				last_entry_date,
                				last_comment_date,
                				last_trackback_date 
                        FROM exp_weblogs 
                        WHERE site_id IN ('".implode("','", $TMPL->site_ids)."') 
                        AND exp_weblogs.is_user_blog = 'n' ";
                        
				$sql .= $FNS->sql_andor_string($blog_name, 'exp_weblogs.blog_name');
                                            
                $cache_sql = md5($sql);
                                                
                if ( ! isset($STAT->stats_cache[$cache_sql]))
                { 	        
                    $query = $DB->query($sql);
                    
                    $sdata = array(
                    					'total_entries'			=> 0,
                    					'total_comments'		=> 0,
                    					'total_trackbacks'		=> 0,
                    					'last_entry_date'		=> 0,
                    					'last_comment_date'		=> 0,
                    					'last_trackback_date'	=> 0
                    			  );
                    			  
                    
                    if ($query->num_rows > 0)
                    {
                        foreach($query->result as $row)
                        { 
                        	foreach ($sdata as $key => $val)
                        	{
                        		if (substr($key, 0, 5) == 'last_')
                        		{
									if ($row[$key] > $val)
									{
										$sdata[$key] = $row[$key];
									}
								}
								else
								{
									$sdata[$key] = $sdata[$key] + $row[$key];
								}
							}
                        }
					
						foreach ($sdata as $key => $val)
						{                        
                            $STAT->stats[$key] = $val;
                            
                            $STAT->stats_cache[$cache_sql][$key] = $val;
                       	} 
                    }
                }
                else
                {
                    foreach($STAT->stats_cache[$cache_sql] as $key => $val)
                    {
                        $STAT->stats[$key] = $val;
                    }
                }
            }
	    }
	    	       	       
		/** ----------------------------------------
		/**  Parse stat fields
		/** ----------------------------------------*/

		$fields = array('total_members', 'total_entries', 'total_forum_topics', 'total_forum_replies', 'total_forum_posts', 'total_comments', 'total_trackbacks', 'most_visitors', 'total_logged_in', 'total_guests', 'total_anon');
		$cond	= array();

		foreach ($fields as $field)
		{
			if ( isset($TMPL->var_single[$field]))
			{
				$cond[$field] = $STAT->stats[$field];
				$TMPL->tagdata = $TMPL->swap_var_single($field, $STAT->stats[$field], $TMPL->tagdata);
			}
		}
		
		if (sizeof($cond) > 0)
		{
			$TMPL->tagdata = $FNS->prep_conditionals($TMPL->tagdata, $cond);
		}
		

		/** ----------------------------------------
		/**  Parse dates
		/** ----------------------------------------*/

		$dates = array('last_entry_date', 'last_forum_post_date',  'last_comment_date', 'last_trackback_date', 'last_visitor_date', 'most_visitor_date');

		foreach ($TMPL->var_single as $key => $val)
		{   
			foreach ($dates as $date)
			{
				if (strncmp($date, $key, strlen($date)) == 0)
				{
					$TMPL->tagdata = $TMPL->swap_var_single(
																$key, 
																( ! isset($STAT->stats[$date]) || $STAT->stats[$date] == 0) ? '--' : 
																$LOC->decode_date($val, $STAT->stats[$date]), 
																$TMPL->tagdata
															 );
				}
			}
		}
		
		
		/** ----------------------------------------
		/**  Online user list
		/** ----------------------------------------*/
		
		$names = '';

		if (count($STAT->stats['current_names']) > 0)
		{
			$chunk = $TMPL->fetch_data_between_var_pairs($TMPL->tagdata, 'member_names');      
			
			$backspace = '';
			
			if ( ! preg_match("/".LD."member_names.*?backspace=[\"|'](.+?)[\"|']/", $TMPL->tagdata, $match))
			{
				if (preg_match("/".LD."name.*?backspace=[\"|'](.+?)[\"|']/", $TMPL->tagdata, $match))
				{
					$backspace = $match['1'];
				}
			}
			else
			{
				$backspace = $match['1'];
			}
			
			$member_path = (preg_match("/".LD."member_path=(.+?)".RD."/", $TMPL->tagdata, $match)) ? $match['1'] : '';
			$member_path = str_replace("\"", "", $member_path);
			$member_path = str_replace("'",  "", $member_path);
			$member_path = $REGX->trim_slashes($member_path);
					
			foreach ($STAT->stats['current_names'] as $k => $v)
			{
				$temp = $chunk;
			
				if ($v['1'] == 'y')
				{
					if ($SESS->userdata['group_id'] == 1)
					{
						$temp = preg_replace("/".LD."name.*?".RD."/", $v['0'].'*', $temp);
					}
					elseif ($SESS->userdata('member_id') == $k)
					{
						$temp = preg_replace("/".LD."name.*?".RD."/", $v['0'].'*', $temp);
					}
					else
					{
						continue;
					}
				}
				else
				{
					$temp = preg_replace("/".LD."name.*?".RD."/", $v['0'], $temp);
				}
				
				
				$path = $FNS->create_url($member_path.'/'.$k);	
				
				$temp = preg_replace("/".LD."member_path=(.+?)".RD."/", $path, $temp);
				
				$names .= $temp;
			}
			
			
			if (is_numeric($backspace))
			{				
				$names = rtrim(str_replace("&#47;", "/", $names));
				$names = substr($names, 0, - $backspace);
				$names = str_replace("/", "&#47;", $names);
			}
			
		}
				
		$names = str_replace(LD.'name'.RD, '', $names);

		$TMPL->tagdata = preg_replace("/".LD.'member_names'.".*?".RD."(.*?)".LD.SLASH.'member_names'.RD."/s", $names, $TMPL->tagdata);

		
		/** ----------------------------------------
		/**  {if member_names}
		/** ----------------------------------------*/
		
		if ($names != '')
		{
			$TMPL->tagdata = preg_replace("/".LD.'if member_names'.".*?".RD."(.*?)".LD.SLASH.'if'.RD."/s", "\\1", $TMPL->tagdata);
		}
		else
		{
			$TMPL->tagdata = preg_replace("/".LD.'if member_names'.".*?".RD."(.*?)".LD.SLASH.'if'.RD."/s", "", $TMPL->tagdata);
		}
		
		$this->return_data = $TMPL->tagdata;
	}
	/* END */


}
// END CLASS
?>