<?php
/*
Plugin Name: WDPR Privacy Policy Generator
Plugin URI:  
Description: [wdpr_priv_generator] shortcode
Version: 1.1
Author: pforret
Author URI: https://www.wdpr.eu
License: GPLv3
*/
require_once dirname(__FILE__) . '/lib/wdpr_questions.php';
require_once dirname(__FILE__) . '/lib/wdpr_answers.php';
require_once dirname(__FILE__) . '/lib/wdpr_tools.php';

define('WDPR_PRIV_GENERATOR_PLUGIN_VERSION', '1.0');

$wdprpg_settings = array(
	'version' => WDPR_PRIV_GENERATOR_PLUGIN_VERSION,
	'powered_by' => "\n".'<!-- www.wdpr.eu -->'."\n",
);

if ( !function_exists('wdpr_priv_generator_shortcode') ) {

	function wdpr_priv_generator_add_stylesheet() {
		wp_enqueue_style( 'wdpr_priv_generator_style', plugins_url( '/css/wdpr_priv_generator.css', __FILE__ ), false, WDPR_PRIV_GENERATOR_PLUGIN_VERSION, 'all' );
	}
	add_action('wp_enqueue_scripts', 'wdpr_priv_generator_add_stylesheet');

    /********************************************************/
// Adding Dashicons in WordPress Front-end
    /********************************************************/
    function load_dashicons_front_end() {
        wp_enqueue_style( 'dashicons' );
    }
    add_action( 'wp_enqueue_scripts', 'load_dashicons_front_end' );

	function wdpr_priv_generator_shortcode( $atts ) {
		global $post, $wdprpg_settings;
		$tool=New wdpr_tools();
        $defaults=Array(
            "lang" => "en",
            "template"  => "privacy");
        extract( shortcode_atts( $defaults, $atts ) );
        $ini_file=$tool->pick_ini($template,$lang);
        //$ini_file=dirname( __FILE__ ) . "/lang/fields-$lang.ini";
        if(!file_exists($ini_file)){
            $return="<p class='notice notice-error'>Language [$lang] not supported (yet).</p>\n";
            return $return;
        }
        $wdpr_fields=New wdpr_questions($ini_file); // make this multi lingual later

		$fields=$wdpr_fields->get_postfields("wdpr_");
		$status="FILLIN";
		if($fields["_submitted"]){
			if($fields["_form_has_validation_errors"]){
				$status="ERRORS";
			} else {
				$status="PROCESS";
			}
		}
		switch($status){
			case "FILLIN":
                $tool->trace("FILL IN FORM");
                $return=$wdpr_fields->show_form("wdpr_");
                break;
			
			case "ERRORS":
                $tool->trace("VALIDATION ERRORS");
                $return=$wdpr_fields->show_validation_errors("wdpr_");
                break;

			case "PROCESS":
                $tool->trace("PROCESS FORM");
                $wdpr_gen=New wdpr_answers();
                $lang=$fields["lang"];
                $ini_file=$tool->pick_ini($template,$lang,true);
                if($ini_file){
                    $return=$wdpr_gen->generate($ini_file,$fields);
                } else {
                    $return="<!-- template/lang not found -->";
                }
                break;
			
		default:
            $tool->trace("NO MATCH");
			$return="<!-- NOT SURE WHAT TO DO -->";
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
