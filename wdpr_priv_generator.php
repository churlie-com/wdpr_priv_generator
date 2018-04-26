<?php
/*
Plugin Name: WDPR Privacy Policy Generator
Plugin URI:  
Description: [wdpr_priv_generator] shortcode
Version: 1.0
Author: pforret
Author URI: https://www.wdpr.eu
License: GPLv3
*/

define('WDPR_PRIV_GENERATOR_PLUGIN_VERSION', '1.0');

$wdprpg_settings = array(
	'version' => WDPR_PRIV_GENERATOR_PLUGIN_VERSION,
	'powered_by' => "\n".'<!-- www.wdpr.eu -->'."\n",
	'form_fields' => array(
		'language' => 'radio:EN/FR/NL:NL',
        'company_name' => 'text!:20',
        'url_website' => 'text!:20',
		'users_can_login' => 'checkbox',
		'visitors_can_comment' => 'checkbox',
		'has_newsletter' => 'checkbox',
		'day' => 'text:10:' . date("Y-m-d"),
		'Generate Privacy Statement' => 'submit',
	),
	'option_fields' => array(
		'language'	=>	'en',
	)
);

if ( !function_exists('wdpr_priv_generator_shortcode') ) {

	function wdpr_priv_generator_add_stylesheet() {
		wp_enqueue_style( 'wdpr_priv_generator_style', plugins_url( '/css/wdpr_priv_generator.css', __FILE__ ), false, WDPR_PRIV_GENERATOR_PLUGIN_VERSION, 'all' );
	}
	add_action('wp_enqueue_scripts', 'wdpr_priv_generator_add_stylesheet');
	
	
	function wdprpg_session_unique(){
		$variables=Array(
			date("Ymd"), // changes every day
			$_SERVER["REMOTE_ADDR"],
			$_SERVER["SERVER_ADDR"],
			$_SERVER["HTTP_ACCEPT_LANGUAGE"],
			$_SERVER["HTTP_USER_AGENT"]
			);
		$unique=substr(sha1(implode("-",$variables)),0,16);
		return $unique;
	}
	
	function wdprpg_show_form($prefix="wdpr_"){
		global $wdprpg_settings;
		
		$plen=strlen($prefix);
		$html="<form method='post'>\n";
		$fields=$wdprpg_settings["form_fields"];
		$fields["submitted"]="hidden:0:1";
		$unique=wdprpg_session_unique();
		$fields["unique"]="hidden:0:$unique";
		$has_submit=false;
		foreach($fields as $key => $val){
			$title=ucwords(str_replace("_"," ",$key));
			if($prefix){
				$key=$prefix . $key;
			}
			$parts=explode(":",$val);
			$type=$parts[0];
			$extra  =(isset($parts[1]) ? $parts[1] : "");
			$default=(isset($parts[2]) ? $parts[2] : "");
			$required=false;
			if(substr($type,-1,1)=="!"){
				$type=substr($type,0,-1);
				$required=true;
				$title.="<sup>*</sup>";
			}
			switch($type){
			case "radio":
				$choices=explode("/",$extra);
				$html.="<dt>$title</dt><dd>";
				foreach($choices as $choice){
					$label=ucwords(str_replace("_"," ",$choice));
					if($choice==$default){
						$html.="<nobr><input type='radio' name='$key' checked='checked' value='$choice'> $label</nobr> ";
					} else {
						$html.="<nobr><input type='radio' name='$key' value='$choice'> $label</nobr> ";
					}
				}
				$html.="</dd>\n";
				break;
				
			case "select":
				$choices=explode("/",$extra);
				$html.="<dt>$title</dt><dd><select name='$key'>";
				foreach($choices as $choice){
					$label=ucwords(str_replace("_"," ",$choice));
					$html.="<option value='$choice'>$label</option>";
				}
				$html.="</select></dd>\n";
				break;
				
			case "hidden":
				$html.="<input type='hidden' name='$key' value='$default' />\n";
				break;
				
			case "checkbox":
				$html.="<dt><input type='checkbox' name='$key' value='1' /> $title</dt>\n";
				break;

			case "submit":
				$html.="<dt></dt><dd><input type='submit' value='$title' /></dd>\n";
				$has_submit=true;
				break;

			case "text":
			default:
				$length=(int)$extra;
				if(!$length)	$length=25;
				$html.="<dt>$title</dt><dd><input type='text' name='$key' value='$default' length='$length' /></dd>\n";
			}
		}
		if(!$has_submit){
			$html.="<dt> </dt><dd><input type='submit' value='Submit' /></dd>\n";		
		}
		$html.="</form>";
		echo $html;
		return $html;
	}
	
	function wdprpg_get_postfields($prefix="wdpr_"){
		$plen=strlen($prefix);
		$return=Array();
		foreach($_POST as $key => $val){
			if($plen){
				if(substr($key,0,$plen) != $prefix){
					// skip field
					continue;
				} else {
					// strip prefix
					$key=substr($key,$plen);
				}
			}
			$return[$key]=$val;
		}
        $return["domain_name"]=$_SERVER["SERVER_NAME"]; // this server, for credits
        $return["generator_page"]=get_permalink(); // this page, for credits
		return $return;
	}
	
	function wdprpg_generate_article($title,$description){
		$html="";
		if($title){
			$html.="<h4 class='wdpr_article_title'>$title</h4>\n";
		}
		if($description){
			if(!is_array($description)){
				$descriptions=Array($description);
			} else {
				$descriptions=$description;
			}
			foreach($descriptions as $description){
				switch(true){
					case substr($description,0,2) == "* ":
					$description=substr($description,2);
					$html.="<li class='wdpr_article_li'>$description</li>\n";
					break;
					
					case substr($description,0,1) == "	": // tab
					$description=substr($description,1);
					$html.="<blockquote class='wdpr_article_quote'>$description</blockquote>\n";
					break;
					
					default:
					$html.="<p class='wdpr_article_text'>$description</p>\n";
				}
			}
		}
		return $html;
	}
	
	function wdprpg_replace_placeholders($html,$fields,$cleanup=false){
		foreach($fields as $key => $val){
			$placeholder="[[$key]]";
			$html=str_replace($placeholder,$val,$html);
		}
		if($cleanup){
			$html=preg_replace("/(\[\[\w*\]\])/","",$html);
		}
		return $html;
	}
	
	function wdprpg_generate_statement($fields){
		$lang=(isset($fields["language"]) ? $fields["language"] :  "EN");
		$inifile=plugin_dir_path( __FILE__ ) . "lang/wdpr_priv_generator-$lang.ini";
		if(!file_exists($inifile)){
			$lang="EN";
			$inifile=plugin_dir_path( __FILE__ ) . "lang/wdpr_priv_generator-$lang.ini";
		}
		if(!file_exists($inifile)){
			return(false);
		}
		$html="";
		$articles=parse_ini_file($inifile,true);
		foreach($articles as $article_id => $article){
			$title=(isset($article["title"]) ? $article["title"] : "");
			$description=(isset($article["description"]) ? $article["description"] : ""); 
			// can be 1 text or an array
			$filter_except=(isset($article["filter_except"]) ? $article["filter_except"] : "");
			$filter_only=(isset($article["filter_only"]) ? $article["filter_only"] : "");
			if($filter_except AND isset($fields[$filter_except]) AND $fields[$filter_except])	continue;
			if($filter_only AND isset($fields[$filter_only]) AND $fields[$filter_only])	continue;
			$html.=wdprpg_generate_article($title,$description);
		}
		echo "<div style='border: 1px solid #999; padding: 10px;'>\n";
		echo wdprpg_replace_placeholders($html,$fields);
		echo "</div>\n";
		
	}

	function wdpr_priv_generator_shortcode( $atts ) {
		global $post, $wdprpg_settings;
		$return = '';
		extract( shortcode_atts( $wdprpg_settings['option_fields'], $atts ) );

		$fields=wdprpg_get_postfields();
		if(!isset($fields["submitted"])){
			wdprpg_show_form();	
		} else {
			wdprpg_generate_statement($fields);
		}

		return $return;
	}
	add_shortcode( 'wdpr_priv_generator', 'wdpr_priv_generator_shortcode' );
}


if ( ! function_exists('wdpr_priv_generator_plugin_meta') ) {
	function wdpr_priv_generator_plugin_meta( $links, $file ) { // add links to plugin meta row
		if ( $file == plugin_basename( __FILE__ ) ) {
			$row_meta = array(
				'support' => '<a href="https://www.wdpr.eu" target="_blank"> ' . __( 'www.wdpr.eu', 'page-list' ) . '</a>',
			);
			$links = array_merge( $links, $row_meta );
		}
		return (array) $links;
	}
	add_filter( 'plugin_row_meta', 'wdpr_priv_generator_plugin_meta', 10, 2 );
}
