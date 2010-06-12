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
 File: mod.weblog_calendar.php
-----------------------------------------------------
 Purpose: Weblog_calendar class
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Weblog_calendar extends Weblog {

	var $sql = '';

    /** ----------------------------------------
    /**  Constructor
    /** ----------------------------------------*/

    function Weblog_calendar()
    { 
    }
    /* END */
    
    /** ----------------------------------------
    /**  Weblog Calendar
    /** ----------------------------------------*/
    
    function calendar()
    {
    	global $LANG, $TMPL, $LOC, $IN, $DB, $FNS, $PREFS, $SESS;

		// Rick is using some funky conditional stuff for the calendar, so
		// we have to reassign the var_cond array using the legacy conditional 
		// parser.  Bummer, but whatcha going to do?
		
		$TMPL->var_cond = $FNS->assign_conditional_variables($TMPL->tagdata, SLASH, LD, RD);

		/** ----------------------------------------
		/**  Determine the Month and Year
		/** ----------------------------------------*/
		
		$year  = '';
		$month = '';
		
		// Hard-coded month/year via tag parameters
		
		if ($TMPL->fetch_param('month') AND $TMPL->fetch_param('year'))
		{
			$year 	= $TMPL->fetch_param('year');
			$month	= $TMPL->fetch_param('month');
			
			if (strlen($month) == 1)
			{
				$month = '0'.$month;
			}
		}
		else
		{
			// Month/year in query string
		
			if (preg_match("#(\d{4}/\d{2})#", $IN->QSTR, $match))
			{
				$ex = explode('/', $match['1']);
				
				$time = mktime(0, 0, 0, $ex['1'], 01, $ex['0']);
				// $time = $LOC->set_localized_time(mktime(0, 0, 0, $ex['1'], 01, $ex['0']));

				$year  = date("Y", $time);
				$month = date("m", $time);
			}
			else
			{
				// Defaults to current month/year
			
				$year  = date("Y", $LOC->set_localized_time($LOC->now));
				$month = date("m", $LOC->set_localized_time($LOC->now));
			}
    	}
    	    	
    	    
		/** ----------------------------------------
		/**  Set Unix timestamp for the given month/year
		/** ----------------------------------------*/
    	
		$local_date = mktime(12, 0, 0, $month, 1, $year);
		// $local_date = $LOC->set_localized_time($local_date);

		/** ----------------------------------------
		/**  Determine the total days in the month
		/** ----------------------------------------*/

        $adjusted_date = $LOC->adjust_date($month, $year);
        
        $month	= $adjusted_date['month'];
        $year	= $adjusted_date['year'];  
        
		$total_days = $LOC->fetch_days_in_month($month, $year);
		
		$previous_date 	= mktime(12, 0, 0, $month-1, 1, $year);
		$next_date 		= mktime(12, 0, 0, $month+1, 1, $year);
    	
		/** ---------------------------------------
		/**  Determine the total days of the previous month
		/** ---------------------------------------*/
		
		$adj_prev_date = $LOC->adjust_date($month-1, $year);

		$prev_month = $adj_prev_date['month'];
		$prev_year = $adj_prev_date['year'];

		$prev_total_days = $LOC->fetch_days_in_month($prev_month, $prev_year);

		/** ----------------------------------------
		/**  Set the starting day of the week
		/** ----------------------------------------*/
				
		// This can be set using a parameter in the tag:  start_day="saturday"
		// By default the calendar starts on sunday
		
		$start_days = array('sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6);
				
		$start_day = (isset($start_days[$TMPL->fetch_param('start_day')])) ? $start_days[$TMPL->fetch_param('start_day')]: 0;
    	
		$date = getdate($local_date);
		$day  = $start_day + 1 - $date["wday"];
		
		while ($day > 1)
		{
    			$day -= 7;
		}
		
		/** ----------------------------------------
		/**  {previous_path="weblog/index"}
		/** ----------------------------------------*/
		
		// This variables points to the previous month

		if (preg_match_all("#".LD."previous_path=(.+?)".RD."#", $TMPL->tagdata, $matches))
		{
			$adjusted_date = $LOC->adjust_date($month - 1, $year, TRUE);
					
			foreach ($matches['1'] as $match)
			{
				$path = $FNS->create_url($match).$adjusted_date['year'].'/'.$adjusted_date['month'].'/';
				
				$TMPL->tagdata = preg_replace("#".LD."previous_path=.+?".RD."#", $path, $TMPL->tagdata, 1);
			}
		}
		
		/** ----------------------------------------
		/**  {next_path="weblog/index"}
		/** ----------------------------------------*/
		
		// This variables points to the next month

		if (preg_match_all("#".LD."next_path=(.+?)".RD."#", $TMPL->tagdata, $matches))
		{
			$adjusted_date = $LOC->adjust_date($month + 1, $year, TRUE);
					
			foreach ($matches['1'] as $match)
			{
				$path = $FNS->create_url($match).$adjusted_date['year'].'/'.$adjusted_date['month'].'/';
				
				$TMPL->tagdata = preg_replace("#".LD."next_path=.+?".RD."#", $path, $TMPL->tagdata, 1);
			}
		}
		
		/** ----------------------------------------
		/**  {date format="%m %Y"}
		/** ----------------------------------------*/
		
		// This variable is used in the heading of the calendar
		// to show the month and year

		if (preg_match_all("#".LD."date format=[\"|'](.+?)[\"|']".RD."#", $TMPL->tagdata, $matches))
		{		
			foreach ($matches['1'] as $match)
			{
				$TMPL->tagdata = preg_replace("#".LD."date format=.+?".RD."#", $LOC->decode_date($match, $local_date), $TMPL->tagdata, 1);
			}
		}
		
		/** ----------------------------------------
		/**  {previous_date format="%m %Y"}
		/** ----------------------------------------*/
		
		// This variable is used in the heading of the calendar
		// to show the month and year

		if (preg_match_all("#".LD."previous_date format=[\"|'](.+?)[\"|']".RD."#", $TMPL->tagdata, $matches))
		{		
			foreach ($matches['1'] as $match)
			{
				$TMPL->tagdata = preg_replace("#".LD."previous_date format=.+?".RD."#", $LOC->decode_date($match, $previous_date), $TMPL->tagdata, 1);
			}
		}
		
		/** ----------------------------------------
		/**  {next_date format="%m %Y"}
		/** ----------------------------------------*/
		
		// This variable is used in the heading of the calendar
		// to show the month and year

		if (preg_match_all("#".LD."next_date format=[\"|'](.+?)[\"|']".RD."#", $TMPL->tagdata, $matches))
		{		
			foreach ($matches['1'] as $match)
			{
				$TMPL->tagdata = preg_replace("#".LD."next_date format=.+?".RD."#", $LOC->decode_date($match, $next_date), $TMPL->tagdata, 1);
			}
		}

				
		/** ----------------------------------------
		/**  Day Heading
		/** ----------------------------------------*/
		/*
			This code parses out the headings for each day of the week
			Contained in the tag will be this variable pair:
			
			{calendar_heading}
			<td class="calendarDayHeading">{lang:weekday_abrev}</td>
			{/calendar_heading}
		
			There are three display options for the header:
			
			{lang:weekday_abrev} = S M T W T F S
			{lang:weekday_short} = Sun Mon Tues, etc.
			{lang:weekday_long} = Sunday Monday Tuesday, etc.
		
		*/
		
		foreach (array('Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa') as $val)
		{
			$day_names_a[] = ( ! $LANG->line($val)) ? $val : $LANG->line($val);
		}
		
		foreach (array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat') as $val)
		{
			$day_names_s[] = ( ! $LANG->line($val)) ? $val : $LANG->line($val);
		}
		
		foreach (array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') as $val)
		{
			$day_names_l[] = ( ! $LANG->line($val)) ? $val : $LANG->line($val);
		}
						
		if (preg_match("/".LD."calendar_heading".RD."(.*?)".LD.SLASH."calendar_heading".RD."/s", $TMPL->tagdata, $match))
		{	
			$temp = '';
		
			for ($i = 0; $i < 7; $i ++)
			{
				$temp .= str_replace(array( LD.'lang:weekday_abrev'.RD,
											LD.'lang:weekday_short'.RD,
											LD.'lang:weekday_long'.RD),
									 array( $day_names_a[($start_day + $i) %7],
									 		$day_names_s[($start_day + $i) %7],
									 		$day_names_l[($start_day + $i) %7]),
									  trim($match['1'])."\n");
			}

			$TMPL->tagdata = preg_replace ("/".LD."calendar_heading".RD.".*?".LD.SLASH."calendar_heading".RD."/s", trim($temp), $TMPL->tagdata);
		}
	
	
		/** ----------------------------------------
		/**  Separate out cell data
		/** ----------------------------------------*/
		
		// We need to strip out the various variable pairs
		// that allow us to render each calendar cell.
		// We'll do this up-front and assign temporary markers
		// in the template which we will replace with the final
		// data later
		
		$row_start 			= ''; 
		$row_end 			= ''; 

		$row_chunk 			= ''; 
		$row_chunk_m		= '94838dkAJDei8azDKDKe01';
		
		$entries 			= ''; 
		$entries_m			= 'Gm983TGxkedSPoe0912NNk';

		$if_today 			= ''; 
		$if_today_m			= 'JJg8e383dkaadPo20qxEid';
		
		$if_entries 		= ''; 
		$if_entries_m		= 'Rgh43K0L0Dff9003cmqQw1';

		$if_not_entries 	= ''; 
		$if_not_entries_m	= 'yr83889910BvndkGei8ti3';

		$if_blank 			= ''; 
		$if_blank_m			= '43HDueie4q7pa8dAAseit6';

		
		if (preg_match("/".LD."calendar_rows".RD."(.*?)".LD.SLASH."calendar_rows".RD."/s", $TMPL->tagdata, $match))
		{	
			$row_chunk = trim($match['1']);
			
			//  Fetch all the entry_date variable
												
			if (preg_match_all("/".LD."entry_date\s+format=[\"'](.*?)[\"']".RD."/s", $row_chunk, $matches))
			{
				for ($j = 0; $j < count($matches['0']); $j++)
				{
					$matches['0'][$j] = str_replace(array(LD,RD), '', $matches['0'][$j]);

					$entry_dates[$matches['0'][$j]] = $LOC->fetch_date_params($matches['1'][$j]);
				}
			}			
									
			if (preg_match("/".LD."row_start".RD."(.*?)".LD.SLASH."row_start".RD."/s", $row_chunk, $match))
			{
				$row_start = trim($match['1']);
			
				$row_chunk = trim(str_replace ($match['0'], "", $row_chunk));
			}
			
			if (preg_match("/".LD."row_end".RD."(.*?)".LD.SLASH."row_end".RD."/s", $row_chunk, $match))
			{
				$row_end = trim($match['1']);
			
				$row_chunk = trim(str_replace($match['0'], "", $row_chunk));
			}
						
            foreach ($TMPL->var_cond as $key => $val)
            {
				if ($val['3'] == 'today')
				{
					$if_today = trim($val['2']);
					
					$row_chunk = str_replace ($val['1'], $if_today_m, $row_chunk);
					
					unset($TMPL->var_cond[$key]);
				}
            
				if ($val['3'] == 'entries')
				{
					$if_entries = trim($val['2']);
					
					$row_chunk = str_replace ($val['1'], $if_entries_m, $row_chunk);
					
					unset($TMPL->var_cond[$key]);
				}
		
				if ($val['3'] == 'not_entries')
				{
					$if_not_entries = trim($val['2']);
					
					$row_chunk = str_replace ($val['1'], $if_not_entries_m, $row_chunk);
					
					unset($TMPL->var_cond[$key]);
				}
				
				if ($val['3'] == 'blank')
				{
					$if_blank = trim($val['2']);
					
					$row_chunk = str_replace ($val['1'], $if_blank_m, $row_chunk);
					
					unset($TMPL->var_cond[$key]);
				}
				
				if (preg_match("/".LD."entries".RD."(.*?)".LD.SLASH."entries".RD."/s", $if_entries, $match))
				{
					$entries = trim($match['1']);
				
					$if_entries = trim(str_replace($match['0'], $entries_m, $if_entries));
				}
				
			}		
				
			$TMPL->tagdata = preg_replace ("/".LD."calendar_rows".RD.".*?".LD.SLASH."calendar_rows".RD."/s", $row_chunk_m, $TMPL->tagdata);
		}
		
        /** ----------------------------------------
        /**  Fetch {switch} variable
        /** ----------------------------------------*/
        
        // This variable lets us use a different CSS class
        // for the current day
				
		$switch_t = '';
		$switch_c = '';
					
		if ($TMPL->fetch_param('switch'))
		{
			$x = explode("|", $TMPL->fetch_param('switch'));
			
			if (count($x) == 2)
			{
				$switch_t = $x['0'];
				$switch_c = $x['1'];
			}
		}
		
		/** ---------------------------------------
		/**  Set the day number numeric format
		/** ---------------------------------------*/
		
		$day_num_fmt = ($TMPL->fetch_param('leading_zeroes') == 'yes') ? "%02d" : "%d";
		
        /** ----------------------------------------
        /**  Build the SQL query
        /** ----------------------------------------*/
				
        $this->initialize();
                
        $this->tagparams['rdf']	= 'off';
        
		$this->build_sql_query('/'.$year.'/'.$month.'/');
		
		if ($this->sql != '')
		{
			$query = $DB->query($this->sql);

			$data = array();
			
			if ($query->num_rows > 0)
			{  			
				// We'll need this later
			
				if ( ! class_exists('Typography'))
				{
					require PATH_CORE.'core.typography'.EXT;
				}
						
				$TYPE = new Typography;   
			 	$TYPE->convert_curly = FALSE;
			 
				/** ----------------------------------------
				/**  Fetch query results and build data array
				/** ----------------------------------------*/
	
				foreach ($query->result as $row)
				{
				
					/** ----------------------------------------
					/**  Adjust dates if needed
					/** ----------------------------------------*/
					
					// If the "dst_enabled" item is set in any given entry
					// we need to offset to the timestamp by an hour
					                
				if ($row['entry_date'] != '')
					$row['entry_date'] = $LOC->offset_entry_dst($row['entry_date'], $row['dst_enabled'], FALSE);
	
					/** ----------------------------------------
					/**  Define empty arrays and strings
					/** ----------------------------------------*/
					
					$defaults = array(
										'entry_date'					=> 'a',
										'permalink'						=> 'a',
										'title_permalink'				=> 'a',
										'author'						=> 's',
										'profile_path'					=> 'a',
										'id_path'						=> 'a',
										'base_fields' 					=> 'a',
										'comment_tb_total'				=> 's',
										'day_path'						=> 'a',
										'comment_auto_path'				=> 's',
										'comment_entry_id_auto_path'	=> 's',
										'comment_url_title_auto_path'	=> 's'
										);
										
										
					foreach ($defaults as $key => $val)
					{
						$$key = ($val == 'a') ? array() : '';
					}				
	
					/** ---------------------------
					/**  Single Variables
					/** ---------------------------*/
						
					foreach ($TMPL->var_single as $key => $val)
					{ 
						if (isset($entry_dates[$key]))
						{
							foreach ($entry_dates[$key] as $dvar)
								$val = str_replace($dvar, $LOC->convert_timestamp($dvar, $row['entry_date'], TRUE), $val);					
		
							$entry_date[$key] = $val;		
						}
						
						
						/** ----------------------------------------
						/**  parse permalink
						/** ----------------------------------------*/
						
						if (strncmp('permalink', $key, 9) == 0)
						{                     
							if ($FNS->extract_path($key) != '' AND $FNS->extract_path($key) != 'SITE_INDEX')
							{
								$path = $FNS->extract_path($key).'/'.$row['entry_id'];
							}
							else
							{
								$path = $row['entry_id'];
							}
						
							$permalink[$key] = $FNS->create_url($path, 1);
						}
						
						/** ----------------------------------------
						/**  parse title permalink
						/** ----------------------------------------*/
						
						if (strncmp('title_permalink', $key, 15) == 0 OR strncmp('url_title_path', $key, 14) == 0)
						{ 
							if ($FNS->extract_path($key) != '' AND $FNS->extract_path($key) != 'SITE_INDEX')
							{
								$path = $FNS->extract_path($key).'/'.$row['url_title'];
							}
							else
							{
								$path = $row['url_title'];
							}
								
							$title_permalink[$key] = $FNS->create_url($path, 1);
								
						}
						
						/** ----------------------------------------
		                /**  {comment_auto_path}
		                /** ----------------------------------------*/

		                if ($key == "comment_auto_path")
		                {           
		                	$comment_auto_path = ($row['comment_url'] == '') ? $row['blog_url'] : $row['comment_url'];
		                }

		                /** ----------------------------------------
		                /**  {comment_url_title_auto_path}
		                /** ----------------------------------------*/

		                if ($key == "comment_url_title_auto_path")
		                { 
		                	$path = ($row['comment_url'] == '') ? $row['blog_url'] : $row['comment_url'];
							$comment_url_title_auto_path = $path.$row['url_title'].'/';
		                }

		                /** ----------------------------------------
		                /**  {comment_entry_id_auto_path}
		                /** ----------------------------------------*/

		                if ($key == "comment_entry_id_auto_path")
		                {           
		                	$path = ($row['comment_url'] == '') ? $row['blog_url'] : $row['comment_url'];
							$comment_entry_id_auto_path = $path.$row['entry_id'].'/';
		                }
			
						/** ----------------------------------------
						/**  {author}
						/** ----------------------------------------*/
						
						if ($key == "author")
						{
							$author = ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'];
						}
						/** ----------------------------------------
						/**  profile path
						/** ----------------------------------------*/
						
						if (strncmp('profile_path', $key, 12) == 0)
						{                       
							$profile_path[$key] = $FNS->create_url($FNS->extract_path($key).'/'.$row['member_id']);
						}
				
						/** ----------------------------------------
						/**  parse comment_path or trackback_path
						/** ----------------------------------------*/
						
						if (preg_match("#^(comment_path|trackback_path|entry_id_path)#", $key))
						{                       
							$id_path[$key] = $FNS->create_url($FNS->extract_path($key).'/'.$row['entry_id']);
						}
						
						/** ----------------------------------------
						/**  parse {comment_tb_total}
						/** ----------------------------------------*/
						
						if ($key == "comment_tb_total")
						{        
							$comment_tb_total = $row['comment_total'] + $row['trackback_total'];					
						}
						
						/** ----------------------------------------
						/**  Basic fields (username, screen_name, etc.)
						/** ----------------------------------------*/
						 
						if (isset($row[$val]))
						{                    
							$base_fields[$key] = $row[$val];
						}
						
						/** ----------------------------------------
						/**  {day_path}
						/** ----------------------------------------*/
						
						if (strncmp('day_path', $key, 8) == 0)
						{               
							$d = date('d', $LOC->set_localized_time($row['entry_date']));
							$m = date('m', $LOC->set_localized_time($row['entry_date']));
							$y = date('Y', $LOC->set_localized_time($row['entry_date']));
											
							if ($FNS->extract_path($key) != '' AND $FNS->extract_path($key) != 'SITE_INDEX')
							{
								$path = $FNS->extract_path($key).'/'.$y.'/'.$m.'/'.$d;
							}
							else
							{
								$path = $y.'/'.$m.'/'.$d;
							}
							
							$if_entries = str_replace(LD.$key.RD, LD.'day_path'.$val.RD, $if_entries);
							$day_path[$key] = $FNS->create_url($path, 1);
						}
						
					}
					// END FOREACH SINGLE VARIABLES
					
				
					/** ----------------------------------------
					/**  Build Data Array
					/** ----------------------------------------*/
					
					$d = date('d', $LOC->set_localized_time($row['entry_date']));
					
					if (substr($d, 0, 1) == '0')
					{
						$d = substr($d, 1);
					}
					
					$data[$d][] = array(
											$TYPE->parse_type($row['title'], array('text_format' => 'lite', 'html_format' => 'none', 'auto_links' => 'n', 'allow_img_url' => 'no')),
											$row['url_title'],
											$entry_date,
											$permalink,
											$title_permalink,
											$author,
											$profile_path,
											$id_path,
											$base_fields,
											$comment_tb_total,
											$day_path,
											$comment_auto_path,
											$comment_url_title_auto_path,
											$comment_entry_id_auto_path
										);
				
				} // END FOREACH 
			} // END if ($query->num_rows > 0)
		} // END if ($this->query != '')
		
        /** ----------------------------------------
        /**  Build Calendar Cells
        /** ----------------------------------------*/
        
        $out = '';  
        
		$today = getdate($LOC->set_localized_time($LOC->now));

		while ($day <= $total_days)
		{    	
			$out .= $row_start;       
			
			for ($i = 0; $i < 7; $i++)
			{    	        
				if ($day > 0 AND $day <= $total_days)
				{ 					
					if ($if_entries != '' AND isset($data[$day]))
					{					
						$out .= str_replace($if_entries_m, $this->var_replace($if_entries, $data[$day], $entries), $row_chunk);
						
						foreach($day_path as $k => $v)
						{
							$out = str_replace(LD.'day_path'.$k.RD, $data[$day]['0']['10'][$k], $out);
						}
					}
					else
					{
						$out .= str_replace($if_not_entries_m, $if_not_entries, $row_chunk);
					}
					
					$out = str_replace(LD.'day_number'.RD, sprintf($day_num_fmt, $day), $out);
					
					
					if ($day == $today["mday"] AND $month == $today["mon"] AND $year == $today["year"])
					{
						$out = str_replace(LD.'switch'.RD, $switch_t, $out);
					}
					else
					{
						$out = str_replace(LD.'switch'.RD, $switch_c, $out);
					}
				}
				else
				{
					$out .= str_replace($if_blank_m, $if_blank, $row_chunk);

					$out = str_replace(LD.'day_number'.RD, ($day <= 0) ? sprintf($day_num_fmt, $prev_total_days + $day) : sprintf($day_num_fmt, $day - $total_days), $out);
				}
				      	        
        	    $day++;
			}
    	    
			$out .= $row_end;   
		}
    	
		// Garbage collection
		
		$out = str_replace(array($entries_m,
								 $if_blank_m, 
								 $if_today_m, 
								 $if_entries_m,
								 $if_not_entries_m),
							'',
							$out);
    	
		return str_replace ($row_chunk_m, $out, $TMPL->tagdata);
    }
    /* END */
    
    
    /** ----------------------------------------
	/**  Replace Calendar Variables
	/** ----------------------------------------*/

    function var_replace($chunk, $data, $row = '')
    { 
		if ($row != '')
		{
			$temp = '';

			foreach ($data as $val)
			{
				$str = $row;
				
				$str = str_replace(array(LD.'title'.RD,
										 LD.'url_title'.RD,
										 LD.'author'.RD,
										 LD.'comment_tb_total'.RD,
										 LD.'comment_auto_path'.RD,
										 LD.'comment_url_title_auto_path'.RD,
										 LD.'comment_entry_id_auto_path'.RD),
									array($val['0'],
										  $val['1'],
										  $val['5'],
										  $val['9'],
										  $val['11'],
										  $val['12'],
										  $val['13']),
									$str);

				// Entry Date
				foreach ($val['2'] as $k => $v)
				{
					$str = str_replace(LD.$k.RD, $v, $str);
				}
				
				// Permalink
				foreach ($val['3'] as $k => $v)
				{
					$str = str_replace(LD.$k.RD, $v, $str);
				}
				
				// Title permalink
				foreach ($val['4'] as $k => $v)
				{
					$str = str_replace(LD.$k.RD, $v, $str);
				}
				
				// Profile path
				foreach ($val['6'] as $k => $v)
				{
					$str = str_replace(LD.$k.RD, $v, $str);
				}
				
				// ID path
				foreach ($val['7'] as $k => $v)
				{
					$str = str_replace(LD.$k.RD, $v, $str);
				}
				
				// Base Fields
				foreach ($val['8'] as $k => $v)
				{
					$str = str_replace(LD.$k.RD, $v, $str);
				}
			
				// Day path
				foreach ($val['10'] as $k => $v)
				{
					$str = str_replace(LD.$k.RD, $v, $str);
				}
				
				$temp .= $str;
			}
			
			$chunk = str_replace('Gm983TGxkedSPoe0912NNk', $temp, $chunk);
		}
					
		return $chunk;
    }
    /* END */
}
// END CLASS
?>