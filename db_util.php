<?php
define('FD_TEXTBOX',0);
define('FD_PASSWORD',1);
define('FD_TEXTAREA',2);
define('FD_CHECKBOX',3);
define('FD_HIDDEN',4);
define('FD_SELECTONE',5);

class DBTool {
  var $val;   // array returned by functions
  var $map;   // display or field type map (based on fieldname)

  // add a value (strings NOT escaped)
  function AddOwnVal($fd_name, $v) {
    if (is_null($v)) {
      $this->val[$fd_name]='NULL';
      $this->map[$fd_name]=2;
    }
    else if (is_numeric($v)) {
      $this->val[$fd_name]=$v;
      $this->map[$fd_name]=1;
    }
    else {
      $this->val[$fd_name]=$v;
      $this->map[$fd_name]=0;
    }
  }
  function SetFieldType($fd_ary,$tp=0) {
    foreach($fd_ary as $fd_name)
      $this->map[$fd_name]=$tp;
  }
  function AddVal($fd_name, $v = Null) {
    $f = '_' . $fd_name;
    if(array_key_exists($f,$_POST))
      $v=$_POST[$f];
    else {
      if(is_null($v))
         return;
     }
    $this->AddOwnVal($fd_name,$v);
  }
  function AddArray($names,$val_ary) {
    foreach($names as $nm)
      $this->AddOwnVal($nm,$val_ary[$nm]);
  }
  function AddValues($names,$ary = null) {
    if(is_null($ary))
      $ary=$_POST;
    foreach ($names as $nm) {
      $f = '_' . $nm;
      if(array_key_exists($f,$ary))
        $v=$ary[$f];
      else
        $v='';      // use empty string when field does not exist (checkbox)
      $this->AddOwnVal($nm,$v);
    }
  }
  function GetQuotedVal($fd_name) {
    if (array_key_exists($fd_name,$this->map)) {
      switch($this->map[$fd_name]){
           case 1: return $this->val[$fd_name];  // number
           case 2: return 'NULL';       // null
          }
    }
    return QuoteStr($this->val[$fd_name]) ; // string
  }

  function ChkEmpty($ary) {
    $rtn=true;
    foreach($ary as $name=>$disp_name) {
      if(empty($this->val[$name])) {
        if(!$rtn) echo "<br>\n";
        echo $disp_name,'不得為空白';
        $rtn=false;
      }
    }
    return $rtn;
  }
//
// Make the insert SQL command
//
  function mk_insert($tbl) {
    foreach ($this->val as $nm=>$v) {
      if (IsSet($fd))
             $fd .= ",$nm";
          else
             $fd=$nm;
      if (IsSet($s))
         $s.= "," . $this->GetQuotedVal($nm);
      else
         $s=$this->GetQuotedVal($nm);
    }
    return "INSERT INTO $tbl ($fd) VALUES ($s)";
  }
  function mk_where($key) {
    foreach($key as $fd => $v) {
      if(IsSet($s))
        $s.=" AND $fd='$v'";
      else
        $s="$fd='$v'";
    }
    return $s;
  }
//
// Make the update SQL command, $val is an array of field/value list
// $key is an array of field/value
//
  function mk_update($tbl, $key, $ignore_ary=null){
    foreach($this->val as $fd => $v) {
      if(is_array($ignore_ary) && in_array($fd,$ignore_ary))
        continue;
      $v=$this->GetQuotedVal($fd);
      if (IsSet($v_list))
             $v_list.=", $fd=$v";
          else
             $v_list="$fd=$v";
    }
    return "UPDATE $tbl SET\n$v_list\nWHERE ".$this->mk_where($key);
  }
}

$dbt=new DBTool;
// generate field related form components
function db_field($fd_name,$type,$sz,$onChange=null) {  //onChange is for FD_SELECTONE
  global $dbt;
//  $nm=" name=\"_$fd_name\"";
  $nm=" id=\"_$fd_name\" name=\"_$fd_name\"";
  $hasVal=is_array($dbt->val) && array_key_exists($fd_name,$dbt->val);
  $v=$hasVal ? $dbt->val[$fd_name] : '';
  switch($type) {
  case FD_TEXTBOX:
        if ($hasVal)
           $v=' value="'.HTMLSpecialChars($v).'"';
        echo "<input type=\"text\"$nm$v size=$sz>";
        break;
  case FD_PASSWORD:
        if ($hasVal)
          $v=" value=\"$v\"";
        echo "<input type=\"password\"$nm$v size=$sz>";
        break;
  case FD_TEXTAREA:
        list($row,$col)=explode(',',$sz);
        echo "<textarea rows=$row$nm cols=$col>$v</textarea>";
        break;
  case FD_CHECKBOX:     // sz is the name after checkbox
        if ($hasVal)
           $v=$v?" checked":"";
        echo "<input type=\"checkbox\"$nm value=1$v>$sz";
        break;
  case FD_HIDDEN: // hidden
        if ($hasVal)
           $v=' value="'.HTMLSpecialChars($v).'"';
        echo "<input type=\"hidden\"$nm$v>$sz";
        break;
  case FD_SELECTONE+1:  // $sz array add -1=>'不限'
       $sz=array(-1=>'不限')+$sz;
  case FD_SELECTONE: // $sz is an array of values
        if ($onChange)  //if it is not null, onchange is a function
          echo "<select size=1$nm onchange='$onChange'>";
        else
          echo "<select size=1$nm>";
        foreach($sz as $key=>$v1) {
          if($hasVal && $key==$v)
           $s=" selected";
          else
           $s='';
          echo "<option$s value=\"$key\">$v1</option>\n";
        }
        echo "</select>";
    	break;
  }
}
function db_radio($fd_name,$ary,$sep_s=' ') {
  global $dbt;
  $nm=" name=\"_$fd_name\"";
  $hasVal=is_array($dbt->val) && array_key_exists($fd_name,$dbt->val);
  foreach($ary as $key=>$v) {
    $s=($hasVal && $key==$dbt->val[$fd_name]) ? ' checked' : '';
    echo "<input type=radio$nm value=\"$key\"$s>$v",$sep_s;
  }
}
function SQLStrCond($fd_name,$val) {
  if(strPos($val,'%')!==false)
    $op=' like ';
  else
    $op='=';
  return $fd_name.$op.QuoteStr($val);
}

function SQLMultiStrCond($fd_name,$ms) {
  if(strPos($ms,',')>0) {
    $ary=explode(',',$ms);
    $likeAry=array();
    $inAry=array();
    foreach($ary as $s) {
      if(strPos($s,'%')!==false)
        $likeAry[]=$fd_name.' like '.QuoteStr($s);
      else
        $inAry[]=QuoteStr($s);
    }
    $s=count($likeAry)>0 ? implode(' or ',$likeAry) : '';
    if(count($inAry)>0) {
      $ms=$fd_name.' in ('.implode(',',$inAry).')';
      if(!empty($s))
         return "($ms or $s)";
      else
         return $ms;
    }
    else
      return '('.$s.')';
  }
  else
    return SQLStrCond($fd_name,$ms);
}

function DoUpdIns($tbl,$is_ins,$key,$extra_chk=array()) {
  global $dbt,$db;
  if ($is_ins) {
    if(is_array($key)) {
      $s='('.$dbt->mk_where($key).')';
      $sql="select count(*) from $tbl where $s";
      foreach($extra_chk as $fd) {
        $val=$dbt->val[$fd];
        $sql=$sql." or [$fd]=". (is_bool($val) || is_numeric($val) ?  $val : QuoteStr($val));
      }
      $result=$db->query($sql);
      $row=$result->fetch_row();
      if($row[0]>0) {
        echo '<h3>已有重複的資料,不能再新增</h3>';
        return -1;
      }
    }
    $sql=$dbt->mk_insert($tbl);
    $msg='新增';
  }
  else {
    $sql=$dbt->mk_update($tbl,$key);
    $msg='更新';
  }
  if(!@$db->query($sql)) {
    echo "<h3>資料庫命令失敗</h3>";
    return -2;
  }
  $rtn=$db->rows_affected($db_link);
  echo "<h3>資料$msg 共 $rtn 筆</h3>\n";
  return $rtn;
}
function NextDay($d_s,$quote=true) {
  $t=new DateTime($d_s);
  $t->modify('+1 day');
  $s=$t->format('Y-m-d');
  if($quote)
    $s=QuoteStr($s);
  return $s;
}
?>
