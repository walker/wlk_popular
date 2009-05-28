<?php

$plugin['version'] = '0.2';
$plugin['author'] = 'Walker Hamilton';
$plugin['author_uri'] = 'http://www.walkerhamilton.com';
$plugin['description'] = 'Logs articles for popularity.';

$plugin['type'] = 0;

@include_once('zem_tpl.php');

if (0) {
?>
# --- BEGIN PLUGIN HELP ---
h1. wlk_popular

h2. Installation

Since you can read this help, you have installed the plugin to txp.
Did you activate it?

h2. Usage

Place the @<txp:wlk_popular js="true" />@ tag in an article form or on a page that has a single article.

@<txp:wlk_popular_list />@ or @<txp:wlk_popular_list which="bottom" />@ returns an unordered list of the most helpful or least helpful.

The list tag automatically wraps each popular article in list item tags (li). This can be changes by setting wraptag="[tag]", etc.
You can change the number of articles returned by setting limit="[number]". This defaults to 5.

# --- END PLUGIN HELP ---
<?php
}

# --- BEGIN PLUGIN CODE ---
	function wlk_popular($atts) {
		global $prefs;
		global $thisarticle;
		
		extract(lAtts(array(
			'js' => (isset($prefs['wlk_popular_js']) && $prefs['wlk_popular_js']=='true')?'true':'false',
			'debug' => 'false'
		),$atts));
		
		//Grab this article's count of plus & minus
		if($thisarticle['thisid'])
		{
			if($js=='true') {
				return '
				<script type="text/javascript">
				<![CDATA[
					$(".wlk_helpfulminus").bind("click", function(e){
						jQuery.post("'.$hu.'/wlk_popular_javascript", {"article_id":"'.$thisarticle['thisid'].'"});
					});
				]]>
				</script>
				';
			} else {
				$results = safe_row('count', 'txp_wlk_popular', 'textpattern_id="'.addslashes($thisarticle['thisid']).'"');
				if(count($results)==0) { 
					safe_insert('txp_wlk_popular', "count='1',textpattern_id='".addslashes($thisarticle['thisid'])."'");
				} else {
					safe_update('txp_wlk_popular', "count='".($results['count']+1)."'", 'textpattern_id="'.addslashes($thisarticle['thisid']).'"');
				}
			}
		}
	}

	function wlk_popular_javascript($atts) {
		$results = safe_row('count', 'txp_wlk_popular', 'textpattern_id="'.addslashes($_POST['article_id']).'"');
		if(count($results)==0) { 
			safe_insert('txp_wlk_popular', "count='1',textpattern_id='".addslashes($thisarticle['article_id'])."'");
		} else {
			safe_update('txp_wlk_popular', "count='".($results['count']+1)."'", 'textpattern_id="'.addslashes($thisarticle['article_id']).'"');
		}
	}
	
	function wlk_popular_list($atts)
	{
		global $prefs;
		global $permlink_mode;
		
		safe_query('CREATE TABLE IF NOT EXISTS `'.safe_pfx('txp_wlk_popular').'` (
			`id` int(11) NOT NULL auto_increment,
			`textpattern_id` int(11) NOT NULL,
			`count` int(11) NOT NULL,
			PRIMARY KEY  (`id`)
			)');
		
		
		extract(lAtts(array(
			'which' => (isset($prefs['wlk_popular_list_which']) && $prefs['wlk_popular_list_which']=='bottom')?'bottom':'top',
			'wraptag' => (!empty($prefs['wlk_popular_wraptag']))?$prefs['wlk_popular_wraptag']:'li',
			'order' => (isset($prefs['wlk_popular_list_which']) && $prefs['wlk_popular_list_which']=='bottom')?'ASC':'DESC',
			'limit' => (isset($prefs['wlk_popular_list_limit']) && is_numeric($prefs['wlk_popular_list_limit']))?$prefs['wlk_popular_list_limit']:'5',
			'debug' => 'false'
		),$atts));

		//Grab the articles with the "top" or "bottom" count count
		$results = safe_query('SELECT txp.ID, txp.Title, txp.Section, txp.Posted, txp.url_title FROM txp_wlk_popular AS popular LEFT JOIN textpattern AS txp ON txp.ID=popular.textpattern_id ORDER BY count '.$order.' LIMIT 0, '.$limit);

		if(mysql_num_rows($results)>0)
		{
			$out = '';
			$results_r = array();
			while($row = mysql_fetch_assoc($results))
			{
				$article_array = $row;
				$article_array['permlink'] = permlinkurl($article_array);
				$results_r[] = $article_array;
			}
			//create the HTML
			foreach($results_r as $article)
			{
				$out .= "\r\t".'<'.$wraptag.'><a href="'.$article['permlink'].'">'.$article['Title'].'</a></'.$wraptag.'>';
			}
			$out .= "\r";
			//Return it
			return $out;
		} else {
			return '';
		}
	}
	
# --- END PLUGIN CODE ---
?>