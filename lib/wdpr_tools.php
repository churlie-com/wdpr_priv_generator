<?php
/**
 * Created by PhpStorm.
 * User: forretp
 * Date: 15/05/2018
 * Time: 8:40
 */

class wdpr_tools {
    var $debug=false;
    var $t0=false;

    function __construct(){
        if(file_exists(dirname( __FILE__ ) . "/debug.txt")){
            $this->debug=true;
        };
        $this->t0=microtime(true);
        $dbt=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,3);
        $caller = isset($dbt[2]['function']) ? $dbt[2]['function'] : "?";
        $caller .= "::" . (isset($dbt[1]['function']) ? $dbt[1]['function'] : "?");
        $this->uniq=substr(sha1($caller),0,4);
    }

    function trace($information){
        if(!$this->debug) return true;
        $dbt=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
        $caller = isset($dbt[1]['function']) ? $dbt[1]['function'] : "";
        $t1=microtime(true);
        $ms=round(1000*($t1-$this->t0));
        if(is_array($information)){
            echo "<!-- [$caller @ $ms ms // $this->uniq] \n";
            print_r($information);
            echo " -->\n";
        } else {
            echo "<!-- [$caller @ $ms ms // $this->uniq] $information -->\n";
        }
    }

    function pick_ini($template,$lang,$answers=false,$fallback_lang="en"){
        $this->trace("start [$template] [$lang] [$answers]");
        $ini_dir=dirname(dirname( __FILE__ )) ."/lang";
        if($answers){
            $choice1="$ini_dir/$template.answers.$lang.ini";
            $choice2="$ini_dir/$template.answers.$fallback_lang.ini";
        } else {
            $choice1="$ini_dir/$template.questions.$lang.ini";
            $choice2="$ini_dir/$template.questions.$fallback_lang.ini";
        }
        if(file_exists($choice1)){
            $this->trace("found ini file " . basename($choice1));
            return $choice1;
        }
        if(file_exists($choice2)){
            $this->trace("found fallback ini file " . basename($choice2));
            return $choice2;
        }
        $this->trace("cannot find \n[$choice1] or \n[$choice2]");
        return false;
    }
}