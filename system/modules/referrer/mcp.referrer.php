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
 File: mcp.referrer.php
-----------------------------------------------------
 Purpose: Referrer class - CP
=====================================================
*/


if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Referrer_CP {

    var $version = '1.3';


    /** -------------------------
    /**  Constructor
    /** -------------------------*/
    
    function Referrer_CP( $switch = TRUE )
    {
        global $IN, $DB;
        
		/** -------------------------------
		/**  Is the module installed?
		/** -------------------------------*/
        
        $query = $DB->query("SELECT COUNT(*) AS count FROM exp_modules WHERE module_name = 'Referrer'");
        
        if ($query->row['count'] == 0)
        {
        	return;
        }

        
        if ($switch)
        {
            switch($IN->GBL('P'))
            {
                case 'view'   			:  $this->view_referrers();
                    break;	
                case 'clear'	  		:  $this->clear_referrers();
                    break;
                case 'delete_confirm'	:  $this->delete_confirm();
                	break;
                case 'delete'			:  $this->delete_referrers();
                	break;
                default       			:  $this->referrer_home();
                    break;
            }
        }
    }
    /* END */
    

    /** -------------------------
    /**  Referrer Home Page
    /** -------------------------*/
    
    function referrer_home($message = '')
    {
        global $DSP, $DB, $LANG, $PREFS;
                        
        $DSP->title = $LANG->line('referrers');
        $DSP->crumb = $LANG->line('referrers'); 

        $DSP->body .= $DSP->qdiv('tableHeading', $LANG->line('referrers'));  
        
        if ($message != '')
        {
			$DSP->body .= $DSP->qdiv('successBox', $DSP->qdiv('success', $message));
        }
    
        $query = $DB->query("SELECT count(*) AS count FROM exp_referrers");
            
        $DSP->body	.=	$DSP->table('tableBorder', '0', '0', '100%').
						$DSP->tr();

		$DSP->body	.=	$DSP->td('tableHeadingAlt', '');
		$DSP->body	.=	$LANG->line('total_referrers').NBS.NBS.$query->row['count'];
		$DSP->body	.=	$DSP->td_c();
		
		$DSP->body	.=	$DSP->tr_c();
		$DSP->body	.=	$DSP->tr();
		
		$DSP->body	.=	$DSP->td('tableCellTwo');
		$DSP->body	.=	$DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=referrer'.AMP.'P=view', $LANG->line('view_referrers')));
		$DSP->body	.=	$DSP->td_c();
		
		$DSP->body	.=	$DSP->tr_c();
		$DSP->body	.=	$DSP->tr();

		$DSP->body	.=	$DSP->td('tableCellTwo');
		$DSP->body	.=	$DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=referrer'.AMP.'P=clear', $LANG->line('clear_referrers')));
		$DSP->body	.=	$DSP->td_c();
		
		$DSP->body	.=	$DSP->tr_c();
		$DSP->body	.=	$DSP->tr();

		$DSP->body	.=	$DSP->td('tableCellTwo');
		$DSP->body	.=	$DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=config_mgr'.AMP.'P=tracking_cfg', $LANG->line('tracking_preferences')));
		$DSP->body	.=	$DSP->td_c();

		$DSP->body	.=	$DSP->tr_c();
		$DSP->body	.=	$DSP->table_c();
    }
    /* END */
    


    /** -------------------------
    /**  View Referrers
    /** -------------------------*/
    
    function view_referrers()
    {
        global $IN, $DSP, $LANG, $FNS, $DB, $LOC, $PREFS, $EXT, $REGX;
        
        // -------------------------------------------
        // 'view_referrers_start' hook.
        //  - Allows complete rewrite of Referrers Viewing page.
        //
        	$edata = $EXT->call_extension('view_referrers_start');
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
                
        if ( ! $rownum = $IN->GBL('rownum', 'GP'))
        {        
            $rownum = 0;
        }
        
        $perpage = 100;
        $search_str = '';
        $search_sql = '';

		$qm = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';
                        
        $DSP->title = $LANG->line('referrers');
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=referrer', $LANG->line('referrers'));
        $DSP->crumb .= $DSP->crumb_item($LANG->line('view_referrers')); 
        
        $DSP->rcrumb = $DSP->qdiv('', $DSP->secure_hash(
        										$DSP->form_open(array('action' => 'C=modules'.AMP.'M=referrer'.AMP.'P=view', 'id' => "referrer_search")).
												'<div style="margin-top:-2px;">'.
												$DSP->input_text('search', '', '15', '50', 'input', '150px').
												$DSP->input_submit($LANG->line('search')).'</div>'.
												$DSP->form_c())
								);

        $r = $DSP->qdiv('tableHeading', $LANG->line('view_referrers'));
        
        if ( isset($_GET['search']) OR isset($_POST['search']))
        {
        	$search_str = (isset($_POST['search'])) ? stripslashes($_POST['search']) : base64_decode($_GET['search']);
        }
        
        if ($search_str != '')
		{
			$s = preg_split("/\s+/", $REGX->keyword_clean($search_str));
			
			foreach($s as $part)
			{
				if (substr($part, 0, 1) == '-')
				{
					$search_sql .= "CONCAT_WS(' ', ref_from, ref_to, ref_ip, ref_agent) NOT LIKE '%".$DB->escape_like_str(substr($part, 1))."%' AND ";
				}
				else
				{
					$search_sql .= "CONCAT_WS(' ', ref_from, ref_to, ref_ip, ref_agent) LIKE '%".$DB->escape_like_str($part)."%' AND ";
				}
			}
			
			$sql = "WHERE (user_blog = '' OR user_blog = 'n') AND (".substr($search_sql, 0, -4).")";
        						
        	$r .= $DSP->qdiv('box', $DSP->qspan('defaultBold', $LANG->line('search')).':'.NBS.$REGX->keyword_clean($search_str));
		}
		else
		{
			$sql = "WHERE user_blog = '' OR user_blog = 'n'";
        }
        
        $query = $DB->query("SELECT COUNT(*) AS count FROM exp_referrers ".$sql);
        
        if ($query->row['count'] == 0)
		{
			$r .= $DSP->qdiv('box', $DSP->qdiv('highlight', $LANG->line('no_referrers')));
		
			$DSP->body .= $r;        

			return;
		}
        
        $total = $query->row['count'];
        
        $sites_query = $DB->query("SELECT site_id, site_label FROM exp_sites");
        $sites = array();
        
        foreach($sites_query->result as $row)
        {
        	$sites[$row['site_id']] = $row['site_label'];
        }
        
        $query = $DB->query("SELECT * FROM exp_referrers ".$sql." ORDER BY ref_id desc LIMIT $rownum, $perpage");
        
        $r .= $DSP->qdiv('box', $LANG->line('total_referrers').NBS.NBS.$total);
        					
        $r .= $DSP->toggle();
        
        $DSP->body_props .= ' onload="magic_check()" ';
        
        $r .= $DSP->magic_checkboxes();

		$r .= <<<EOT

<script type="text/javascript">
function showHide(entryID, htmlObj, linkType) {

extTextDivID = ('extText' + (entryID));
extLinkDivID = ('extLink' + (entryID));

if (linkType == 'close')
{
	document.getElementById(extTextDivID).style.display = "none";
	document.getElementById(extLinkDivID).style.display = "block";
	htmlObj.blur();
}
else
{
	document.getElementById(extTextDivID).style.display = "block";
	document.getElementById(extLinkDivID).style.display = "none";
	htmlObj.blur();
}

}
</script>

EOT;

		$r .= $DSP->form_open(array('action' => 'C=modules'.AMP.'M=referrer'.AMP.'P=delete_confirm', 'name' => 'target', 'id' => 'target'));
		
        $r .= $DSP->table('tableBorder', '0', '', '100%').
              $DSP->tr().
              $DSP->table_qcell('tableHeadingAlt', 
                                array(
                                        $DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"").$LANG->line('delete'),
                                        $LANG->line('referrer_from'),
                                        $LANG->line('referrer_to'),
                                        $LANG->line('referrer_date')
                                     )
                                ).
              $DSP->tr_c();


        $i = 0;
        
		$site_url = $PREFS->ini('site_url');
        
        foreach($query->result as $row)
        {
            $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
                      
            $r .= $DSP->tr();
            
            // Delete Toggle
            
            $r .= $DSP->table_qcell($style, $DSP->input_checkbox('toggle[]', $row['ref_id'], '', "id='delete_box_".$row['ref_id']."'"), '12%');
            
            // From
            $row['ref_from'] = str_replace('http://','',$row['ref_from']);
            
            if (strlen($row['ref_from']) > 40)
            {
            	$from_pieces = explode('/',$row['ref_from']);
            	
            	$new_from = $from_pieces['0'].'/';
            	
            	for($p=1; $p < sizeof($from_pieces); $p++)
            	{
            		if (strlen($from_pieces[$p]) + strlen($new_from) <= 40)
            		{
            			$new_from .= ($p == (sizeof($from_pieces) - 1)) ? $from_pieces[$p] : $from_pieces[$p].'/';
            		}
            		else
            		{
            			$new_from .= '&#8230;';
            			break;
            		}
            	} 
            }
            else
            {
            	$new_from = $row['ref_from'];
            }
            
            $r .= $DSP->table_qcell($style, $DSP->anchor($FNS->fetch_site_index().$qm.'URL='.urlencode($row['ref_from']), $new_from, '', 1), '34%');
        
            // To
            
            $to = '/'.ltrim(str_replace($site_url, '', $row['ref_to']), '/');
            
            $r .= $DSP->table_qcell($style, $DSP->anchor($FNS->fetch_site_index().$qm.'URL='.urlencode($row['ref_to']), $to, '', 1), '34%');
                	
        	// Date
        	
        	$date = ($row['ref_date'] != '' AND $row['ref_date'] != 0) ? $LOC->set_human_time($row['ref_date']) : '-';
        	
        	$r .= $DSP->table_qcell($style, $date, '20%');
        	
			$r .= $DSP->tr_c();
			$r .= $DSP->tr();
        	$r .= $DSP->td($style, '', '1');
        	$r .= NBS;
            $r .= $DSP->td_c();
         
        	// IP
        	$ip = ($row['ref_ip'] != '' AND $row['ref_ip'] != 0) ? $row['ref_ip'] : '-';
        	$r .= $DSP->td($style, '', '1');
        	$r .= $DSP->qspan('defaultBold', $LANG->line('referrer_ip')).':'.NBS.$ip;
            $r .= $DSP->td_c();

        	// Agent
        	$agent = ($row['ref_agent'] != '') ? $row['ref_agent'] : '-';
        	if (strlen($agent) > 11) 
        	{
        		$agent2 = $DSP->qspan('defaultBold', $LANG->line('ref_user_agent')).':'.NBS."<a href=\"javascript:void(0);\" name=\"ext{$i}\" onclick=\"showHide({$i},this,'close');return false;\">[-]</a>".NBS.NBS.$agent;
        		
        		$agent = "<div id='extLink{$i}'>".$DSP->qspan('defaultBold', $LANG->line('ref_user_agent')).':'.NBS."<a href=\"javascript:void(0);\" name=\"ext{$i}\" onclick=\"showHide({$i},this,'open');return false;\">[+]</a>".NBS.NBS.preg_replace("/(.+?)\s+.*/", "\\1", $agent)."</div>";
        		
				$agent .= '<div id="extText'.$i.'" style="display: none; padding:0;">'.$agent2.'</div>';
        	}
        	
            $r .= $DSP->td($style, '', '1');
        	$r .= $DSP->qspan('defaultBold', $LANG->line('site')).':'.NBS.$sites[$row['site_id']];
            $r .= $DSP->td_c();
            
            $r .= $DSP->td($style, '', '1');
        	$r .= $agent;
            $r .= $DSP->td_c();


			$r .= $DSP->tr();            
        }

        $r .= $DSP->table_c();
             
        $r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('delete')));             
        
        $r .= $DSP->form_close();     

        // Pass the relevant data to the paginate class so it can display the "next page" links
        
        $r .=  $DSP->div('itemWrapper').
               $DSP->pager(
                            BASE.AMP.'C=modules'.AMP.'M=referrer'.AMP.'P=view'.(($search_str == '') ? '' : AMP.'search='.base64_encode($search_str)),
                            $total,
                            $perpage,
                            $rownum,
                            'rownum'
                          ).
              $DSP->div_c();


        $DSP->body .= $r;        
    }
    /* END */
    
    
	/** -------------------------------------------
    /**  Delete Confirm
    /** -------------------------------------------*/

    function delete_confirm()
    { 
        global $IN, $DSP, $LANG, $DB;
        
        if ( ! $IN->GBL('toggle', 'POST'))
        {
            return $this->view_referrers();
        }
        
       	$DSP->title = $LANG->line('referrers');
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=referrer', $LANG->line('referrers'));
        $DSP->crumb .= $DSP->crumb_item($LANG->line('delete_confirm'));    

        $DSP->body	.=	$DSP->form_open(array('action' => 'C=modules'.AMP.'M=referrer'.AMP.'P=delete'));
        
        $i = 0;
        
        foreach ($_POST as $key => $val)
        {        
            if (strstr($key, 'toggle') AND ! is_array($val))
            {
                $DSP->body	.=	$DSP->input_hidden('delete[]', $val);
                
                $i++;
            }        
        }
        
		$DSP->body .= $DSP->qdiv('alertHeading', $LANG->line('delete_confirm'));
		$DSP->body .= $DSP->div('box');
		$DSP->body .= $DSP->qdiv('defaultBold', $LANG->line('referrer_delete_question'));
		$DSP->body .= $DSP->qdiv('alert', BR.$LANG->line('action_can_not_be_undone'));
		$DSP->body .= $DSP->div_c();

        $DSP->body .= $DSP->table('tableBorder', '0', '', '100%').
        			  $DSP->tr(). 
        			  $DSP->table_qcell('tableHeading', $LANG->line('blacklist_question')).
        			  $DSP->table_qcell('tableHeading', NBS).
        			  $DSP->tr_c();
        			  
        $style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';;
                      
		if ($DB->table_exists('exp_blacklisted') === TRUE)
		{
			$add_ips = $LANG->line('add_and_blacklist_ips');
			$add_urls = $LANG->line('add_and_blacklist_urls');
			$add_agents = $LANG->line('add_and_blacklist_agents');						
		}
		else
		{
			$add_ips = $LANG->line('add_ips');
			$add_urls = $LANG->line('add_urls');
			$add_agents = $LANG->line('add_agents');				
		}

        $DSP->body .= $DSP->tr().
        			  $DSP->table_qcell('tableCellOne', $add_urls).
        			  $DSP->table_qcell('tableCellOne', $DSP->input_checkbox('add_urls', 'y')).
        			  $DSP->tr_c().
        			  $DSP->tr().
        			  $DSP->table_qcell('tableCellTwo', $add_ips).
        			  $DSP->table_qcell('tableCellTwo', $DSP->input_checkbox('add_ips', 'y')).
        			  $DSP->tr_c().
        			  $DSP->tr().
        			  $DSP->table_qcell('tableCellOne', $add_agents).
        			  $DSP->table_qcell('tableCellOne', $DSP->input_checkbox('add_agents', 'y')).
        			  $DSP->tr_c();
        			  
		$DSP->body .= $DSP->table_c();
		
		$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('delete')));
		$DSP->body .= $DSP->form_close();
    }
    /* END */
    
    
    
    /** -------------------------------------------
    /**  Delete Referrers
    /** -------------------------------------------*/

    function delete_referrers()
    { 
        global $IN, $DSP, $LANG, $SESS, $DB, $STAT, $EXT;
        
        if ( ! $IN->GBL('delete', 'POST'))
        {
            return $this->referrer_home();
        }
        
        // -------------------------------------------
        // 'delete_referrers_start' hook.
        //  - Allows complete control of the delete referrers routine
        //  - Can also add additional processing
        //
        	$edata = $EXT->call_extension('delete_referrers_start');
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
        

        $ids = array();
        $new = array('url'=>array(),'ip' => array(), 'agent' => array());
		$white = array('url'=>array(),'ip' => array(), 'agent' => array());
                
        foreach ($_POST as $key => $val)
        {        
            if (strstr($key, 'delete') AND ! is_array($val))
            {
                $ids[] = "ref_id = '".$val."'";
            }
        }
        
        $IDS = implode(" OR ", $ids);
        
        /** --------------------------
        /**  Add To Blacklist?
        /** --------------------------*/
        
        if (isset($_POST['add_urls']) || isset($_POST['add_agents']) || isset($_POST['add_ips']))
        {
        	$query = $DB->query("SELECT ref_from, ref_ip, ref_agent FROM exp_referrers WHERE ".$IDS);
        	
        	if ($query->num_rows == 0)
        	{
        		return $this->referrer_home();
        	}
        	
        	/** ---------------------
        	/**  New Values
        	/** ---------------------*/
        	
        	foreach($query->result as $row)
        	{
        		if(isset($_POST['add_urls']))
        		{
        			$mod_url = str_replace('http://','',$row['ref_from']);
        			$new['url'][] = str_replace('www.','',$mod_url);
        		}
        		
        		if(isset($_POST['add_agents']))
        		{
        			$new['agent'][] = $row['ref_agent'];
        		}
        		
        		if(isset($_POST['add_ips']))
        		{
        			$new['ip'][] = $row['ref_ip'];
        		}
        	}
 
        	/** -----------------------------
        	/**  Add Current Blacklisted - but only if the table exists!
        	/** -----------------------------*/

			if ($DB->table_exists('exp_blacklisted'))
			{       	
        
        		$query			= $DB->query("SELECT * FROM exp_blacklisted");
        		$old['url']		= array();
        		$old['agent']	= array();
        		$old['ip']		= array();
        
        		if ($query->num_rows > 0)
        		{
        			foreach($query->result as $row)
        			{
        				$old_values = explode('|',$row['blacklisted_value']);
        				for ($i=0; $i < sizeof($old_values); $i++)
        				{
        					$old[$row['blacklisted_type']][] = $old_values[$i]; 
        				}       	
        			}
        		}
        	
        		/** -----------------------------------------
        		/**  Check for uniqueness and sort
        		/** -----------------------------------------*/
        
        		$new['url'] 	= array_unique(array_merge($old['url'],$new['url']));
        		$new['agent']	= array_unique(array_merge($old['agent'],$new['agent']));
        		$new['ip']		= array_unique(array_merge($old['ip'],$new['ip']));
        	
        		sort($new['url']);
        		sort($new['agent']);
        		sort($new['ip']);         	
        	
        	
        		/** -----------------------------------------
				/**  Put blacklist info back into database
				/** -----------------------------------------*/
			
				$DB->query("DELETE FROM exp_blacklisted");
		
				foreach($new as $key => $value)
				{
					$blacklisted_value = implode('|',$value);
			
					$data = array(	'blacklisted_type' 	=> $key,
								'blacklisted_value'	=> $blacklisted_value);
								
					$DB->query($DB->insert_string('exp_blacklisted', $data));
				}
			
				/** -----------------------------------------
        		/**  Current Whitelisted
        		/** -----------------------------------------*/
        
        		$query				= $DB->query("SELECT * FROM exp_whitelisted");
        
        		if ($query->num_rows > 0)
        		{
        			foreach($query->result as $row)
        			{
        				$white_values = explode('|',$row['whitelisted_value']);
        				for ($i=0; $i < sizeof($white_values); $i++)
        				{
        					if (trim($white_values[$i]) != '')
        					{
        						$white[$row['whitelisted_type']][] = $white_values[$i]; 
        					}
						}
        			}       	
        		}
     
        		/** ----------------------------------------------
				/**  Using new blacklist members, clean out spam
				/** ----------------------------------------------*/
		
				$new['url']		= array_diff($new['url'], $old['url']);
				$new['agent']	= array_diff($new['agent'], $old['agent']);
        		$new['ip']		= array_diff($new['ip'], $old['ip']);
         	}       	

        	// -------------------------------------------
			// 'delete_referrers_blacklist' hook.
			//  - Accepts the new members for the blacklist and you can
			//  do whatever you want with 'em
			//
				$edata = $EXT->call_extension('delete_referrers_blacklist', $new);
				if ($EXT->end_script === TRUE) return;
			//
			// -------------------------------------------
        	
        	
        	$modified_weblogs = array();
        
        	foreach($new as $key => $value)
			{
				$name = ($key == 'url') ? 'from' : $key; 
				
				if (sizeof($value) > 0 && isset($_POST['add_'.$key.'s']))
				{
					sort($value);
					
					for($i=0; $i < sizeof($value); $i++)
					{
						if ($value[$i] != '')
						{
							$sql = "DELETE FROM exp_referrers WHERE ref_{$name} LIKE '%".$DB->escape_like_str($value[$i])."%'";
							
							if (sizeof($white[$key]) > 1)
							{
								$sql .=  " AND ref_{$name} NOT LIKE '%".implode("%' AND ref_{$name} NOT LIKE '%", $DB->escape_like_str($white[$key]))."%'";
							}
							elseif (sizeof($white[$key]) > 0)
							{
								$sql .= "AND ref_{$name} NOT LIKE '%".$DB->escape_like_str($white[$key]['0'])."%'";
							}
							
							$DB->query($sql);
							
							if ($key == 'url' OR $key == 'ip')
							{
								$sql = " exp_trackbacks WHERE trackback_".$key." LIKE '%".$DB->escape_like_str($value[$i])."%'";
							
								if (sizeof($white[$key]) > 1)
								{
									$sql .=  " AND trackback_".$key." NOT LIKE '%".implode("%' AND trackback_".$key." NOT LIKE '%", $DB->escape_like_str($white[$key]))."%'";
								}
								elseif (sizeof($white[$key]) > 0)
								{
									$sql .= "AND trackback_".$key." NOT LIKE '%".$DB->escape_like_str($white[$key]['0'])."%'";
								}
							
								$query = $DB->query("SELECT entry_id, weblog_id FROM".$sql);
							
								if ($query->num_rows > 0)
								{
									$DB->query("DELETE FROM".$sql);
							
									foreach($query->result as $row)
									{
										$modified_weblogs[] = $row['weblog_id'];
									
										$results = $DB->query("SELECT COUNT(*) AS count from exp_trackbacks WHERE entry_id = '".$row['entry_id']."'");
										$results2 = $DB->query("SELECT MAX(trackback_date) AS max_date FROM exp_trackbacks 
																WHERE entry_id = '".$row['entry_id']."'");
            							$date = ($results2->num_rows == 0 OR ! is_numeric($results2->row['max_date'])) ? 0 : $results2->row['max_date'];
            						
										$DB->query("UPDATE exp_weblog_titles 
													SET trackback_total = '".$results->row['count']."',
													recent_trackback_date = '{$date}'
													WHERE entry_id = '".$row['entry_id']."'");
									}							
								}
							}
						}			
					}
				}
        	}
        }
        
        if (isset($modified_weblogs) && sizeof($modified_weblogs) > 0)
        {
        	$modified_weblogs = array_unique($modified_weblogs);
        	
        	foreach($modified_weblogs as $weblog_id)
        	{
        		$STAT->update_trackback_stats($weblog_id);
        	}
        }
        
        // -------------------------------------------
        // 'delete_referrers_end' hook.
        //  - Add additional processing at the end of routine
        //
        	$edata = $EXT->call_extension('delete_referrers_end');
        	if ($EXT->end_script === TRUE) return;
        //
        // -------------------------------------------
        
        
        /** --------------------------
        /**  Delete Referrers
        /** --------------------------*/
        
        $DB->query("DELETE FROM exp_referrers WHERE ".$IDS);
    
        $message = (count($ids) == 1) ? $LANG->line('referrer_deleted') : $LANG->line('referrers_deleted');

        return $this->referrer_home($message);
    }
    /* END */
    
    

    /** -------------------------
    /**  Clear Referrers
    /** -------------------------*/
    
    function clear_referrers()
    {
        global $IN, $DSP, $LANG, $DB;
                
        $DSP->title = $LANG->line('referrers');
        $DSP->title = $LANG->line('referrers');
        $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=referrer', $LANG->line('referrers'));
        $DSP->crumb .= $DSP->crumb_item($LANG->line('clear_referrers'));        

        $r = $DSP->qdiv('tableHeading', $LANG->line('clear_referrers'));
        
        $save = ( ! isset($_POST['save'])) ? '' : $_POST['save'];
                
        if ($save < 0) 
        		$save = 0;
        	        
        if (is_numeric($save) AND $save >= 0)
        {
            $query = $DB->query("SELECT count(*) AS count FROM exp_referrers");
        
            $total = $query->row['count'];
            
            if ($save == 0)
            {  
                $DB->query("DELETE FROM exp_referrers");
                
                $total = 0;
            }
            else
            {
				if ($total > $save)
				{         
					$query = $DB->query("SELECT MAX(ref_id) AS max_id FROM exp_referrers");
					
					$max = ($query->num_rows == 0 OR ! is_numeric($query->row['max_id'])) ? 0 : $query->row['max_id'];
				
					$save--;
					
					$id = $max - $save;
					
					$DB->query("DELETE FROM exp_referrers WHERE ref_id < $id");
					
					$total = $save +1;
				}
			}
            
            $r .= $DSP->qdiv('successBox', $DSP->qdiv('success', $LANG->line('referrers_deleted')));
            
            $r .= $DSP->qdiv('box', $LANG->line('total_referrers').NBS.NBS.$total);
        }
        else
        {
            $r .= $DSP->form_open(array('action' => 'C=modules'.AMP.'M=referrer'.AMP.'P=clear'))
                 .$DSP->div('box')
                 .$DSP->qdiv('itemWrapper', $DSP->qdiv('defaultBold', $LANG->line('save_instructions')))
                 .$DSP->input_text('save', '100', '6', '4', 'input', '50px')
                 .$DSP->qdiv('itemWrapper', BR.$DSP->input_submit($LANG->line('submit')))
                 .$DSP->div_c()
                 .$DSP->form_close();
        }
        
        $DSP->body = $r;
    }
    /* END */
    
    
    


    /** -------------------------
    /**  Module installer
    /** -------------------------*/

    function referrer_module_install()
    {
        global $DB;        
        
        $sql[] = "INSERT INTO exp_modules (module_id, module_name, module_version, has_cp_backend) VALUES ('', 'Referrer', '$this->version', 'y')";
    
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

    function referrer_module_deinstall()
    {
        global $DB;    

        $query = $DB->query("SELECT module_id FROM exp_modules WHERE module_name = 'Referrer'"); 
                
        $sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row['module_id']."'";
        $sql[] = "DELETE FROM exp_modules WHERE module_name = 'Referrer'";
        $sql[] = "DELETE FROM exp_actions WHERE class = 'Referrer'";
        $sql[] = "DELETE FROM exp_actions WHERE class = 'Referrer_CP'";

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