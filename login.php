<?php require_once("cfg.php");
$url='index.php';
if(!empty($_POST['userName']) && !empty($_POST['userPw'])) {
  $username=$_POST['userName'];
  $pw=$_POST['userPw'];
}
else {
  $msg="不正常的網頁呼叫";  //沒有正常登入
  include("showmsg.php");
  die;
}
//在這邊檢查是否真有其人(user identification)
$sql='select user_id as id, username as name, user_pw, nickname, sys_admin, cate_admin from users where username='.QuoteStr($username);  
$result=$db->query($sql);
if ($result->num_rows==0){
    $msg='查無使用者，請先註冊！';
    include('showMsg.php');
    die;
}
//測試階段，密碼採用明碼不加密
$row=$result->fetch_assoc();
if ($row['user_pw']!=$pw){
    $msg='密碼錯誤，請重新登入';
    include('showMsg.php');
    die;
}
unset($row["user_pw"]);
$user_info=$row;
$_SESSION['user_info']=$user_info;
//將該使用者隸屬的類別、專題與群組select回來，只有基本的id、名字等信息(為了要在main.php顯示訊息，必須在這個階段先select必要訊息)
//將query分成兩次下，第一次select回cate和prj，第二次select groups，避免query太複雜
$result=$db->query("SELECT cate_id,cate_name,projects.prj_id,full_name,short_name,user_prj_rel.admin from projects
  inner join categories on projects.category_id=categories.cate_id
  inner join user_prj_rel on projects.prj_id=user_prj_rel.prj_id
  where user_prj_rel.user_id=".$user_info["id"]);
if ($result->num_rows==0){ 
  $prj_grp=null;
  $cate_prj=null;
}
else{
  //這邊拆分成兩個層級，一個是主要的prj_grp的基本資訊，另一個$cate_prj只儲存category和project的對應關係(所以只會放哪一個category有哪些prj_id)
  $prj_grp=array();
  $cate_prj=array();
  while ($row=$result->fetch_assoc()){
    $prj_grp[$row["prj_id"]]=array("cate_id"=>$row["cate_id"],"cate_name"=>$row["cate_name"],"full_name"=>$row["full_name"],"short_name"=>$row["short_name"],"admin"=>$row["admin"],"group"=>array());
    $cate_prj[$row["cate_id"]][]=$row["prj_id"];
  }
  //select group(s)
  $result=$db->query("SELECT `groups`.prj_id,`groups`.grp_id,grp_name FROM `groups`
    inner join user_grp_rel on `groups`.grp_id=user_grp_rel.grp_id and `groups`.prj_id=user_grp_rel.prj_id
    where user_id=".$user_info["id"]);
  while ($row=$result->fetch_assoc())
    $prj_grp[$row["prj_id"]]["group"][$row["grp_id"]]=$row["grp_name"];
}
$_SESSION["prj_grp"]=$prj_grp;
$_SESSION["cate_prj"]=$cate_prj;
include('main.php');
?>