<?php
/**
 * Created by PhpStorm.
 * User: forretp
 * Date: 26/04/2018
 * Time: 10:48
 */
require_once 'wdpr_tools.php';

class wdpr_questions
{
    var $initialised = false;
    var $fieldlist = Array();

 function __construct($inifile=false)
 {
     if(!$inifile){ return false; }
     if(!file_exists($inifile)){    return false;  }
     $this->fieldlist=parse_ini_file($inifile,true);
     $this->tool=New wdpr_tools();
     if(isset($this->fieldlist)){
         $this->initialised=true;
         return true;
     }
     return false;
 }
 
	function get_fields(){
		$fields=$this->fieldlist;
        $fields["_submitted"]=Array(
            "type" => "hidden",
            "default" => 1,
        );
        $fields["_today"]=Array(
            "type" => "hidden",
            "default" => date("Y-m-d"),
        );
		$fields["_unique"]=Array(
			"type" => "hidden",
			"default" => $this->form_session(),
		);
        //$this->tool->trace(array_keys($fields));
		return $fields;
	}

	function get_defaults(){
		if(!$this->initialised) return false;
		$list=Array();
		foreach($this->fieldlist as $fieldname => $fieldinfo){
			if(isset($fieldinfo["default"])){
				$list[$fieldname]=$fieldinfo["default"];
			}
		}
        $this->tool->trace($list);
		return $list;
	}
	
    function get_postfields($prefix="wdpr_"){
        $return=Array();
        $return["_form_has_validation_errors"]=0;
        $fields=$this->get_fields();
        foreach($fields as $fieldname => $fieldinfo){
            $key=$prefix . $fieldname;
            $val=(isset($_POST[$key]) ? $_POST[$key] : false);
            $required=(isset($fieldinfo["required"]) ? true : false);
            $type=(isset($fieldinfo["type"]) ? $fieldinfo["type"] : "text");
            if($required AND !$val){
                $return["_form_has_validation_errors"]=1;
            }
            switch($type){
                default:
                $return[$fieldname]=$val;
            }
        }
        //$return["domain_name"]=$_SERVER["SERVER_NAME"]; // this server, for credits
        $return["generator_page"]=get_permalink(); // this page, for credits
        $this->tool->trace($return);
        return $return;
    }

    function show_form($prefix="wdpr_"){
        $html="<form method='post'>\n";
        $fields=$this->get_fields();
        $has_submit=false;
        $html.="<dl>\n";
        foreach($fields as $key => $fieldinfo){
            $ftype=(isset($fieldinfo["type"]) ? $fieldinfo["type"] : "text");
            if($ftype=="submit"){
                $has_submit=true;
            }
            $foptions=(isset($fieldinfo["option"]) ? $fieldinfo["option"] : Array());
            $fdefault=(isset($fieldinfo["default"]) ? $fieldinfo["default"] : false);
            if($fdefault AND substr($fdefault,0,4) == "SRV:"){
                // short for $_SERVER
                $varname=substr($fdefault,4);
                $fdefault=$_SERVER[$varname];
            }
            $frequired=(isset($fieldinfo["required"]) ? true : false);
            $flabel=(isset($fieldinfo["label"]) ? $fieldinfo["label"] : false);
            $fdescr=(isset($fieldinfo["description"]) ? $fieldinfo["description"] : false);
            $fplace=(isset($fieldinfo["placeholder"]) ? $fieldinfo["placeholder"] : false);
            $html.=$this->form_field($prefix,$key,$ftype,$foptions,$fdefault,$frequired,$flabel,$fplace,$fdescr);
        }
        if(!$has_submit){
            $html.=$this->form_field($prefix,"","submit");
        }
        $html.="</dl>\n";
        $html.="</form>\n";
        return $html;
    }

    function show_validation_errors($prefix="wdpr_"){
        $fields=$this->get_fields();
        $values=$this->get_postfields($prefix);
        $html="<dl>";
        foreach($fields as $key => $fieldinfo){
            $ftype=(isset($fieldinfo["type"]) ? $fieldinfo["type"] : "text");
            if($ftype=="hidden")    continue;
            if($ftype=="submit")    continue;
            $title=(isset($fieldinfo["label"]) ? $fieldinfo["label"] : false);
            if(!$title){
                $title=ucwords(str_replace("_"," ",$key));
            }
            $required=(isset($fieldinfo["required"]) ? true : false);
            if($required AND !$values[$key]){
                $html.="<dt class='wdpr_form_dt'><i>$title</i></dt><dd class='wdpr_form_dd'>is a required field!</dd>\n";
                continue;
            }
            if($ftype=="url" and !$this->validate_field("url",$values[$key])){
                $html.="<dt class='wdpr_form_dt'><i>$title</i></dt><dd class='wdpr_form_dd'>is not a URL!</dd>\n";
                continue;
            }
            if($ftype=="email" and !$this->validate_field("email",$values[$key])){
                $html.="<dt class='wdpr_form_dt'><i>$title</i></dt><dd class='wdpr_form_dd'>is not an email!</dd>\n";
                continue;
            }
            if($ftype=="text" and !$this->validate_field("text",$values[$key])){
                $html.="<dt class='wdpr_form_dt'><i>$title</i></dt><dd class='wdpr_form_dd'>is not valid text field!</dd>\n";
                continue;
            }
        }
        $html.="</dl>";
        return $html;
    }

    private function validate_field($type,$value){
     $value=trim($value);
     if(!$value) return true;
     switch($type){
         case "url":
             if(preg_match("#^(https?://[\w\d\-\_\.]*\.[\w]*)$#",$value)) return true;
             if(preg_match("#^([\w\d\-\_\.]*\.[\w]*)$#",$value)) return true;
             return false;
             break;
         case "email":
             if(preg_match("#^([\w\d\-\_\.\+]*@[\w\d\-\_\.]*\.[\w]*)$#",$value)) return true;
             return false;
             break;

         default:
             if(preg_match("#([<>])#",$value)) return false;
             return true;
     }
    }
	
    private function form_field($prefix,$name,$type,$options=Array(),$default=false,$required=false,$title=false,$placeholder=false,$description=false){
     $html="";
     if(!$title){
         $title=ucwords(str_replace("_"," ",$name));
     }
     if($prefix){
         $key=$prefix . $name;
     } else {
         $key=$name;
     }
     if($required){
         $title="<sup style='color: red'>*</sup>$title";
     }
     switch($type){
         case "radio":
         case "yesno":
             $html.="<dt class='wdpr_form_dt'>$title</dt><dd class='wdpr_form_dd'>";
             if($type=="yesno" AND !$options){
                 $options=Array( "Y:Yes" , "N:No" );
             }
             foreach($options as $option){
                 if(strpos($option,":")){
                     list($value,$label)=explode(":",$option);
                 } else {
                     $value=$option;
                     $label=ucwords(str_replace("_"," ",$option));
                 }
                 if($value==$default){
                     $html.="<nobr><input type='radio' name='$key' checked='checked' value='$value'> $label</nobr> ";
                 } else {
                     $html.="<nobr><input type='radio' name='$key' value='$value'> $label</nobr> ";
                 }
             }
             if($description){
                 $html.=$this->description_html($description);
             }
             $html.="</dd>\n";
             break;

         case "select":
             $html.="<dt class='wdpr_form_dt'>$title</dt><dd class='wdpr_form_dd'><select name='$key'>\n";
             foreach($options as $choice){
				 if(strstr($choice,":")){
					list($val,$label)=explode(":",$choice,2);
					 $label=ucwords(str_replace("_"," ",$label));
				 } else {
					 $val=$choice;
					 $label=ucwords(str_replace("_"," ",$choice));
				 }
				 if($val==$default){
                     $html.="<option selected value='$val'>$label</option>\n";
                 } else {
                     $html.="<option value='$val'>$label</option>\n";
                 }
             }
             $html.="</select>\n";
             if($description){
                 $html.=$this->description_html($description);
             }
             $html.="</dd>\n";
             break;

         case "hidden":
             $html.="<input type='hidden' name='$key' value='$default' />\n";
             if($description){
                 $html.="<dd class='wdpr_form_dd'>" . $this->description_html($description) . "</dd>\n";
             }
             break;

         case "checkbox":
         case "boolean":
             if($default == "Y" OR $default > 0){
                 $html.="<dt class='wdpr_form_dt'><input type='checkbox' checked name='$key' value='1' /> $title</dt>\n";
             } else {
                 $html.="<dt class='wdpr_form_dt'><input type='checkbox' name='$key' value='1' /> $title</dt>\n";
             }
             if($description){
                 $html.="<dd class='wdpr_form_dd'>" . $this->description_html($description) . "</dd>\n";
             }
             break;

         case "submit":
             $html.="<dt class='wdpr_form_dt'></dt><dd class='wdpr_form_dd'><input type='submit' value='$title' />";
             if($description){
                 $html.=$this->description_html($description);
             }
             $html.="</dd>\n";
             break;

         case "explain":
             if(trim($title)){
                 $html.="<dt class='wdpr_form_dt'>$title</dt>";
             }
             if($description){
                 $html.="<dd class='wdpr_form_dd'>" . $this->description_html($description) . "</dd>\n";
             }
             break;

         case "chapter":
             $html.="<div style='background: #DDD; padding: 16px'>";
             if(trim($title)){
                 $html.="<span style='font-size: 2em'>$title</span>";
             }
             if($description){
                 $html.="<br /><i><small>$description</small></i>\n";
             }
             $html.="</div>";
             break;

         case "disclaimer":
             if(trim($title)){
                 $html.="<dt class='wdpr_form_dt' style='font-family: Lucida Console, Monaco, monospace'>$title</dt>";
             }
             if($description){
                 $html.="<dd style='padding: 5px; font-size: .75em; font-family: Lucida Console, Monaco, monospace'>" . $this->description_html($description) . "</dd>\n";
             }
             break;

		case "text":
		case "url":
		case "email":
		default:
             $html.="<dt class='wdpr_form_dt'>$title</dt><dd class='wdpr_form_dd'><input type='text' name='$key' placeholder='$placeholder' value='$default' />";
             if($description){
                 $html.=$this->description_html($description);
             }
             $html.="</dd>\n";
     }
     return $html;
 }

    private function form_session(){
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

    private function description_html($descriptions){
        if(!$descriptions){
            return false;
        }
        if(!is_array($descriptions)){
            $descriptions=Array($descriptions);
        }
        $html="";
        foreach($descriptions as $description){
            $html.="<div class='wdpr_form_description'>$description</div>\n";
        }
        return $html;
    }

}