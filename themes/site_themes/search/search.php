<?php



$template_matrix = array(
							array('search_index',		'webpage'),
							array('results',		'webpage'),
							array('search_css',		'css')
						);



function search_index()
{
return <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{lang}" lang="{lang}">

<head>
<title>{lang:search}</title>

<meta http-equiv="content-type" content="text/html; charset={charset}" />

<link rel='stylesheet' type='text/css' media='all' href='{stylesheet=search/search_css}' />
<style type='text/css' media='screen'>@import "{stylesheet=search/search_css}";</style>

</head>
<body>

<div id='pageheader'>
<div class="heading">{lang:search_engine}</div>
</div>

<div id="content">

<div class='breadcrumb'>
<span class="defaultBold">&nbsp; <a href="{homepage}">{site_name}</a>&nbsp;&#8250;&nbsp;&nbsp;{lang:search}</span>
</div>

<div class='outerBorder'>
<div class='tablePad'>

{exp:search:advanced_form result_page="search/results" cat_style="nested"}

<table cellpadding='4' cellspacing='6' border='0' width='100%'>
<tr>
<td width="50%">

<fieldset class="fieldset">
<legend>{lang:search_by_keyword}</legend>

<input type="text" class="input" maxlength="100" size="40" name="keywords" style="width:100%;" />

<div class="default">
<select name="search_in">
<option value="titles" selected="selected">{lang:search_in_titles}</option>
<option value="entries">{lang:search_in_entries}</option>
<option value="everywhere" >{lang:search_everywhere}</option>
</select>

</div>

<div class="default">
<select name="where">
<option value="exact" selected="selected">{lang:exact_phrase_match}</option>
<option value="any">{lang:search_any_words}</option>
<option value="all" >{lang:search_all_words}</option>
<option value="word" >{lang:search_exact_word}</option>
</select>
</div>

</fieldset>

<div class="default"><br /></div>

<table cellpadding='0' cellspacing='0' border='0'>
<tr>
<td valign="top">

<div class="defaultBold">{lang:weblogs}</div>

<select id="weblog_id" name='weblog_id[]' class='multiselect' size='12' multiple='multiple' onchange='changemenu(this.selectedIndex);'>
{weblog_names}
</select>

</td>
<td valign="top" width="16">&nbsp;</td>
<td valign="top">

<div class="defaultBold">{lang:categories}</div>

<select name='cat_id[]' size='12'  class='multiselect' multiple='multiple'>
<option value='all' selected="selected">{lang:any_category}</option>
</select>

</td>
</tr>
</table>



</td><td width="50%" valign="top">


<fieldset class="fieldset">
<legend>{lang:search_by_member_name}</legend>

<input type="text" class="input" maxlength="100" size="40" name="member_name" style="width:100%;" />
<div class="default"><input type="checkbox" class="checkbox" name="exact_match" value="y"  /> {lang:exact_name_match}</div>

</fieldset>

<div class="default"><br /></div>


<fieldset class="fieldset">
<legend>{lang:search_entries_from}</legend>

<select name="date" style="width:150px">
<option value="0" selected="selected">{lang:any_date}</option>
<option value="1" >{lang:today_and}</option>
<option value="7" >{lang:this_week_and}</option>
<option value="30" >{lang:one_month_ago_and}</option>
<option value="90" >{lang:three_months_ago_and}</option>
<option value="180" >{lang:six_months_ago_and}</option>
<option value="365" >{lang:one_year_ago_and}</option>
</select>

<div class="default">
<input type='radio' name='date_order' value='newer' class='radio' checked="checked" />&nbsp;{lang:newer}
<input type='radio' name='date_order' value='older' class='radio' />&nbsp;{lang:older}
</div>

</fieldset>

<div class="default"><br /></div>

<fieldset class="fieldset">
<legend>{lang:sort_results_by}</legend>

<select name="orderby">
<option value="date" >{lang:date}</option>
<option value="title" >{lang:title}</option>
<option value="most_comments" >{lang:most_comments}</option>
<option value="recent_comment" >{lang:recent_comment}</option>
</select>

<div class="default">
<input type='radio' name='sort_order' class="radio" value='desc' checked="checked" /> {lang:descending}
<input type='radio' name='sort_order' class="radio" value='asc' /> {lang:ascending}
</div>
</fieldset>

</td>
</tr>
</table>


<div class='searchSubmit'>

<input type='submit' value='Search' class='submit' />

</div>

{/exp:search:advanced_form}

<div class='copyright'><a href="http://expressionengine.com/">Powered by ExpressionEngine</a></div>


</div>
</div>
</div>

</body>
</html>
EOF;
}
/* END */



function results()
{
return <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{lang}" lang="{lang}">

<head>
<title>{lang:search}</title>

<meta http-equiv="content-type" content="text/html; charset={charset}" />

<link rel='stylesheet' type='text/css' media='all' href='{stylesheet=search/search_css}' />
<style type='text/css' media='screen'>@import "{stylesheet=search/search_css}";</style>

</head>
<body>

<div id='pageheader'>
<div class="heading">{lang:search_results}</div>
</div>

<div id="content">

<table class='breadcrumb' border='0' cellpadding='0' cellspacing='0' width='99%'>
<tr>
<td><span class="defaultBold">&nbsp; <a href="{homepage}">{site_name}</a>&nbsp;&#8250;&nbsp;&nbsp;<a href="{path=search/index}">{lang:search}</a>&nbsp;&#8250;&nbsp;&nbsp;{lang:search_results}</span></td>
<td align="center"><span class="defaultBold">{lang:keywords} {exp:search:keywords}</span></td>
<td align="right"><span class="defaultBold">{lang:total_search_results} {exp:search:total_results}</span></td>
</tr>
</table>

<div class='outerBorder'>
<div class='tablePad'>

<table border="0" cellpadding="6" cellspacing="1" width="100%">
<tr>
<td class="resultHead">{lang:title}</td>
<td class="resultHead">{lang:excerpt}</td>
<td class="resultHead">{lang:author}</td>
<td class="resultHead">{lang:date}</td>
<td class="resultHead">{lang:total_comments}</td>
<td class="resultHead">{lang:recent_comments}</td>
</tr>

{exp:search:search_results switch="resultRowOne|resultRowTwo"}

<tr>
<td class="{switch}" width="30%" valign="top"><b><a href="{auto_path}">{title}</a></b></td>
<td class="{switch}" width="30%" valign="top">{excerpt}</td>
<td class="{switch}" width="10%" valign="top"><a href="{member_path=member/index}">{author}</a></td>
<td class="{switch}" width="10%" valign="top">{entry_date format="%m/%d/%y"}</td>
<td class="{switch}" width="10%" valign="top">{comment_total}</td>
<td class="{switch}" width="10%" valign="top">{recent_comment_date format="%m/%d/%y"}</td>
</tr>

{/exp:search:search_results}

</table>


{if paginate}

<div class='paginate'>

<span class='pagecount'>{page_count}</span>&nbsp; {paginate}

</div>

{/if}


</td>
</tr>
</table>

<div class='copyright'><a href="http://expressionengine.com/">Powered by ExpressionEngine</a></div>

</div>
</div>
</div>

</body>
</html>
EOF;
}
/* END */


function search_css()
{
return <<<EOF
body {
 margin:0;
 padding:0;
 font-family:Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:11px;
 color:#000;
 background-color:#fff;
}

a {
 text-decoration:none; color:#330099; background-color:transparent;
}
a:visited {
 color:#330099; background-color:transparent;
}
a:hover {
 color:#000; text-decoration:underline; background-color:transparent;
}

#pageheader {  
 background-color: #4C5286;
 border-top: 1px solid #fff;
 border-bottom: 1px solid #fff;
 padding:  20px 0 20px 0;
}

.heading {  
 font-family:		Georgia, Times New Roman, Times, Serif, Arial;
 font-size: 		16px;
 font-weight:		bold;
 letter-spacing:	.05em;
 color:			#fff;
 margin: 			0;
 padding:			0 0 0 28px;
}

#content {
 left:				0px;
 right:				10px;
 margin:			10px 25px 10px 25px;
 padding:			8px 0 0 0;
}

.outerBorder {
 border:		1px solid #4B5388;
}

.header {
 margin:			0 0 14px 0;
 padding:			2px 0 2px 0;
 border:			1px solid #000770;
 background-color:	#797EB8;
 text-align:		center;
}

h1 {
 font-family:		Georgia, Times New Roman, Times, Serif, Arial;
 font-size: 		20px;
 font-weight:		bold;
 letter-spacing:	.05em;
 color:				#fff;
 margin: 			3px 0 3px 0;
 padding:			0 0 0 10px;
}

p {
 font-family:	Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:		11px;
 font-weight:	normal;
 color:			#000;
 background:	transparent;
 margin: 		6px 0 6px 0;
}

.searchSubmit {
 font-family:       Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:         11px;
 color:             #000;
 text-align: center;
 padding:           6px 10px 6px 6px;
 border-top:        1px solid #4B5388;
 border-bottom:     1px solid #4B5388;
 background-color:  #C6C9CF;
}

.fieldset {
 border:        1px solid #999;
 padding: 10px;
}

.breadcrumb {
 margin:			0 0 10px 0;
 background-color:	transparent;
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:			10px;
}

.default, .defaultBold {
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:			11px;
 color:				#000;
 padding:			3px 0 3px 0;
 background-color:	transparent;
}

.defaultBold {
 font-weight:		bold;
}

.paginate {
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:			12px;
 font-weight: 		normal;
 letter-spacing:	.1em;
 padding:			10px 6px 10px 4px;
 margin:			0;
 background-color:	transparent;
}

.pagecount {
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:			10px;
 color:				#666;
 font-weight:		normal;
 background-color: transparent;
}

.tablePad {
 padding:			3px 3px 5px 3px;
 background-color:	#fff;
}

.resultRowOne {
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:			11px;
 color:				#000;
 padding:           6px 6px 6px 8px;
 background-color:	#DADADD;
}

.resultRowTwo {
 font-family:       Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:         11px;
 color:             #000;
 padding:           6px 6px 6px 8px;
 background-color:  #eee;
}

.resultHead {
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size: 		11px;
 font-weight: 		bold;
 color:				#000;
 padding: 			8px 0 8px 8px;
 border-bottom:		1px solid #999;
 background-color:	transparent;
}

.copyright {
 text-align:        center;
 font-family:       Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:         9px;
 color:             #999;
 margin-top:        15px;
 margin-bottom:     15px;
}


form {
 margin:            0;
 padding:           0;
 border:            0;
}
.hidden {
 margin:            0;
 padding:           0;
 border:            0;
}
.input {
 border-top:        2px solid #979AC2;
 border-left:       2px solid #979AC2;
 border-bottom:     1px solid #979AC2;
 border-right:      1px solid #979AC2;
 color:             #333;
 font-family:       Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:         11px;
 height:            1.7em;
 padding:           0;
 margin:        	0;
} 
.textarea {
 border-top:        2px solid #979AC2;
 border-left:       2px solid #979AC2;
 border-bottom:     1px solid #979AC2;
 border-right:      1px solid #979AC2;
 color:             #333;
 font-family:       Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:         11px;
 padding:           0;
 margin:        	0;
}
.select {
 background-color:  #fff;
 font-family:       Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:         11px;
 font-weight:       normal;
 letter-spacing:    .1em;
 color:             #333;
 margin-top:        2px;
 margin-bottom:     2px;
} 
.multiselect {
 border-top:        2px solid #979AC2;
 border-left:       2px solid #979AC2;
 border-bottom:     1px solid #979AC2;
 border-right:      1px solid #979AC2;
 background-color:  #fff;
 color:             #333;
 font-family:       Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:         11px;
 margin-top:        2px;
 margin-top:        2px;
} 
.radio {
 color:             transparent;
 background-color:  transparent;
 margin-top:        4px;
 margin-bottom:     4px;
 padding:           0;
 border:            0;
}
.checkbox {
 background-color:  transparent;
 color:				transparent;
 padding:           0;
 border:            0;
}
.submit {
 background-color:  #fff;
 font-family:       Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:         11px;
 font-weight:       normal;
 border-top:		1px solid #989AB6;
 border-left:		1px solid #989AB6;
 border-right:		1px solid #434777;
 border-bottom:		1px solid #434777;
 letter-spacing:    .1em;
 padding:           1px 3px 2px 3px;
 margin:        	0;
 background-color:  #6C73B4;
 color:             #fff;
}  
EOF;
}
/* END */


?>