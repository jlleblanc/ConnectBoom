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
 File: core.filebrowser.php
-----------------------------------------------------
 Purpose: File Browser class
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}



class File_Browser {

    var $upload_path	= "../uploads/";
    var $filelist		= array();
    var $ignore			= array();
    var $width   		= '';
    var $height  		= '';
    var $imgtype 		= '';
    var $images_only	= FALSE;
    var $cutoff_date	= FALSE;
    var $show_errors	= TRUE;
    var $recursive		= TRUE;
    
    var $skippable		= array();



    /** -------------------------------------
    /**  Constructor
    /** -------------------------------------*/

    function File_Browser()
    {
        global $DSP, $LANG, $PREFS;
    
        $LANG->fetch_language_file('filebrowser');
        
        // Files with these prefixes we will automatically assume are not images
        $this->skippable = array('mp2', 'mp3', 'm4a', 'm4p', 'asf', 'mov', 
        						'mpeg', 'mpg', 'wav', 'wma', 'wmv', 'aif', 
        						'aiff', 'movie', 'dvi', 'pdf', 'avi', 'flv', 'swf', 'm4v');
        
        
    }
    /* END */




    /** -------------------------------------
    /**  List of Files
    /** -------------------------------------*/

    function create_filelist($folder='')
    {
        global $IN, $DSP, $LANG;  
        
		$this->set_cutoff_date();
        
        $ignore = (count($this->ignore) > 0) ? TRUE : FALSE;        

		if ( ! $handle = @opendir($this->upload_path)) 
		{ 
			if ($this->show_errors == FALSE)
			{
				return FALSE;
			}
		
			return $DSP->error_message($LANG->line('path_does_not_exist'));
		}
		
		$filedatum = array();
		$fileorder = array();
		
		while (false !== ($file = @readdir($handle)))
		{
			if (is_file($this->upload_path.$file) && substr($file,0,1) != '.' && $file != "index.html")
			{
				if ($this->cutoff_date > 0)
				{
					if (@filemtime($this->upload_path.$file) < $this->cutoff_date)
						continue;
				}	
			
				$skip = FALSE;
				
				// ignore the file if the name sans extension ends in the string to be shunned
				if (sizeof($ignore) > 0)
				{
					foreach ($this->ignore as $shun)
					{
						$name = array_shift($temp = explode('.', $file));
						
						if (substr($name, - strlen($shun)) == $shun)
						{
							$skip = TRUE;
							continue;
						}
					}
				}
				
				if ($skip === TRUE)
				{
					continue;
				}
				
				$filedatum[] = array($file, $folder);
				$fileorder[] = $folder.$file;
			}
			elseif(@is_dir($this->upload_path.$file) && substr($file,0,1) != '.')
			{
				if ($this->recursive == TRUE)
				{
					$old_path = $this->upload_path;
					$this->upload_path = $this->upload_path.$file.'/';
					$this->create_filelist($folder.$file.'/');
					$this->upload_path = $old_path;
				}
			}
		}
		
		if (count($filedatum) > 0)
		{
			$filearray = array(); 
			
			natcasesort($fileorder);
			
			foreach($fileorder as $key => $value)
			{
				$filearray[$key] = $filedatum[$key];
			}
			
			unset($fileorder);
			unset($filedatum);
		
			foreach($filearray as $val)
			{
				if (FALSE === $this->image_properties($this->upload_path.$val['0']))
				{
					if ($this->images_only == TRUE)
						continue;
				
					$this->filelist[] = array('type' 	=> "other",
											  'name' 	=> $val['1'].$val['0'],
											  'folder'	=> $val['1']
											 );
				}
				else
				{
					if ($this->images_only == TRUE)
					{
						if ($this->imgtype != 1 AND $this->imgtype != 2 AND $this->imgtype != 3)
							continue;
					}
										
					$this->filelist[] = array('type' 	=> "image",
											  'name' 	=> $val['1'].$val['0'],
											  'folder'	=> $val['1'],
											  'width'   => $this->width,
											  'height'	=> $this->height,
											  'imgtype'	=> $this->imgtype
											 );    				
				} 
			}
		
		}
		
		
		closedir($handle);
		return true;
    }
    /* END */

    
    /** -------------------------------------
    /**  Sets a Unix time based on the parameter
    /** -------------------------------------*/

	function set_cutoff_date()
	{
		if ($this->cutoff_date == FALSE OR $this->cutoff_date < 1)
		{
			$this->cutoff_date = FALSE;
			return;
		}
	
		$min = ($this->cutoff_date > 1) ? $this->cutoff_date * (60*60*24) : 0;
		$midnight = mktime(0, 0, 0, date('m'), date('d'), date('Y'));		
		$this->cutoff_date = $midnight - $min;
	}
	/* END */
    
    
    /** -------------------------------------
    /**  Set upload directory path
    /** -------------------------------------*/

    function set_upload_path($path)
    {
		global $DSP, $LANG;

		if (substr($path, -1) != '/' AND substr($path, -1) != '\\')
		{
			$path .= '/';
		}
		
        if ( ! @is_dir($path))
        {
			if ($this->show_errors == FALSE)
			{
				return FALSE;
			}
		        
            return $DSP->error_message($LANG->line('path_does_not_exist'));
        }
        else
        {
            $this->upload_path = $path;
        }
    }
    /* END */
    

    /** -------------------------------------
    /**  Get image properties
    /** -------------------------------------*/

    function image_properties($file)
    {
  	  	if (function_exists('getimagesize')) 
        {
        	foreach($this->skippable as $suffix)
        	{
        		if (substr(strtolower($file), -strlen($suffix)) == $suffix)
        		{
        			return FALSE;
        		}
        	}
        
            if ( ! $D = @getimagesize($file))
            {
            	return FALSE;
            }
            
            $this->width   = $D['0'];
            $this->height  = $D['1'];
            $this->imgtype = $D['2'];
                       
            return TRUE;
        }

        return FALSE;
    }
    /* END */

}
// END CLASS
?>