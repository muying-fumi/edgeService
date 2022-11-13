<?php
function inOrEqualOrBetweenSQL($fd_name,$str){  //輸入一個逗號分隔的list、dash連接的兩個數(一個區間)、或是一個單一的數字，來產生sql where condition
    if (strpos($str,',')){  //str_contains() PHP8以後可用
        //逗號分隔的清單
        $tmp=explode(",",$str);
        foreach($tmp as $ele){
            if (!isSet($content))
                $content="$fd_name in (".quoteStr($ele);
            else
                $content.=",".quoteStr($ele);
        }
        return $content.")";
    }
    if (strpos($str,'-')){ //dash連接的兩個數(一個區間)
        $tmp=explode("-",$str,2);   //如果格式錯誤，如a-b-c，只取a、b，c會被忽略
        return $fd_name." between ".quoteStr($tmp[0])." and ".quoteStr($tmp[1]);
    }
    return $fd_name."=".quoteStr($str); //當成是一個數字(這邊不檢查其他錯誤情況)
}
/*
function likeSQL($fd_name,$str){   //產生sql的like語法，用來搜尋文字，$str內含有的*、?、%當作"任意"
    $str="%".$str."%";
    $str=quoteStr($str);    //最初的str是使用者輸入的，所以可能會有SQL injection的情況，*用quoteStr();來預防
    $str=str_replace("*","%",$str);     //*視為任意
    $str=str_replace("?","%",$str);     //?視為任意
    $str=str_replace("_","\_",$str);    //_為wildcard(指任意單一字元)，這邊視為純文字
    return "($fd_name LIKE $str)";
}
*/
?>