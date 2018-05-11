<?php
/**
 * Created by PhpStorm.
 * User: forretp
 * Date: 26/04/2018
 * Time: 12:09
 */

class wdpr_textgenerator
{
    var $parno=0;

    function generate($ini_file,$fields){
        if(!file_exists($ini_file)){
            $this->trace("Cannot find template file [$ini_file]");
            return(false);
        }
		$this->trace("START OF wdpr_textgenerator::generate");
        $html="";
        $articles=parse_ini_file($ini_file,true);
        foreach($articles as $article_id => $article){
            if(!$this->check_filters("except",$article_id,$article,$fields))    continue;
            if(!$this->check_filters("only",$article_id,$article,$fields))    continue;

            $title=(isset($article["title"]) ? $article["title"] : "");
            $text=(isset($article["paragraph"]) ? $article["paragraph"] : "");
            $html.=$this->article_to_html($title,$text);
        }
        // template is built,  now replace all placeholders
        $html=$this->replace_tags($html,$fields);
        return $html;
    }

    private function article_to_html($title,$paragraphs){
        $html="";
        if($title){
            $this->parno++;
            $html.="<h4 class='wdpr_article_title'>$this->parno. $title</h4>\n";
        }
        if($paragraphs){
            if(!is_array($paragraphs)){
                $paragraphs=Array($paragraphs);
            }
            foreach($paragraphs as $paragraph){
                switch(true){
                    case substr($paragraph,0,2) == "* ":    // bullet
                        $paragraph=substr($paragraph,2);
                        $html.="<li class='wdpr_article_text'>$paragraph</li>\n";
                        break;

                    case substr($paragraph,0,1) == "	": // tab
                        $paragraph=substr($paragraph,1);
                        $html.="<blockquote class='wdpr_article_quote'>$paragraph</blockquote>\n";
                        break;

                    case substr($paragraph,0,2) == "//": // comment
                        $paragraph=substr($paragraph,2);
                        $html.="<p class='wdpr_article_comment'><span class='dashicons dashicons-warning'></span> &lsaquo; $paragraph &rsaquo;</p>\n";
                        break;

                    default:
                        $html.="<p class='wdpr_article_text'>$paragraph</p>\n";
                }
            }
        }
        return $html;
    }

    private function check_filters($type,$article_id,$article,$fields){
        switch(strtolower($type)){
            case "only":
                if(!isset($article["filter_only"])){
                    return true;
                }
                if(!$article["filter_only"]){
                    return true;
                }
                $this->trace("check_filters: [$article_id][$type]");
                $filters=$article["filter_only"];
                $this->trace($filters);
                if(!is_array($filters))  $filters=Array($filters);
                $allowed=false;
                foreach($filters as $filter){
                    if(strpos($filter,":")){
                        list($field_name,$value)=explode(":",$filter,2);
                    } else {
                        $field_name=$filter;
                        $value=false;
                    }
                    if($value){
                        if(isset($fields[$field_name]) AND $fields[$field_name]==$value) $allowed=true;
                    } else {
                        if(isset($fields[$field_name]) AND $fields[$field_name]) $allowed=true;
                    }
                }
                return $allowed;
                break;

            case "except":
                if(!isset($article["filter_except"])){
                    return true;
                }
                if(!$article["filter_except"]){
                    return true;
                }
                $this->trace("check_filters: [$article_id][$type]");
                $filters=$article["filter_except"];
                $this->trace($filters);
                if(!is_array($filters))  $filters=Array($filters);
                $allowed=true;
                foreach($filters as $filter){
                    if(strpos($filter,":")){
                        list($field_name,$value)=explode(":",$filter,2);
                    } else {
                        $field_name=$filter;
                        $value=false;
                    }
                    if($value){
                        if(isset($fields[$field_name]) AND $fields[$field_name]==$value) $allowed=false;
                    } else {
                        if(isset($fields[$field_name]) AND $fields[$field_name]) $allowed=false;
                    }
                }
                return $allowed;
                break;

            default:
                $this->trace("Skip filter - unknown type [$type]");
                return true;
        }
    }

    private function replace_tags($html,$fields,$cleanup=false){
        foreach($fields as $key => $val){
            $placeholder="[[$key]]";
            $html=str_replace($placeholder,$val,$html);
        }
        if($cleanup){
            // remove all unreplaced placeholders
            $html=preg_replace("/(\[\[\w*\]\])/","",$html);
        }
        return $html;
    }

    private function trace($information){
        if(is_array($information)){
            echo "<!--  ";
            print_r($information);
            echo "-->\n";
        } else {
            echo "<!--  $information -->\n";
        }
    }
}