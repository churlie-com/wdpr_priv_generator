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
require_once dirname( __FILE__ ) . '/lib/wdpr_fieldconfig.php';
require_once dirname( __FILE__ ) . '/lib/wdpr_textgenerator.php';

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


	function wdpr_priv_generator_shortcode( $atts ) {
		global $post, $wdprpg_settings;
        $wdpr_fields=New wdpr_fieldconfig(dirname( __FILE__ ) . '/config/fields.ini');
		$return = '';
		$defaults=$wdpr_fields->get_defaults();
		extract( shortcode_atts( $defaults, $atts ) );

		$fields=$wdpr_fields->get_postfields();
		if(!isset($fields["submitted"])){
            $return=$wdpr_fields->show_form("wdpr_");
		} else {
            $wdpr_gen=New wdpr_textgenerator();
            $template="privacy";
            $lang=$fields["lang"];
            $ini_file=dirname( __FILE__ ) . "/lang/$template-$lang.ini";

			$return=$wdpr_gen->generate($ini_file,$fields);
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