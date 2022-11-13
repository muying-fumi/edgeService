<?php include('chkLogin.php');
if (!chkCateAdmin("你不是類別管理者，無權新增/編輯類別！","main.php"))
    die;

define ('MODE_NEW',0);
define ('MODE_EDIT',1);
define ('MODE_UPDATE',2);
define ('MODE_INSERT',3);
include('db_util.php');
require_once('cfg.php');
require_once('searching.php');
if (!empty($_GET['new']))
    $mode=MODE_NEW;
else if (!empty($_GET['key0'])) //編輯哪一個類別(傳入cate_id)
    $mode=MODE_EDIT;
else if (!empty($_POST['btnUpdate']))
    $mode=MODE_UPDATE;
else if (!empty($_POST['btnInsert']))
    $mode=MODE_INSERT;

$mode_name=array(MODE_NEW=>'新增類別',MODE_EDIT=>'編輯類別名稱',MODE_UPDATE=>'執行編輯',MODE_INSERT=>'執行新增');
?><html>
<head>
    <meta charset='utf-8'>
    <title><?php echo $mode_name[$mode];?></title>
    <link rel="stylesheet" type='text/css' href="css/style_v1-1.css?v=<?=time()?>">
</head>
<body>
<?php include("navbar.php");
if ($mode==MODE_NEW){ ?>
<h1>新增類別</h1>
<div class="center">
<ul style='text-align:left'>
<li><font size=3 style='font-weight:bold'>使用者id可輸入以半形逗號(,)分隔的清單、以dash(-)分隔的區間或是單一的數字</font></li>
</ul>
</div>
<?php } else if ($mode==MODE_INSERT){ //一律由有類別管理者權限者來新增專題，避免隨便阿貓阿狗就可以新增很多的專題 ?>
<h1><?php 
$mode=MODE_NEW;  //中間若有任何錯誤，都可以重複新增資料，所以預設為新增資料模式(除非新增資料成功，才會變成編輯模式。)
//中間涉及在categories新增一筆資料、projects新增n筆資料、user_prj_rel新增n筆資料，但是categories新增成功與否和另兩者為獨立，故不需要transaction把categories和另兩者綁在一起。
$dbt->AddValues(array("cate_name")); //因為POST上來的proj_admin_name不是db的欄位，所以這邊先不將他放進$dbt裡面
$dbt->AddOwnVal("creator_id",$user_info['id']);
$dbt->AddOwnVal("create_time",date($db_time_fmt));
$result=$db->query($dbt->mk_insert("categories"));
$dbt->AddValues(array("prj_admin_id"));
if(!$result){
    echo '新增類別失敗';  //可持續嘗試新增資料，可能資料有誤
}
else{   //在categories新增一筆資料成功，接著要在projects新增n筆資料(n>=0)
    $mode=MODE_EDIT;    //可編輯類別名稱，不可編輯隸屬於該類別下的使用者id
    $cate_id=$db->insert_id;
    $dbt->AddOwnVal("cate_id",$cate_id);
    $result=$db->query("select user_id,username from users where ".inOrEqualOrBetweenSQL("user_id",$dbt->val["prj_admin_id"])); //select要專題管理者的username
    if ($result->num_rows==0){ //沒搜到有對應的id
        echo "新增類別".$dbt->val["cate_name"]."成功，但沒有成功新增任何專題";
    }
    else{
        $err=false;
        while ($row=$result->fetch_row()){
            //新增每一個專題管理者的資料到projects(新增project時，必須一併將user_prj_rel新增好資料，故需要用transaction包起來)
            $db->begin_transaction();
            if (!$db->query("insert into projects (full_name,category_id,create_time) values (".quoteStr($row[1]).",$cate_id,'".date($db_time_fmt)."')")){
                echo "新增$row[1]為專題管理者時發生錯誤";
                $db->rollback(); 
                $err=true;
                //需繼續嘗試新增其他人為專題管理者(如果while迴圈會繼續的話)
            }
            else{   //新增每一個專題管理者的資料到user_prj_rel
                if (!$db->query("insert into user_prj_rel (user_id,prj_id,admin) values ($row[0],".$db->insert_id.",1)")){
                    if (!$err)  //尚未有發生錯誤，這裡是第一個(不加<br>)。
                        echo "新增$row[1]至使用者/專題關係表時發生錯誤";
                    else
                        echo "<br>新增$row[1]至使用者/專題關係表時發生錯誤";
                    $db->rollback(); 
                    $err=true;
                    //需繼續嘗試新增其他專題管理者和專題id的relation(如果while迴圈會繼續的話)
                }
                else
                    $db->commit();  //該專題成功新增，且指派該使用者為專題管理者成功，故commit。
            }
        }
        if (!$err){
            echo "新增類別".$dbt->val["cate_name"]."及對應專題成功！";
        }
    }
}    
?></h1>
<?php } else if ($mode==MODE_EDIT){ ?>
<h1><?php
//這邊是一開始GET參數就有傳key0的，上面insert mode完不會進來這裡
echo "目前編輯類別id: ".$_GET["key0"]."<br>";
$result=$db->query("select cate_name from categories where cate_id=".quoteStr($_GET['key0']));
if (!$row=$result->fetch_assoc()){
    echo "該類別不存在";
    die;
}
$dbt->val=$row;
$dbt->AddOwnVal("cate_id",$_GET["key0"]);
?></h1>
<?php } else if ($mode==MODE_UPDATE){ ?>
<h1><?php 
//將post上來的新類別名稱update至db
$mode=MODE_EDIT; //可持續嘗試編輯類別名稱
$dbt->AddValues(array("cate_id","cate_name"));
$sql=$dbt->mk_update("categories",array("cate_id"=>$dbt->val["cate_id"]),array("cate_id")); //update至table categories, where condition cate_id=特定cate_id, set內容值排除cate_id。
if (!$db->query($sql)){
    echo "更改類別名稱失敗";
}
else{
    echo "更改類別名稱為".$dbt->val["cate_name"]."成功！";
}
?></h1>
<?php } ?>
<?php if ($mode==MODE_EDIT){ ?>
<h3>可在下方編輯類別名稱</h3>
<?php } ?>
<form action='editCategory.php' method='POST'>
<table align=center>
<?php if ($mode==MODE_NEW){ ?>
<tr><td>請輸入類別名稱: </td><td><?php db_field('cate_name',FD_TEXTBOX,30);?></td></tr>
<tr><td>輸入專題管理者的使用者id: </td><td><?php db_field('prj_admin_id',FD_TEXTBOX,30);?></td></tr>
<?php } else if ($mode==MODE_EDIT) { ?>
<tr><td>類別id: </td><td><?php db_field("cate_id",FD_HIDDEN,$dbt->val["cate_id"]);?></td></tr>
<tr><td>請輸入欲更改的類別名稱: </td><td><?php db_field('cate_name',FD_TEXTBOX,30);?></td></tr>
<tr><td>隸屬於該類別的使用者: </td><td><?php //下面select隸屬於該類別的使用者id
$result=$db->query("SELECT user_prj_rel.user_id FROM `categories` inner join projects on categories.cate_id=projects.category_id
    inner join user_prj_rel on projects.prj_id=user_prj_rel.prj_id
    WHERE cate_id=".$dbt->val["cate_id"]);
while ($row=$result->fetch_row()){
    if (!isSet($id_str))
        $id_str=$row[0];
    else
        $id_str.=", ".$row[0];
}
if (!isset($id_str))
    echo "";
else
    echo $id_str;
?></td></tr>
<?php } ?>
</table><br><br>
<?php if ($mode==MODE_NEW){ ?>
<input type=submit name='btnInsert' value='提交' onclick='return checkInput();'>
<?php } else if ($mode==MODE_EDIT){ ?>
<input type=submit name='btnUpdate' value='提交' onclick=''>
<?php } ?>
</form><br>
<a href='main.php'>返回主畫面</a>
<script>
var cate_name=document.getElementById("_cate_name");
var prj_admin_id=document.getElementById("_prj_admin_id");

function checkInput(){
if (cate_name.value==""){
    alert("你還沒有輸入類別名稱！");
    cate_name.focus();
    return false;
}
//prj_admin_id可為空，代表暫不輸入產生專題並指排專題小組長
return true;
}
</script>
</body>
</html>