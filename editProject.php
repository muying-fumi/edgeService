<?php require_once('cfg.php');
include("chkLogin.php");
include("db_util.php");
require_once("searching.php");
if (!empty($_GET["getNames"]) && !empty($_GET["prj_id"])){   //get full_name and short_name of the passing prj_id
    echo '"'.$prj_grp[$_GET["prj_id"]]["full_name"].'","'.$prj_grp[$_GET["prj_id"]]["short_name"].'"';
    exit;
}
/*
if (!empty($_GET["getNames"]) && !empty($_GET["prj_id"])){   //get full_name and short_name of the passing prj_id
    echo '"'.$prj_grp[$_GET["prj_id"]]["full_name"].'","'.$prj_grp[$_GET["prj_id"]]["short_name"].'"';
    exit;
}
*/
if (!empty($_GET["listMem"]) && !empty($_GET["prj_id"])){   //顯示出隸屬於這個project的使用者清單
    $result=$db->query("SELECT users.user_id,username,nickname,user_prj_rel.admin FROM `users`
        inner join user_prj_rel on users.user_id=user_prj_rel.user_id
        where active=1 and prj_id=".$_GET["prj_id"]);   //只顯示已被激活的使用者
    $response="";
    while ($row=$result->fetch_assoc()){
        if (empty($response)){
            $response="<h2>專案id=".$_GET["prj_id"]."的組員名單</h2>";
            $response.="<div class='center' style='font-weight:bold'>* 僅有<font color=red>已被激活</font>的使用者會在表內</div><br>";
            $response.="<table align='center' class='blueTb'><tr><th>使用者id</th><th>帳號</th><th>暱稱</th><th>身分別</th></tr>";
        }
        $response.="<tr onMouseOver=\"this.style.background='#FFEBC5';\" onMouseOut=\"this.style.background='';\">";
        foreach ($row as $key=>$val){
            if ($key=="admin")
                $response.=$val?"<td>小組長</td>":"<td>組員</td>";
            else
                $response.="<td>$val</td>";
        }
        $response.="</tr>";
    }
    if (empty($response))
        echo "sql error";
    else{
        $response.="</table>";
        echo $response;
    } 
    exit;
}
define("MODE_NEW_SELECT",0);
define("MODE_NEW_SPECIFY",1);
define("MODE_EDIT_SELECT",2);
define("MODE_EDIT_SPECIFY",3);
define("MODE_INSERT_SELECT",4);
define("MODE_INSERT_SPECIFY",5);
define("MODE_UPDATE_SELECT",6);
define("MODE_UPDATE_SPECIFY",7);
define("MODE_ERROR",8);

if (!empty($_GET["new"])){  //新增專題只有類別管理者可以
    if (!chkCateAdmin())
        die;
    if (!empty($_GET["key1"])){  //key1為cate_id
        $mode=MODE_NEW_SPECIFY;   //該模式為指定新增專案至指定類別
        if (!chkUserCateRelValid($_GET["key1"],"","main.php"))  //檢查傳進來的cate_id是否合法(類別是否存在且creator是該使用者)
            die;
        $result=$db->query("select cate_name from categories where cate_id=".$_GET["key1"]);
        $dbt->AddOwnVal("cate_id",$_GET["key1"]);
        $dbt->AddOwnVal("cate_name",$result->fetch_row()[0]);
    }
    else{
        if (!chkhasCate())
            die;
        $mode=MODE_NEW_SELECT;    //未指定專案要新增到哪一個類別底下，之後要給user選擇(By html <select>)
    }
}
else if (!empty($_GET["edit"])){    
    if (empty($_GET["key0"])){  //沒有指定要編輯哪一個專案，用<select>來選擇
        $mode=MODE_EDIT_SELECT;
    }
    else{
        if (!chkPrjAdmin($_GET["key0"])) //key0為prj_id
            die;
        $mode=MODE_EDIT_SPECIFY;    //有指定prj_id，就直接用該指定prj_id來編輯。
        $prj_id=$_GET["key0"];
        $dbt->AddOwnVal("prj_id",$prj_id);
    } 
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
$mode_name=array(MODE_NEW_SELECT=>"新增專案",MODE_NEW_SPECIFY=>"新增專案",MODE_EDIT_SELECT=>"編輯專案",MODE_EDIT_SPECIFY=>"編輯專案",MODE_INSERT_SELECT=>"執行新增",MODE_INSERT_SPECIFY=>"執行新增",MODE_UPDATE_SELECT=>"執行編輯",MODE_UPDATE_SPECIFY,MODE_ERROR=>"錯誤");
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
else if ($mode==MODE_INSERT_SELECT || $mode==MODE_INSERT_SPECIFY){
$dbt->AddValues(array("cate_id","cate_name","prj_admin_id")); //POST上來資料放入$dbt
if (!chkUserCateRelValid($dbt->val["cate_id"])){ //要再檢查一次cate_id是否合法(前端可能造假)
    echo "<h1>您不是該類別的管理者或該類別不存在，請重新選擇</h1>";
    $mode=MODE_NEW_SELECT;  //不管本來是select還是specify，現在一律給使用者選
}
else{ //cate_id存在
    if ($mode==MODE_INSERT_SELECT){ //select只有儲存cate_id，cate_name沒有POST上來
        $result=$db->query("select cate_name from categories where cate_id=".$dbt->val["cate_id"]);
        $dbt->AddOwnVal("cate_name",$result->fetch_row()[0]);
    }
    $result=$db->query("select user_id,username from users where ".inOrEqualOrBetweenSQL("user_id",$dbt->val["prj_admin_id"]));
    if ($result->num_rows==0){    //prj_admin_id不存在於db
        echo "<h1>輸入的專題管理者id不存在，請重新輸入</h1>";
        if ($mode==MODE_INSERT_SELECT)
            $mode=MODE_NEW_SELECT;
        else
            $mode=MODE_NEW_SPECIFY;
    }
    else{
        $valid_id=array();
        while ($row=$result->fetch_row()){
            $db->begin_transaction();
            if (!$db->query("insert into projects (full_name,category_id,create_time) values (".quoteStr($row[1]).",".$dbt->val["cate_id"].",'".date($db_time_fmt)."')")){
                echo "<h1>新增$row[1]為專題管理者時發生錯誤，請重新嘗試</h1>";
                $db->rollback();
            }
            else{
                if (!$db->query("insert into user_prj_rel (user_id,prj_id,admin) values (".$row[0].",".$db->insert_id.",1)")){ 
                    echo "<h1>新增$row[1]至使用者/專題關係表時發生錯誤，請重新嘗試</h1>";
                    $db->rollback();
                }
                else{
                    $db->commit();
                    $valid_id[]=$row[0];
                }
            }    
        }
        if (count($valid_id)>0){
            echo "<h1>新增".count($valid_id)."個專題以及將使用者id=".implode(",",$valid_id)."指定為專題管理者成功！</h1>";
        }
        echo "<h3>仍可持續嘗試新增其它專題</h3>";
        if ($mode==MODE_INSERT_SELECT)
            $mode=MODE_NEW_SELECT;
        else
            $mode=MODE_NEW_SPECIFY;
    }
}
}
else if ($mode==MODE_UPDATE_SELECT || $mode==MODE_UPDATE_SPECIFY){
$dbt->AddValues(array("cate_id","prj_id","full_name","short_name","member_id"));
$result=$db->query("update projects set full_name=".quoteStr($dbt->val["full_name"]).", short_name=".quoteStr($dbt->val["short_name"])." where prj_id=".$dbt->val["prj_id"]);
if (!$result)
    echo "<h1>編輯專案全名或簡稱失敗</h1>";
else{
    //編輯成功的資料寫回$prj_grp，最後寫回session
    $prj_grp[$dbt->val["prj_id"]]["full_name"]=$dbt->val["full_name"];
    $prj_grp[$dbt->val["prj_id"]]["short_name"]=$dbt->val["short_name"];
    $_SESSION["prj_grp"]=$prj_grp;
    $err=false;
    if (!empty($dbt->val["member_id"])){
        $result=$db->query("select user_id,username from users where ".inOrEqualOrBetweenSQL("user_id",$dbt->val["member_id"]));
        if ($result->num_rows==0){    //member_id不存在於db
            echo "<h1>輸入的成員id不存在，請重新輸入</h1>";
            $err=true;
        }
        else{
            $valid_id=array();
            while ($row=$result->fetch_row()){
                if (!$db->query("insert into user_prj_rel (user_id,prj_id,admin) values (".$row[0].",".$dbt->val["prj_id"].",0)")){
                    echo "<h1>新增$row[1]至使用者/專題關係表時發生錯誤，請檢查是否已經是小組成員</h1>";
                    $err=true;
                }
                else
                    $valid_id[]=$row[0];
            }    
        }
    }
    if (!$err)
        echo "<h1>編輯資料成功！仍可持續編輯！</h1>";   
    if ($mode==MODE_UPDATE_SELECT)
        $mode=MODE_EDIT_SELECT;
    else{
        $prj_id=$dbt->val["prj_id"];
        $mode=MODE_EDIT_SPECIFY;
    }
}}}?>
<div class="center">
<ul style='text-align:left'><li><font style='font-weight:bold'><font color='red'>*</font>為必填項目</font></li>
<li><font style='font-weight:bold'>使用者id可輸入以半形逗號(,)分隔的清單、以dash(-)分隔的區間或是單一的數字</font></li>
</div>
<!--<li><font style='font-weight:bold'>如果專題名稱(全名或簡稱)有任一沒有輸入，會代預設值為<font color='FF5E36'>專題管理者帳號</font></font></li>--></ul>
<form method='POST' action='editProject.php'>
<table align=center>
<?php if ($mode==MODE_NEW_SELECT || $mode==MODE_NEW_SPECIFY){ ?>
<tr><td>類別id及名稱<font color='red'>*</font></td><td class="full_width"><?php
if ($mode==MODE_NEW_SELECT){    //產生<select>html statements
    $select_ary=array(-1=>"請選擇該專案隸屬的類別");
    $result=$db->query("select cate_id, cate_name from categories where creator_id=".$user_info["id"]);
    while ($row=$result->fetch_row())
        $select_ary[$row[0]]="id=$row[0]: $row[1]";
    db_field("cate_id",FD_SELECTONE,$select_ary);
} else{
    db_field("cate_id",FD_HIDDEN,"id=".$dbt->val["cate_id"].": ");
    db_field("cate_name",FD_HIDDEN,$dbt->val["cate_name"]);
} ?>
</td></tr>
<tr><td>請輸入專題管理者id<font color='red'>*</font></td><td class="full_width"><?php db_field("prj_admin_id",FD_TEXTBOX,30);?></td></tr>
<?php } else if ($mode==MODE_EDIT_SELECT || $mode==MODE_EDIT_SPECIFY){ ?>
<tr><td>專案隸屬類別、id及名稱<font color='red'>*</font></td><td class="full_width"><?php
if ($mode==MODE_EDIT_SELECT){    //產生<select>html statements
    $select_ary=array(-1=>"請選擇專案");
    foreach ($prj_grp as $prj_id=>$val)
        if ($val["admin"])
            $select_ary[$prj_id]="類別id=".$val["cate_id"].": ".$val["cate_name"]."／專案id=$prj_id: ".$val["full_name"]."(".$val["short_name"].")";
    db_field("prj_id",FD_SELECTONE,$select_ary,"setNames()");
} else{
    db_field("prj_id",FD_HIDDEN,"類別id=".$prj_grp[$prj_id]["cate_id"].": ".$prj_grp[$prj_id]["cate_name"]."／專案id=$prj_id: ".$prj_grp[$prj_id]["full_name"]."(".$prj_grp[$prj_id]["short_name"].")");
    $dbt->AddArray(array("full_name","short_name"),$prj_grp[$prj_id]);
}
?></td></tr>
<tr><td>請輸入專案全名<font color='red'>*</font></td><td class="full_width"><?php db_field("full_name",FD_TEXTBOX,50);?></td></tr>
<tr><td>請輸入專案簡稱<font color='red'>*</font></td><td class="full_width"><?php db_field("short_name",FD_TEXTBOX,50);?></td></tr>
<tr><td>欲新增組員的使用者id</td><td class="full_width"><?php db_field("member_id",FD_TEXTBOX,50);?></td></tr>
<tr><td>查看組員</td><td><a href="#memTb" onclick="listMembers();">GO</a></td></tr>
<?php } ?>
</table><br><br>
<?php if ($mode==MODE_NEW_SELECT) { ?>
<input type=submit name='btnInsertSelect' value='提交' onclick='return chkInput(0);'>
<?php } else if ($mode==MODE_NEW_SPECIFY) { ?>
<input type=submit name='btnInsertSpecify' value='提交' onclick='return chkInput(0); //cate_id理論上不會為-1，畢竟是傳進來的，如果id不對的話前面就會擋掉了'>
<?php } else if ($mode==MODE_EDIT_SELECT) { ?>
<input type=submit name='btnUpdateSelect' value='提交' onclick='return chkInput(1);'>
<?php } else if ($mode==MODE_EDIT_SPECIFY) { ?>
<input type=submit name='btnUpdateSpecify' value='提交' onclick='return chkInput(1);'>
<?php } ?>
</form><br>
<a href="main.php">返回主畫面</a>
<br><br>
<div id="feedback"></div>
<div id="memTb"></div>
<script>
<?php if ($mode==MODE_NEW_SELECT || $mode==MODE_NEW_SPECIFY){ ?>
var cate_id=document.getElementById("_cate_id");
<?php } ?>
var prj_admin_id=document.getElementById("_prj_admin_id");
<?php if ($mode==MODE_EDIT_SELECT || $mode==MODE_EDIT_SPECIFY){ ?>
var prj_id=document.getElementById("_prj_id");
<?php } ?>
var full_name=document.getElementById("_full_name");    //專案全名
var short_name=document.getElementById("_short_name");  //專案簡稱
var feedback=document.getElementById("feedback");
var memTb=document.getElementById("memTb");

function chkInput(mode){
if (mode==0){   //new mode，新增專案(僅類別管理者可操作)
    if (cate_id.value==-1){
        alert("您還沒有選擇類別！");
        return false;
    }
    if (prj_admin_id.value==""){
        alert("您還沒有輸入專題管理者的id！");
        prj_admin_id.focus();
        return false;
    }
}
else if (mode==1){  //edit mode，編輯專案(僅小組管理者可以操作)
    if (prj_id.value==-1){
        alert("您還沒有選擇要編輯的專案！");
        return false;
    }
    if (full_name.value==""){
        alert("您還沒有輸入專案的全名！");
        full_name.focus();
        return false;
    }
    if (short_name.value==""){
        alert("您還沒有輸入專案的簡稱！");
        short_name.focus();
        return false;
    }
}
else if (mode==2){  //當MODE_EDIT_SELECT的select onchange時會到該mode
    if (prj_id.value==-1)
        return false;
}
else if (mode==3){  //當要查看組員時會到該mode
    if (prj_id.value==-1){
        alert("您還沒有選擇專案！");
        return false;
    }
}
return true;
}

function setNames(){
//set full_name and short_name for the selected project by ajax
if (!chkInput(2)){
    full_name.value="";
    short_name.value="";
    return false;
}
//ajax
var xhttp = new XMLHttpRequest();
xhttp.onreadystatechange = function() {
    if (this.readyState == 4) {
        if (this.status == 200){
            var split_str=this.response.split(",");
            //為了避免full_name、short_name含有逗號，response一律含有quotes(單或雙引號，這裡不假定)
            full_name.value=split_str[0].substring(1,split_str[0].length-1);    
            short_name.value=split_str[1].substring(1,split_str[1].length-1);
        }
        else{
            feedback.innerHTML="error code: "+this.status;
        }
        feedback.innerHTML="";
    }
}
xhttp.open("GET", "editProject.php?getNames=1&prj_id="+prj_id.value, true);
feedback.innerHTML="<img src='img/loading.gif' width=20>";
xhttp.send();
}

function listMembers(){
//list members by ajax    
if (!chkInput(3)){
    return false;
}
var xhttp = new XMLHttpRequest();
xhttp.onreadystatechange = function() {
    if (this.readyState == 4) {
        if (this.status == 200){
            memTb.innerHTML=this.response;
        }
        else{
            memTb.innerHTML="error code: "+this.status;
        }
    }
    feedback.innerHTML="";
}
xhttp.open("GET", "editProject.php?listMem=1&prj_id="+prj_id.value, true);
feedback.innerHTML="<img src='img/loading.gif' width=20>";
xhttp.send();
}
</script>
</body>
</html>