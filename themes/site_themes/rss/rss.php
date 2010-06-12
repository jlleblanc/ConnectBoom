<?php

$template_matrix = array(
							array('rss_2',	'rss'),
							array('atom',	'rss')
						);

//-------------------------------------
//	RSS 2.0
//-------------------------------------

function rss_2()
{

ob_start();

echo "{assign_variable:master_weblog_name=\"default_site\"}\n{exp:rss:feed weblog=\"{master_weblog_name}\"}\n\n";

echo '<?xml version="1.0" encoding="{encoding}"?>'."\n";

?>
<rss version="2.0"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
    xmlns:admin="http://webns.net/mvcb/"
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns:content="http://purl.org/rss/1.0/modules/content/">

    <channel>
    
    <title>{exp:xml_encode}{weblog_name}{/exp:xml_encode}</title>
    <link>{weblog_url}</link>
    <description>{weblog_description}</description>
    <dc:language>{weblog_language}</dc:language>
    <dc:creator>{email}</dc:creator>
    <dc:rights>Copyright {gmt_date format="%Y"}</dc:rights>
    <dc:date>{gmt_date format="%Y-%m-%dT%H:%i:%s%Q"}</dc:date>
    <admin:generatorAgent rdf:resource="http://expressionengine.com/" />
    
{exp:weblog:entries weblog="{master_weblog_name}" limit="10" rdf="off" dynamic_start="on" disable="member_data|trackbacks"}
    <item>
      <title>{exp:xml_encode}{title}{/exp:xml_encode}</title>
      <link>{title_permalink=site/index}</link>
      <guid>{title_permalink=site/index}#When:{gmt_entry_date format="%H:%i:%sZ"}</guid>
      <description>{exp:xml_encode}{summary}{body}{/exp:xml_encode}</description>
      <dc:subject>{exp:xml_encode}{categories backspace="1"}{category_name}, {/categories}{/exp:xml_encode}</dc:subject>
      <dc:date>{gmt_entry_date format="%Y-%m-%dT%H:%i:%s%Q"}</dc:date>
    </item>
{/exp:weblog:entries}
    
    </channel>
</rss>

{/exp:rss:feed}
<?php

$buffer = ob_get_contents();
ob_end_clean(); 
return $buffer;

}
/* END */



//-------------------------------------
//	Atom
//-------------------------------------

function atom()
{

ob_start();

echo '{assign_variable:master_weblog_name="default_site"}
{assign_variable:atom_feed_location="site/atom"}'."\n\n".
'{exp:rss:feed weblog="{master_weblog_name}"}'."\n\n";

echo '<?xml version="1.0" encoding="{encoding}"?>'."\n";

?>
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="{weblog_language}">

    <title type="text">{exp:xml_encode}{weblog_name}{/exp:xml_encode}</title>
    <subtitle type="text">{exp:xml_encode}{weblog_name}:{weblog_description}{/exp:xml_encode}</subtitle>
    <link rel="alternate" type="text/html" href="{weblog_url}" />
    <link rel="self" type="application/atom+xml" href="{path={atom_feed_location}}" />
    <updated>{gmt_edit_date format='%Y-%m-%dT%H:%i:%sZ'}</updated>
    <rights>Copyright (c) {gmt_date format="%Y"}, {author}</rights>
    <generator uri="http://expressionengine.com/" version="{version}">ExpressionEngine</generator>
    <id>tag:{trimmed_url},{gmt_date format="%Y:%m:%d"}</id>

{exp:weblog:entries weblog="{master_weblog_name}" limit="15" rdf="off" dynamic_start="on" disable="member_data|trackbacks"}
    <entry>
      <title>{exp:xml_encode}{title}{/exp:xml_encode}</title>
      <link rel="alternate" type="text/html" href="{url_title_path=site/index}" />
      <id>tag:{trimmed_url},{gmt_entry_date format="%Y"}:{relative_url}/{weblog_id}.{entry_id}</id>
      <published>{gmt_entry_date format="%Y-%m-%dT%H:%i:%sZ"}</published>
      <updated>{gmt_edit_date format='%Y-%m-%dT%H:%i:%sZ'}</updated>
      <author>
            <name>{author}</name>
            <email>{email}</email>
            {if url}<uri>{url}</uri>{/if}
      </author>
{categories}
      <category term="{exp:xml_encode}{category_name}{/exp:xml_encode}"
        scheme="{path=site/index}"
        label="{exp:xml_encode}{category_name}{/exp:xml_encode}" />{/categories}
      <content type="html"><![CDATA[
        {body} {extended}
      ]]></content>
    </entry>
{/exp:weblog:entries}

</feed>

{/exp:rss:feed}
<?php

$buffer = ob_get_contents();
ob_end_clean(); 
return $buffer;

}
/* END */



?>