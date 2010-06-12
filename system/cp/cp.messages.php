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
 File: cp.messages_cp.php
-----------------------------------------------------
 Purpose: Private Messages - CP Templates
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Messages_CP extends Messages {
 

    function Messages_CP()
    {
    }
    /* END */
    
	/** -----------------------------------
    /**  Success Message - CP
    /** -----------------------------------*/
    
    function message_success_cp()
    {
    	return <<<WHOA
    	
		<div class="box"><div class='success'>&nbsp;{lang:message}</div></div>

WHOA;
    
    }
    /* END */
    
    
	/** -----------------------------------
    /**  Error Message - CP
    /** -----------------------------------*/
    
    function message_error_cp()
    {
    	return <<<WHOA

<div class="alertHeadingCenter">{lang:heading}</div>
<div class='box'>
<div class='defaultCenter'>
{lang:message}
<br /><br />
<a href='javascript:history.go(-1)' style='text-transform:uppercase;'>&#171; {lang:back}</a>
</div>
</div>
</div>

WHOA;
    
    }
    /* END */
    
    
    
    /** -----------------------------------
    /**  Display Folder Template
    /** -----------------------------------*/
    
    function message_edit_folders_row_cp()
    {
    	return <<<WHOA
<tr>
<td  class='{style}' style='width:100%;'>
<input type='text' name='folder_{input:folder_id}' id='folder_{input:folder_id}' value='{input:folder_name}' size='20' maxlength='20' class='input'  /> <span class="highlight">{lang:required}</span>
</td>
</tr>
    
WHOA;
    
    }
    
    
	/** -----------------------------------
    /**  Edit Folders Form for CP
    /** -----------------------------------*/
    
    function message_edit_folders_cp()
    {	
    	return <<<DUDE
    
<div class='tablePad'>

{include:success_message}


{form:form_declaration:edit_folders}

<table class='tableBorder' border='0'  cellspacing='0' cellpadding='0' style='width:100%;' >
<tr>
<td class='tableHeading'>{lang:edit_folders}</td>
</tr>
<tr>
<td  class='tableCellOne' >
<div class='defaultBold'>{lang:folder_name}</div>
</td>
</tr>

{include:current_folders}
{include:new_folder}

</table>

<div class='box'>
<div class='highlight'>{lang:folder_directions}</div>
</div>

<div class='itemWrapperTop'>
<input  type='submit' class='submit' value='{lang:submit}'  />
</div>
</form>
</div>
    
DUDE;

    }
    /* END */
	
	
	/** ----------------------------------------
    /**  Folder Rows Template for CP
    /** ----------------------------------------*/

    function message_folder_rows_cp()
    {
    	global $DSP;
    	
    	$r = $DSP->tr().
    	 	 $DSP->table_qcell('{style}', $DSP->qdiv('defaultCenter','<strong style="font-size:14px;">{message_status}</strong>'), '5%').
    	 	 $DSP->table_qcell('{style}', $DSP->anchor('{message_url}', '{message_subject}'), '40%').
    	 	 $DSP->table_qcell('{style}', '{sender}', '25%').
    	 	 $DSP->table_qcell('{style}', '{message_date}', '25%').
    	 	 $DSP->table_qcell('{style}', $DSP->input_checkbox('toggle[]', '{msg_id}'), '5%').
    	 	 $DSP->tr_c();

		return $r;
	}
	/* END */
	
	
	/** ----------------------------------------
    /**  No Folder Rows Template for CP
    /** ----------------------------------------*/

    function message_no_folder_rows_cp()
    {
    	global $DSP;
    	
    	$r = $DSP->tr().
    	 	 $DSP->td('tableCellTwo', '100%', '5').
    	 	 $DSP->div('defaultCenter').
    	 	 $DSP->span('defaultBold').'{lang:no_messages}'.$DSP->span_c().
    	 	 $DSP->div_c().
    	 	 $DSP->td_c().
    	 	 $DSP->tr_c();

		return $r;
	}
	/* END */
	


	/** ----------------------------------------
    /**  Core Folder Template for CP
    /** ----------------------------------------*/

    function message_folder_cp()
    {
    	global $DSP, $LANG;
    	
    	$r = '{include:hidden_js}'.NL;
    	
    	$r .= $DSP->qdiv('tableHeading', '{lang:folder_name}');
    	
    	/** -------------------------------
    	/**  Stats and Secondary Menu
    	/** -------------------------------*/
    	
    	$r .= $DSP->table('border', '10', '0', '100%').
              $DSP->tr().
              $DSP->td('tablePad'); 
              
        $r .= $DSP->table('tableBorderNoBot', '0', '0', '300px').
              $DSP->tr().
              $DSP->td('tableCellOne', '100%', '3').
              $LANG->line('messages_percent_full').
              $DSP->td_c().
              $DSP->tr_c().
              $DSP->tr().
              $DSP->td('tableCellOne', '100%', '3').
              '<div style="width:{image:messages_graph:width}px; height:{image:messages_graph:height}px; background-color: #666699; border:1px solid #000;"></div>'.
              //'<img src="{image:messages_graph:url}" width="{image:messages_graph:width}" height="{image:messages_graph:height}" align="middle" alt="Percentange Graph" />'.
              $DSP->td_c().
              $DSP->tr_c().
              $DSP->tr().
              $DSP->table_qcell('tableCellOne', $LANG->line('zero_percent')).
              $DSP->table_qcell('tableCellOne', $DSP->div('defaultCenter').$LANG->line('fifty_percent').$DSP->div_c()).
              $DSP->table_qcell('tableCellOne', $DSP->div('defaultRight').$LANG->line('hundred_percent').$DSP->div_c()).
              $DSP->tr_c().
              $DSP->table_c();
                            
		$r .= $DSP->td_c().
			  $DSP->td('tablePad', '', '', '', 'top');
			  
		$r .= $DSP->div('defaultRight').
			  $DSP->span('defaultBold').
			  $DSP->anchor('{path:compose_message}', $LANG->line('compose_message')).
			  
			  $DSP->span_c().
			  $DSP->div_c().
			  $DSP->div('defaultRight').
			  $DSP->span('defaultBold'). 
			  $DSP->anchor('{path:erase_messages}', $LANG->line('erase_messages'), "onclick='if(!confirm(\"{lang:erase_popup}\")) return false;'").
			  
			  $DSP->span_c().
			  $DSP->div_c().
			  $DSP->div('defaultRight').BR.
			  $LANG->line('switch_folder').
			  '{include:folder_pulldown:change}'.
			  $DSP->div_c();
			  
			  
		$r .= $DSP->td_c().   
			  $DSP->tr_c().     
			  $DSP->table_c();

    	/** -------------------------------
    	/**  Folder Contents
    	/** -------------------------------*/
                
        $r .= '{form:form_declaration:modify_messages}';
      
        $r .= '{if paginate}'.
        	  $DSP->table('tablePad', '5', '0', '').
              $DSP->tr().
              $DSP->td().
        	  '{include:pagination_link}'.
        	  $DSP->td_c().
        	  $DSP->tr_c().
        	  $DSP->table_c().
        	  '{/if}';
        	  
        	  
        
        $r .= $DSP->table('tableBorderSides', '0', '', '100%');

		$r .= $DSP->tr().
			  $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', NBS), '5%').
              $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', '{lang:message_subject}'), '40%').
              $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', '{lang:message_sender}'), '25%').
              $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', '{lang:message_date}'), '25%').
              $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"")), '5%').
              $DSP->tr_c();
        $r .= NL.'{include:folder_rows}'.NL;
              
        $r .= $DSP->table_c();  
             
        $r .= '{if paginate}'.
        	  $DSP->table('tablePad', '5', '0', '').
              $DSP->tr().
              $DSP->td().
        	  '<div>{include:pagination_link}</div>'.
        	  $DSP->td_c().
        	  $DSP->tr_c().
        	  $DSP->table_c().
        	  '{/if}';
             
        $r .= NL.'{include:folder_pulldown:move}'.NL.
        	  NL.'{include:folder_pulldown:copy}'.NL;
              
        $r .= $DSP->qdiv('paddedTop',
        	  '{form:copy_button}'.
        	  '{form:move_button}'.
        	  '{form:delete_button}').
              $DSP->form_close();
              
        $r .= $DSP->qdiv('defaultRight', $DSP->qdiv('lightLinks', $LANG->line('messages_allowed_total').NBS));
        
        $r .= '{include:toggle_js}';
        		
		return $r;
	}
	/* END */
	


	/** -----------------------------
    /**  Perform Member search
    /** -----------------------------*/
    
    function search_members_cp()
    { 
		global $DSP, $DB, $LANG;
		
		$r  = '{form:form_declaration:do_member_search}';

        $r .= $DSP->heading('{lang:member_search}');
        
        $r .= '{if message}'.NL.
        	  $DSP->qdiv('highlight', '{include:message}').NL.
        	  '{/if}';
        
        $r .= $DSP->div('box');
                              
        $r .= $DSP->itemgroup(
        						$LANG->line('screen_name', 'screen_name'),
        						$DSP->input_text('screen_name', '', '35', '100', 'input', '100%')
        					 );
        					 
        $r .= $DSP->itemgroup(
        						$LANG->line('email', 'email'),
        						$DSP->input_text('email', '', '35', '100', 'input', '100%')
        					 );
                              
        $r .= $DSP->itemgroup(
        						$DSP->qdiv('defaultBold', $LANG->line('member_group'))
        					 );
                              
       	$r .= $DSP->input_select_header('group_id');
        
        $r .= $DSP->input_select_option('any', '{lang:any}');
                                
        $r .= '{include:member_group_options}';
        
        $r .= $DSP->input_select_footer();
        
        // END select list
        
        $r .= $DSP->div_c();
        
        $r .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit('{lang:submit}'));
                
        $r .= $DSP->form_close();
        
        return $r;
	}
	/* END */
	
	
	/** -----------------------------------
    /**  JavaScript for Move Button
    /** -----------------------------------*/
    
    function member_results_cp()
    {
    	global $DSP;
    	
    	$r = <<<EMT

<script type="text/javascript"> 
//<![CDATA[
        
function insert_name(name)
{
	if (opener.document.getElementById('submit_message').{which_field}.value != '')
	{
		opener.document.getElementById('submit_message').{which_field}.value += ', ';
	}
	
	opener.document.getElementById('submit_message').{which_field}.value += name;
}

//]]>
</script>
        
EMT;
        
        $r	.=	$DSP->table('tableBorder', '0', '', '100%').
             			$DSP->tr().
             			$DSP->td('tableHeading').
						'{lang:search_results}'.
						$DSP->td_c();
						
        
        $r .= '{include:search_results}';
        
        $r .= $DSP->table_c();
                
        $r .= '<div class="defaultCenter"><div class="highlight">{lang:insert_member_instructions}</div></div>';
        
        $r .= $DSP->qdiv('itemWrapper', BR.'<div align="center"><a href="{path:new_search_url}"><b>'.
        								'{lang:new_search}</b></a></div>'.
        								BR.'<div align="center"><a href="JavaScript:window.close();opener.document.getElementById(\'submit_message\').{which_field}.focus();"><b>'.
        								'{lang:close_window}</b></a></div>');
        
        return $r;
    }
    /* END */
    
  	/** ----------------------------------------
	/**  Member Search Results Row - CP
	/** ----------------------------------------*/

	function member_results_row_cp()
	{		
		return <<<DOT
		
		<tr><td class="tableCellOne">{item}</td></tr>
DOT;

	}
	/* END */
    
    
    
    
 	/** ----------------------------------------
	/**  Attachments CP
	/** ----------------------------------------*/

	function message_attachments_cp()
	{
		global $DSP;
		
		$r = $DSP->table('tableBorder');
		
		$row = array(array('class' => 'tableHeading', 
    					   'text'  => '{lang:file_name}',
    					   'width' => '40%'),
    					   
    				array('class'  => 'tableHeading', 
    					   'text'  => '{lang:file_size}',
    					   'width' => '30%'),
    				
    				array('class'  => 'tableHeading', 
    					   'text'  => '{lang:remove}',
    					   'width' => '30%')
    				);
	
		$r .= $DSP->table_row($row).NL.'{include:attachment_rows}';
		
		$r .= $DSP->table_c();
		
		/*
		$row = array(array('class' => 'tableCellOne', 
    					   'text'  => $DSP->qdiv('default', '{lang:total_attach_allowed}') ,
    					   'colspan' => '1'),
    					   
    				array('class'  => 'tableCellOne', 
    					   'text'  => $DSP->qdiv('default', '{lang:remaining_space}'),
    					   'colspan' => '2')
    				);
    	
    	$r .= $DSP->table_row($row);
    	*/
	
	
		return $r;
	}
	/* END */
	
	/** -------------------------------------
	/**  Submission Error Message - CP
	/** -------------------------------------*/

	function message_submission_error_cp()
	{
		return <<< EOF


<div class="alertHeadingCenter">{lang:error}</div>

<div class='box'>
<div class='defaultCenter'>
<div class='defaultBold'>{lang:error_message}</div>
</div>
</div>

EOF;

	}
	/* END */
	
	
	
	/** ----------------------------------------
	/**  Attachment Rows CP
	/** ----------------------------------------*/

	function message_attachment_rows_cp()
	{
		global $DSP;
	
		$row = array(array('class' => 'tableCellOne', 
    					   'text'  => '{input:attachment_name}'),
    					   
    				array('class'  => 'tableCellOne', 
    					   'text'  => '{input:attachment_size} {lang:file_size_unit}'),
    				
    				array('class'  => 'tableCellOne', 
    					   'text'  => $DSP->input_submit('{lang:remove}', 'remove[{input:attachment_id}]')),
    				);
	
		return $DSP->table_row($row);
	}
	/* END */
	
	/** ----------------------------------------
	/**  Attachment Links CP
	/** ----------------------------------------*/

	function message_attachment_link_cp()
	{
		global $DSP;
	
		$r =  $DSP->qdiv('default', 
						 '<img src="{path:image_url}marker_file.gif" width="9" height="9" border="0" alt="" title="File" /> '.
						 $DSP->anchor('{path:download_link}',
						 			  '{input:attachment_name} ({input:attachment_size} {lang:file_size_unit})'
						 			  ));
	
		return $r;  //attachment
	}
	/* END */
	
	
	/** -----------------------------------
    /**  Preview Template for CP
    /** -----------------------------------*/

    function preview_message_cp()
    {
    	global $LANG, $DB, $DSP;
    	
    	$r = $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'), FALSE);
    	
    	$row = array(array('class'   => 'tableHeadingAlt', 
    					   'text'    => '{lang:preview_message}'));
    	
    	$r .= $DSP->table_row($row);
    	
    	$row = array(array('class' => 'tableCellOne', 
    					   'text'  => '{include:parsed_message}')
    				);
    				
    	$r .= $DSP->table_row($row).$DSP->table_c();
    	
    	return $r;
    	
    }
    /* END */
    



 	/** -----------------------------------
    /**  Compose Template for CP
    /** -----------------------------------*/

    function message_compose_cp()
    {
    	global $LANG, $DB, $DSP, $FNS;
    	
    	$DSP->extra_header .= $this->compose_header_js().NL;
    	
    	$r =  '{include:hidden_js}'.NL.
    		  '{include:search_js}'.NL.
    		  '{include:spellcheck_js}'.NL.
    		  '{include:text_counter_js}'.NL.
    		  '{include:dynamic_address_book}';
    		  
    		  
    	$r .= <<<DOT
    	
<!-- Hidden Emoticon Popup -->

<div id="emoticons" class="box" style="position:absolute;visibility:hidden;">

<script type="text/javascript"> 
//<![CDATA[
function add_smiley(smiley)
{
	taginsert('other', " " + smiley + " ", '');
	emoticonspopup.hidePopup('emoticons');
	document.getElementById('submit_message').body.focus();
}
//]]>
</script>
DOT;

		$form_details = array('action'	=> '',
    					 	  'id'		=> 'upload',
    					 	  'secure'	=> ($this->allegiance == 'cp') ? FALSE : TRUE
    					 	  );  	
    	
		$r .= $FNS->form_declaration($form_details);
		
		$r .= $DSP->table_open(array('cellpadding' => '10', 'width' => '200px'));
		
		$r .= '{include:emoticons}';
		
		$r .= $DSP->table_c().$DSP->form_close();
		
		$r .= $DSP->qdiv('defaultCenter', $DSP->qdiv('defaultBold', '<a href="" onclick="emoticonspopup.hidePopup(); return false;">{lang:close_window}</a>'));

		$r .= $DSP->div_c();
		
		$r .= '

<script type="text/javascript"> 
//<![CDATA[
var emoticonspopup = new PopupWindow("emoticons");
emoticonspopup.offsetY=0;
emoticonspopup.offsetX=0;
emoticonspopup.autoHide();
//]]>
</script>
<!-- End Hidden Emoticon Popup -->
'; 		  

		$r .= '{include:submission_error}'.NL.'{include:preview_message}';
    		 
    	$r .= '{form:form_declaration:messages}';
    	
    	$r .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
    	
    	$row = array(array('class'   => 'tableHeading', 
    					   'colspan' => '2', 
    					   'text'    => '{lang:new_message}'));
    	
    	$r .= $DSP->table_row($row);
    	
    	$row = array(array('class' => 'tableCellTwo', 
    					   'width' => '25%', 
    					   'align' => 'right',
    					   'text'  => $DSP->qspan('defaultBold', '{lang:message_recipients}').NBS.'{include:search:recipients}'),
    					   
    				array('class'  => 'tableCellTwo', 
    					   'width' => '75%',
    					   'text'  => $DSP->input_textarea('recipients','{input:recipients}', '2','textarea','100%', "onkeyup='buddy_list(this.name);'").'<div id="recipients_buddies"></div>'),
    				);
    				
    	$r .= $DSP->table_row($row);
    	
    	$row = array(array('class' => 'tableCellTwo', 
    					   'width' => '25%', 
    					   'align' => 'right',
    					   'text'  => $DSP->qspan('defaultBold', '{lang:cc}').NBS.'{include:search:cc}'),
    					   
    				array('class'  => 'tableCellTwo', 
    					   'width' => '75%',
    					   'text'  => $DSP->input_textarea('cc','{input:cc}', '2', 'textarea','100%', "onkeyup='buddy_list(this.name);'")
    					   			  .'<div id="cc_buddies"></div>'),
    				);
    				
    	$r .= $DSP->table_row($row);
    	
    	$row = array(array('class' => 'tableCellTwo', 
    					   'width' => '25%', 
    					   'align' => 'right',
    					   'text'  => $DSP->qspan('defaultBold', '{lang:message_subject}')),
    					   
    				array('class'  => 'tableCellTwo', 
    					   'width' => '75%',
    					   'text'  => $DSP->input_text('subject','{input:subject}')),
    				);
    				
    	$r .= $DSP->table_row($row);
    	
    	$row = array(array('class' => 'tableCellTwo', 
    					   'width' => '25%', 
    					   'align' => 'right',
    					   'text'  => $DSP->qdiv('lightLinks', '{lang:guided}'.NBS.
    					   									   $DSP->input_radio('mode', 'guided', '', " onclick='setmode(this.value)'").
    					   									   NBS.'{lang:normal}'.NBS.
    					   									   $DSP->input_radio('mode', 'normal', '1', " onclick='setmode(this.value)'"))),
    					   
    				array('class'  => 'tableCellTwo', 
    					   'width' => '75%',
    					   'text'  => $DSP->qdiv('default','{include:html_formatting_buttons}'))
    				);
    				
    	$r .= $DSP->table_row($row);
    	
$font = <<<EOT
<select name="size" class="select" onchange="selectinsert(this, 'size')" >
<option value="0">{lang:size}</option>
<option value="1">{lang:small}</option>
<option value="3">{lang:medium}</option>
<option value="4">{lang:large}</option>
<option value="5">{lang:very_large}</option>
<option value="6">{lang:largest}</option>
</select>

<select name="color" class="select" onchange="selectinsert(this, 'color')">
<option value="0">{lang:color}</option>
<option value="blue">{lang:blue}</option>
<option value="green">{lang:green}</option>
<option value="red">{lang:red}</option>
<option value="purple">{lang:purple}</option>
<option value="orange">{lang:orange}</option>
<option value="yellow">{lang:yellow}</option>
<option value="brown">{lang:brown}</option>
<option value="pink">{lang:pink}</option>
<option value="gray">{lang:grey}</option>
</select>
EOT;
    	
    	$row = array(array('class' => 'tableCellTwo', 
    					   'width' => '25%', 
    					   'align' => 'right',
    					   'text'  => $DSP->qdiv('defaultBold', '{lang:font_formatting}')),
    					   
    				array('class'  => 'tableCellTwo', 
    					   'width' => '75%',
    					   'text'  => $DSP->qdiv('default',$font))
    				);
    				
    	$r .= $DSP->table_row($row);

    		  
    		  
    	$row = array(array('class'  => 'tableCellTwo', 
    					   'width'  => '25%', 
    					   'align'  => 'right',
    					   'valign' => 'top',
    					   'text'   => $DSP->qdiv('defaultBold', '{lang:message}').
    					   			   $DSP->qdiv('default', NL.BR.NL.
    					   			   			  $DSP->anchor('javascript:void(0);','{lang:smileys}', " onclick='dynamic_emoticons();return false;'"))),
    					   
    				array('class'  => 'tableCellTwo', 
    					   'width' => '75%',
    					   'text'  => $DSP->input_textarea('body','{input:body}', '20', 'textarea', '100%'," onkeydown='text_counter();' onkeyup='text_counter();'")),
    				);
    				
    	$r .= $DSP->table_row($row);
    	
    	$row = array(array('class'  => 'tableCellTwo', 
    					   'width'  => '25%', 
    					   'align'  => 'right',
    					   'valign' => 'top',
    					   'text'   => $DSP->qdiv('defaultBold', $DSP->qdiv('itemWrapper', '{lang:spell_check}'))),
    					   
    				array('class'   => 'tableCellTwo', 
    					   'width'  => '75%',
    					   'valign' => 'top',
    					   'text'   => $DSP->div('default').
    					   			   $DSP->div('itemWrapper').NBS.NBS.
    					   			   $DSP->anchor('javascript:void(0);', '{lang:check_spelling}', 'onclick="eeSpell.getResults(\'body\');return false;"').
    					   			   '<span id="spellcheck_hidden_body" style="visibility:hidden;">'.
    					   			   NBS.NBS.NBS.NBS.'|'.NBS.NBS.NBS.NBS.
    					   			   $DSP->anchor('javascript:void(0);', '{lang:save_spellcheck}', 'onclick="SP_saveSpellCheck();return false;"').
    					   			   NBS.NBS.NBS.NBS.'|'.NBS.NBS.NBS.NBS.
    					   			   $DSP->anchor('javascript:void(0);', '{lang:revert_spellcheck}', 'onclick="SP_revertToOriginal();return false;"').
    					   			   '</span>'.
    					   			   $DSP->div_c().$DSP->div_c().NL.NL.
    					   			   '<iframe src="{path:spellcheck_iframe}" width="100%" style="display:none;" id="spellcheck_frame_body" class="iframe" name="spellcheck_frame_body"></iframe>'.
    					   			   '<div id="spellcheck_popup" class="wordSuggestion" style="position:absolute;visibility:hidden;"></div>'
    					   			   )
    				);
			
		$r .= '{if spellcheck}'.NL.NL;	
    	$r .= $DSP->table_row($row);
    	$r .= '{/if}'.NL.NL;
    	
    	
    	
    	$row = array(array('class'  => 'tableCellTwo', 
    					   'width'  => '25%', 
    					   'align'  => 'right',
    					   'text'   => $DSP->qdiv('defaultBold', '{lang:characters}')),
    					   
    				array('class'   => 'tableCellTwo', 
    					   'width'  => '75%',
    					   'valign' => 'top',
    					   'text'   => '<input type="text" class="input" name="charsleft" size="5" maxlength="4" value="{lang:max_chars}" readonly="readonly" />')
    				);
    				
    	$r .= $DSP->table_row($row);
    	
    	
    	$row = array(array('class'  => 'tableCellTwo', 
    					   'width'  => '25%', 
    					   'align'  => 'right',
    					   'valign' => 'top',
    					   'text'   => $DSP->qdiv('defaultBold', '{lang:message_options}')),
    					   
    				array('class'   => 'tableCellTwo', 
    					   'width'  => '75%',
    					   'valign' => 'top',
    					   'text'   => $DSP->qdiv('', $DSP->input_checkbox('sent_copy','y', '', '{input:sent_copy_checked}').' {lang:sent_copy}').NL.
    					   			   //$DSP->qdiv('', $DSP->input_checkbox('tracking','y', '' ,'{input:tracking_checked}').' {lang:track_message}').NL.
    					   			   $DSP->qdiv('', $DSP->input_checkbox('hide_cc','y', '', '{input:hide_cc_checked}').' {lang:hide_cc}').NL),
    				);
    				
    	$r .= $DSP->table_row($row);
    	
    	$row = array(array('class'  => 'tableCellTwo', 
    					   'width'  => '25%', 
    					   'align'  => 'right',
    					   'valign' => 'top',
    					   'text'   => $DSP->qdiv('defaultBold', '{lang:attachments}').
    					   			   $DSP->qdiv('itemWrapper', $DSP->qdiv('lightLinks', '{lang:max_size}'.NBS.'{lang:max_file_size} KB'))),
    					   
    				array('class'   => 'tableCellTwo', 
    					   'width'  => '75%',
    					   'valign' => 'top',
    					   'text'   => $DSP->qdiv('', '<input type="file" name="userfile" size="20" class="input" />').
    					   			   $DSP->qdiv('lightLinks', '{lang:click_preview_to_attach}')),
    				);
    				
    	$r .= '{if attachments_allowed}'.NL.
    		  $DSP->table_row($row).
    		  '{/if}'.NL;
    		  
    	$row = array(array('class'  => 'tableCellTwo', 
    					   'width'  => '25%', 
    					   'align'  => 'right',
    					   'valign' => 'top',
    					   'text'   => $DSP->qdiv('defaultBold', '{lang:attachments}')),
    					   
    				array('class'   => 'tableCellTwo', 
    					   'width'  => '75%',
    					   'valign' => 'top',
    					   'text'   => $DSP->qdiv('', '{include:attachments}')),
    				);
    				
    	$r .= '{if attachments_exist}'.NL.
    		  $DSP->table_row($row).
    		  '{/if}'.NL;
    		  
    	$row = array(array('class'  => 'tableCellTwo', 
    					   'width'  => '25%', 
    					   'align'  => 'right',
    					   'valign' => 'top',
    					   'text'   => NBS),
    					   
    				array('class'   => 'tableCellTwo', 
    					   'width'  => '75%',
    					   'valign' => 'top',
    					   'text'   => $DSP->div('itemWrapper').
    					   			   $DSP->input_submit('{lang:preview_message}', 'preview').
    					   			   NBS.NBS.NBS.
    					   			   $DSP->input_submit('{lang:draft_message}', 'draft').
    					   			   NBS.NBS.NBS.
    					   			   $DSP->input_submit('{lang:send_message}', 'submit').
    					   			   $DSP->div_c()),
    				);
    	
    	$r .= $DSP->table_row($row).
    		  $DSP->table_c().
    		  $DSP->form_close();
    	
 	
 		return $r;
 	}
 	/* END */
 	
 	
 	
	/** -----------------------------------
	/**  View Message - CP
	/** -----------------------------------*/
	
	function view_message_cp()
	{	
		global $DSP;
	
		$r = '{form:form_declaration:view_message}'.NL.
			 '{include:hidden_js}'.NL.
			 '{include:folder_pulldown:move}'.NL.
			 '{include:folder_pulldown:copy}';

		$r .= $DSP->table('tableBorderNoBot', '3', '0', '100%').
			  $DSP->tr().
			  $DSP->td('', '100%', '','','middle');
		
		$r .= '{form:reply_button} {form:reply_all_button} {form:forward_button} {form:move_button} {form:copy_button} {form:delete_button}';
		
		$r .= $DSP->td_c().
			  $DSP->tr_c().
			  $DSP->table_c();
			  
		$r .= $DSP->table('tableBorder', '0', '0', '100%').
			  $DSP->tr().
			  $DSP->td('tableHeading', '', '2').
			  '{lang:private_message}'.
			  $DSP->td_c().
			  $DSP->tr_c();
			  
		$r .= $DSP->tr().
			  $DSP->td('tableCellTwoBold', '130px').
			  $DSP->qdiv('defaultRight', '{lang:message_sender}:').
			  $DSP->td_c().
			  $DSP->td('tableCellOne').
			  '{include:sender}'.
			  $DSP->td_c().
			  $DSP->tr_c();
			  
		$r .= $DSP->tr().
			  $DSP->td('tableCellTwoBold', '130px').
			  $DSP->qdiv('defaultRight', '{lang:message_subject}:').
			  $DSP->td_c().
			  $DSP->td('tableCellOne').
			  '{include:subject}'.
			  $DSP->td_c().
			  $DSP->tr_c();
			  
		$r .= $DSP->tr().
			  $DSP->td('tableCellTwoBold', '130px').
			  $DSP->qdiv('defaultRight', '{lang:message_date}:').
			  $DSP->td_c().
			  $DSP->td('tableCellOne').
			  '{include:date}'.
			  $DSP->td_c().
			  $DSP->tr_c();
			  
		$r .= $DSP->tr().
			  $DSP->td('tableCellTwoBold', '130px').
			  $DSP->qdiv('defaultRight', '{lang:message_recipients}:').
			  $DSP->td_c().
			  $DSP->td('tableCellOne').
			  '{include:recipients}'.
			  $DSP->td_c().
			  $DSP->tr_c();
			  
		$r .= '{if show_cc}'.NL.
			  $DSP->tr().
			  $DSP->td('tableCellTwoBold', '130px').
			  $DSP->qdiv('defaultRight', '{lang:cc}:').
			  $DSP->td_c().
			  $DSP->td('tableCellOne').
			  '{include:cc}'.
			  $DSP->td_c().
			  $DSP->tr_c().NL.
			  '{/if}';
			  
		$r .= '{if attachments_exist}'.NL.
			  $DSP->tr().
			  $DSP->td('tableCellTwoBold', '130px').
			  $DSP->qdiv('defaultRight', '{lang:attachments}:').
			  $DSP->td_c().
			  $DSP->td('tableCellOne').
			  '{include:attachment_links}'.
			  $DSP->td_c().
			  $DSP->tr_c().NL.
			  '{/if}';
			  
		$r .= $DSP->tr().
			  $DSP->td('tableCellPlain', '', '2').
			  $DSP->qdiv('itmeWrapper', '{include:parsed_message}<br />').
			  $DSP->td_c().
			  $DSP->tr_c().
			  $DSP->table_c();
			  
		$r .= $DSP->table('', '3', '0', '100%').
			  $DSP->tr().
			  $DSP->td('', '100%', '','','middle');
		
		$r .= $DSP->td_c().
			  $DSP->tr_c().
			  $DSP->table_c();
			  
		$r .= '</form>';
		
		return $r;
	
	}
	/* END */
	
	
	
	/** -----------------------------------
	/**  CP Template for Block and Buddy List
	/** -----------------------------------*/
    
    function buddies_block_list_cp()
    {
    	global $DSP;
    	
    	$r = '{include:toggle_js}'.NL.
    		 '{include:buddy_search_js}';
    	
    	/** -------------------------------
    	/**  List Contents
    	/** -------------------------------*/
                
        $r .= '{form:form_declaration:list}';

        $r .= $DSP->table('tableBorder', '0', '', '100%');
		$r .= $DSP->tr();
		$r .= $DSP->td('tableHeading', '', '3');
    	$r .= '{include:member_search} &nbsp;{lang:list_title}';
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();


		$r .= $DSP->tr().
              $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', '{lang:screen_name}'), '25%').
              $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', '{lang:member_description}'), '70%').
              $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"")), '5%').
              $DSP->tr_c();
              
        $r .= NL.'{include:list_rows}'.NL;
              
        $r .= $DSP->table_c();
        
		$r .= $DSP->qdiv('itemWrapper', $DSP->qdiv('defaultRight', '{form:add_button}'.NBS.NBS.'{form:delete_button}'));

             
        $r .= $DSP->form_close();

		return $r;

    }
    /* END */
    
    
    
	/** -----------------------------------
    /**  Block and Buddy List Rows - CP
    /** -----------------------------------*/
    
    function buddies_block_row_cp()
    {
    	global $DSP;
    	
    	$r = $DSP->tr().
    	 	 $DSP->table_qcell('{style}', $DSP->anchor('{path:send_pm}','{screen_name}'), '20%').
    	 	 $DSP->table_qcell('{style}', '{member_description}', '60%').
    	 	 $DSP->table_qcell('{style}', $DSP->input_checkbox('toggle[]', '{listed_id}'), '5%').
    	 	 $DSP->tr_c();

		return $r;
    }
    /* END */
    
    
	/** ----------------------------------------
    /**  Empty List Template for CP
    /** ----------------------------------------*/

    function empty_list_cp()
    {
    	global $DSP;
    	
    	$r = $DSP->tr().
    	 	 $DSP->td('tableCellTwo', '100%', '3').
    	 	 $DSP->div('defaultCenter').
    	 	 $DSP->span('defaultBold').'{lang:empty_list}'.$DSP->span_c().
    	 	 $DSP->div_c().
    	 	 $DSP->td_c().
    	 	 $DSP->tr_c();

		return $r;
	}
	/* END */
	
	
	/** -----------------------------------
	/**  Bulletin Board - CP
	/** -----------------------------------*/
    
	function bulletin_board_cp()
	{
		global $DSP;
		
		$r = '{if message}'.
			 $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.'{include:message}')).
			 '{/if}';
    	
        $r .= $DSP->table('tableBorder', '0', '', '100%');
		$r .= $DSP->tr();
		$r .= $DSP->td('tableHeading', '', '3');
    	$r .= '{lang:bulletin_board}';
		$r .= $DSP->td_c();
		$r .= $DSP->tr_c();

		$r .= '{if can_post_bulletin}'.
			  $DSP->tr().
              $DSP->table_qcell('tableCellOne', $DSP->qspan('defaultBold', $DSP->anchor('{path:send_bulletin}', '{lang:send_bulletin}')), '100%').
              $DSP->tr_c().
              '{/if}';
              
        $r .= '{if no_bulletins}'.
			  $DSP->tr().
              $DSP->table_qcell('tableCellOne', $DSP->qspan('defaultBold', '{lang:message_no_bulletins}'), '100%').
              $DSP->tr_c().
              '{/if}';
              
        $r .= '{if bulletins}'.
			  '{include:bulletins}'.
              '{/if}';
              
		$r .= '{if paginate}'.
			  $DSP->tr().
              $DSP->table_qcell('tableCellOne', '{include:pagination_link}', '100%').
              $DSP->tr_c().
              '{/if}';
              
        $r .= $DSP->table_c();

		return $r;
}
/* END */


// -----------------------------------
//  Single Bulletin
// -----------------------------------   
    
function bulletin_cp()
{
	global $DSP;
	
	$r = $DSP->tr().
		 $DSP->table_qcell('{style}',
						$DSP->qdiv('', 
						$DSP->qspan('defaultBold', "{lang:message_sender}:").NBS.'{bulletin_sender}'.
						BR.
						$DSP->qspan('defaultBold', "{lang:message_date}:").NBS.'{bulletin_date}'.BR.
						'{if can_delete_bulletin}'.
						$DSP->qspan('defaultBold', '{lang:delete_bulletin}:'.NBS.$DSP->anchor('{path:delete_bulletin}', '{lang:yes}', "onclick='if(!confirm(\"{lang:delete_bulletin_popup}\")) return false;'")).BR.
						'{/if}'.
						BR.
						$DSP->qdiv('itempadbig',  
									$DSP->input_textarea('bulletin_{bulletin_id}','{bulletin_message}', '8', 'textarea', '100%', "readonly='readonly'"))
					,
					'bulletin_div_{bulletin_id}'
					)).
		 $DSP->tr_c();
	
	return $r;
}
/* END */


//-------------------------------------
//  Bulletin Sending Form
//-------------------------------------

function bulletin_form_cp()
{
	global $DSP;
	
	$r  = '{form:form_declaration:sending_bulletin}';
		  
	$r .= '{if message}'.
		  $DSP->qdiv('successBox', $DSP->qdiv('success', NBS.'{include:message}')).
		  '{/if}';
	
	$r .= $DSP->table('tableBorder', '0', '10', '100%').
		  $DSP->tr().
		  $DSP->td('tableHeading', '', '2').
		  '{lang:send_bulletin}'.
		  $DSP->td_c().
		  $DSP->tr_c();
		  
	$s = $DSP->input_select_header('group_id').
		 '{group_id_options}'.
		 $DSP->input_select_footer();
				
	$r .= $DSP->tr();
	$r .= $DSP->table_qcell('tableCellOne', $DSP->qspan('defaultBold', '{lang:member_group}'), '20%');
	$r .= $DSP->table_qcell('tableCellOne', $s, '80%');
	$r .= $DSP->tr_c();
	
	$r .= $DSP->tr();
	$r .= $DSP->table_qcell('tableCellTwo', $DSP->qspan('defaultBold', '{lang:bulletin_message}'), '20%');
	$r .= $DSP->table_qcell('tableCellTwo', $DSP->input_textarea('bulletin_message', '', 10, 'textarea', '100%'), '80%');
	$r .= $DSP->tr_c();
	
	$r .= $DSP->tr();
	$r .= $DSP->table_qcell('tableCellOne', $DSP->qspan('defaultBold', '{lang:bulletin_date}'), '20%');
	$r .= $DSP->table_qcell('tableCellOne', $DSP->input_text('bulletin_date', '{input:bulletin_date}', '20', '50', 'input', '80%'), '80%');
	$r .= $DSP->tr_c();
	
	$r .= $DSP->tr();
	$r .= $DSP->table_qcell('tableCellTwo', $DSP->qspan('defaultBold', '{lang:bulletin_expires}'), '20%');
	$r .= $DSP->table_qcell('tableCellTwo', $DSP->input_text('bulletin_expires', '{input:bulletin_expires}', '20', '50', 'input', '80%'), '80%');
	$r .= $DSP->tr_c();
		
	$r .= $DSP->tr();
	$r .= $DSP->td('tableCellOne', '', '2');
	$r .= $DSP->qdiv('buttonWrapper', $DSP->input_submit('{lang:submit}'));
	$r .= $DSP->td_c();
	$r .= $DSP->tr_c();
		
	$r .= $DSP->table_c(); 

	$r .= $DSP->form_close();
	
	return $r;
}
/* END */
}
/* END */
?>