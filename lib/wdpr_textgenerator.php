<?php
/**
 * Created by PhpStorm.
 * User: forretp
 * Date: 26/04/2018
 * Time: 12:09
 */

class wdpr_textgenerator
{


    function generate($ini_file,$fields){
        if(!file_exists($ini_file)){
            $this->trace("Cannot find template file [$ini_file]");
            return(false);
        }
        $html="";
        $articles=parse_ini_file($ini_file,true);
        foreach($articles as $article_id => $article){
            // filter_except=fieldname: check fieldname is not set
            $filter_except=(isset($article["filter_except"]) ? $article["filter_except"] : "");
            if($filter_except AND isset($fields[$filter_except]) AND $fields[$filter_except])	continue;

            // filter_only=fieldname: check fieldname is set
            $filter_only=(isset($article["filter_only"]) ? $article["filter_only"] : "");
            if($filter_only AND (!isset($fields[$filter_only]) OR !$fields[$filter_only]))	continue;

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
            $html.="<h4 class='wdpr_article_title'>$title</h4>\n";
        }
        if($paragraphs){
            if(!is_array($paragraphs)){
                $paragraphs=Array($paragraphs);
            }
            foreach($paragraphs as $paragraph){
                switch(true){
                    case substr($paragraph,0,2) == "* ":
                        $paragraph=substr($paragraph,2);
                        $html.="<p class='wdpr_article_text'>&bull; $paragraph</p>\n";
                        break;

                    case substr($paragraph,0,1) == "	": // tab
                        $paragraph=substr($paragraph,1);
                        $html.="<blockquote class='wdpr_article_quote'>$paragraph</blockquote>\n";
                        break;

                    default:
                        $html.="<p class='wdpr_article_text'>$paragraph</p>\n";
                }
            }
        }
        return $html;
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

    function trace($text){
        echo "<!-- $text -->\n";
    }
}