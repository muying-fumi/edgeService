<?php require_once('cfg.php');
include("chkLogin.php");
include("db_util.php");
require_once("searching.php");
define("MODE_NEW_SELECT",0);
define("MODE_NEW_SPECIFY",1);
define("MODE_EDIT_SELECT",2);
define("MODE_EDIT_SPECIFY",3);
define("MODE_INSERT_SELECT",4);
define("MODE_INSERT_SPECIFY",5);
define("MODE_UPDATE_SELECT",6);
define("MODE_UPDATE_SPECIFY",7);
define("MODE_ERROR",8);
if (!empty($_GET["new"])){  //新增群組只有專題管理者(小組長)可以
    if (empty($_GET["key0"]))
        $mode=MODE_NEW_SELECT;    //未指定專案要新增到哪一個專案底下，之後要給user選擇(By html <select>)
    else
        $mode=MODE_NEW_SPECIFY;
}
else if (!empty($_GET["edit"])){    
    $mode=MODE_EDIT_SELECT;
}
else if (!empty($_POST["btnInsertSelect"])){
    $mode=MODE_INSERT_SELECT;
}
else if (!empty($_POST["btnInsertSpecify"])){
    $mode=MODE_INSERT_SPECIFY;
}
else if (!empty($_POST["btnUpdateSelect"])){
    $mode=MODE_UPDATE_SELECT;
}
else if (!empty($_POST["btnUpdateSpecify"])){
    $mode=MODE_UPDATE_SPECIFY;
}
else{
    $mode=MODE_ERROR;
}
$mode_name=array(MODE_NEW_SELECT=>"新增群組",MODE_NEW_SPECIFY=>"新增群組",MODE_EDIT_SELECT=>"編輯群組",MODE_EDIT_SPECIFY=>"編輯群組",MODE_INSERT_SELECT=>"執行新增",MODE_INSERT_SPECIFY=>"執行新增",MODE_UPDATE_SELECT=>"執行編輯",MODE_UPDATE_SPECIFY,MODE_ERROR=>"錯誤");
?>
<html>
<head>
    <meta charset='utf-8'>
    <title><?php echo $mode_name[$mode];?></title>
    <link rel="stylesheet" type='text/css' href="css/style_v1-1.css?v=<?=time()?>">
</head>
<body>
<?php if ($mode==MODE_ERROR){ echo "<h1>URL有誤</h1>"; die; }
else{ include("navbar.php");
if ($mode==MODE_NEW_SELECT || $mode==MODE_NEW_SPECIFY || $mode==MODE_EDIT_SELECT || $mode==MODE_EDIT_SPECIFY) echo "<h1>".$mode_name[$mode]."</h1>";
}
?>
<div class="center">
<ul style='text-align:left'><li><font style='font-weight:bold'><font color='red'>*</font>為必填項目</font></li>
<li><font style='font-weight:bold'>使用者id可輸入以半形逗號(,)分隔的清單、以dash(-)分隔的區間或是單一的數字</font></li>
</div>
<form method='POST' action='editGroup.php'>
<table align=center>
<?php if ($mode==MODE_NEW_SELECT || $mode==MODE_NEW_SPECIFY){ ?>
<tr><td>專案隸屬類別、id與名稱<font color="red">*</font></td><td class="full_width"><?php
if ($mode==MODE_NEW_SELECT){    //產生<select>html statements
    $select_ary=array(-1=>"請選擇該群組隸屬的專案");
    foreach ($prj_grp as $prj_id=>$val) //include chkLogin.php後就會有$prj_grp
        if ($val["admin"])  //為該專案小組長，有權限可以新增組員
            $select_ary[$prj_id]="類別id=".$val["cate_id"].": ".$val["cate_name"]."／專案id=$prj_id: ".$val["full_name"]."(".$val["short_name"].")";
    db_field("prj_id",FD_SELECTONE,$select_ary,"setNames()");
} else{
    db_field("cate_id",FD_HIDDEN,"id=".$dbt->val["cate_id"].": ");
    db_field("cate_name",FD_HIDDEN,$dbt->val["cate_name"]);
} ?>
</td></tr>
<tr><td>輸入群組成員id<font color=red>*</font></td><td class="full_width"><?php db_field("grp_mem_id",FD_TEXTBOX,50);?></td></tr>
<?php } ?>
</table><br>
<?php if ($mode==MODE_NEW_SELECT){ ?>
<input type="submit" name="btnInsertSelect" value="提交">
<?php } else if ($mode==MODE_NEW_SPECIFY){ ?>
<input type="submit" name="btnInsertSpecify" value="提交">
<?php } ?>
</form>
<script>

</script>
</body>
</html>