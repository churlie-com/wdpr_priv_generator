<?php
/**
 * Created by PhpStorm.
 * User: forretp
 * Date: 26/04/2018
 * Time: 10:48
 */

class wdpr_fieldconfig
{
    var $initialised = false;
    var $data = Array();

 function __construct($inifile=false)
 {
     if(!$inifile){ return false; }
     if(!file_exists($inifile)){    return false;  }
     $this->data=parse_ini_file($inifile,true);
     if(isset($this->data)){
         $this->initialised=true;
         return true;
     }
     return false;
 }

 function get_defaults(){
     if(!$this->initialised) return false;
     $list=Array();
     foreach($this->data as $fieldname => $fieldinfo){
         if(isset($fieldinfo["default"])){
             $list[$fieldname]=$fieldinfo["default"];
         }
     }
     return $list;
 }
    function get_postfields($prefix="wdpr_"){
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
        echo "<!--\n";
        print_r($return);
        echo "-->\n";
        return $return;
    }

 function show_form($prefix="wdpr_"){
     $html="<form method='post'>\n";
     $fields=$this->data;
     $fields["submitted"]=Array(
         "type" => "hidden",
         "default" => 1,
     );
     $fields["unique"]=Array(
         "type" => "hidden",
         "default" => $this->form_session(),
     );
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
         $html.=$this->form_field($prefix,$key,$ftype,$foptions,$fdefault,$frequired,$flabel,$fdescr);
     }
     if(!$has_submit){
         $html.=$this->form_field($prefix,"","submit");
     }
     $html.="</dl>\n";
     $html.="</form>\n";
     return $html;
 }

 private function form_field($prefix,$name,$type,$options=Array(),$default=false,$required=false,$title=false,$description=false){
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
         $title.="<sup style='color: red'>*</sup>";
     }
     switch($type){
         case "radio":
             $html.="<dt>$title</dt><dd>";
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
                 $html.=" <br /><small>$description</small>";
             }
             $html.="</dd>\n";
             break;

         case "select":
             $html.="<dt>$title</dt><dd><select name='$key'>";
             foreach($options as $choice){
                 $label=ucwords(str_replace("_"," ",$choice));
                 $html.="<option value='$choice'>$label</option>";
             }
             $html.="</select>";
             if($description){
                 $html.=" <br /><small>$description</small>";
             }
             $html.="</dd>\n";
             break;

         case "hidden":
             $html.="<input type='hidden' name='$key' value='$default' />\n";
             break;

         case "checkbox":
         case "boolean":
             $html.="<dt><input type='checkbox' name='$key' value='1' /> $title</dt>\n";
             if($description){
                 $html.="<dd><small>$description</small></dd>\n";
             }
             break;

         case "submit":
             $html.="<dt></dt><dd><input type='submit' value='$title' />";
             if($description){
                 $html.=" <br /><small>$description</small>";
             }
             $html.="</dd>\n";
             break;

         case "text":
         default:
             $html.="<dt>$title</dt><dd><input type='text' name='$key' value='$default' />";
             if($description){
                 $html.=" <br /><small>$description</small>";
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
}