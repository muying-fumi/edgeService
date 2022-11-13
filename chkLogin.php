<?php require_once('cfg.php');
if (!IsSet($_SESSION['user_info'])) {
    $url='index.php';
    $msg='😯請先登入系統！';
    include('showMsg.php');
    die;
  }
  $user_info=$_SESSION['user_info'];
  $prj_grp=$_SESSION["prj_grp"];
  $cate_prj=$_SESSION["cate_prj"];

function chkSysAdmin($msg="",$url=null){
  global $user_info;
  if (!$user_info['sys_admin']){
    if (!isset($msg)) //msg==null，代表不呼叫showMsg，純粹檢查是否有admin資格。
      return false;
    $msg=!empty($msg)?$msg:'你不是系統管理者，無權訪問該頁面';
    $url=isset($url)?$url:'javascript:history.back()';
    include('showMsg.php');
    return false;
  }
  return true;
}

function chkCateAdmin($msg="",$url=null){
  global $user_info;
  if (!$user_info['cate_admin']){
    if (!isset($msg)) //msg==null，代表不呼叫showMsg，純粹檢查是否有admin資格。
      return false;
    $msg=isset($msg)?$msg:'你不是類別管理者，無權訪問該頁面';
    $url=isset($url)?$url:'javascript:history.back()';
    include('showMsg.php');
    return false;
  }
  return true;
}

function chkPrjAdmin($prj_id,$msg="",$url=null){
  global $user_info,$db;
  $result=$db->query("select count(*) from user_prj_rel where prj_id=".$prj_id." and user_id=".$user_info["id"]." and admin=1");
  if ($result->fetch_row()[0]==0){ //沒有該筆user_prj_rel(count=0)
    if (!isset($msg)) //msg==null，代表不呼叫showMsg，純粹檢查是否有admin資格。
      return false;
    $msg=!empty($msg)?$msg:'你不是該專案的管理者，無權訪問該頁面';
    $url=isset($url)?$url:'javascript:history.back()';
    include('showMsg.php');
    return false;
  }
  return true;  //$result->fetch_row()[0]理論上會是1
}

function chkUserCateRelValid($cate_id,$msg="",$url=null){
  global $user_info, $db;
  $result=$db->query("select count(*) from categories where cate_id=".quoteStr($cate_id)." and creator_id=".$user_info["id"]); //*可能要拉出去變成chkLogin的一個function
  if ($result->fetch_row()[0]==0){ //你沒有新增專案至此類別的權限
      if (!isset($msg)) //msg==null，不呼叫showMsg
        return false;
      $msg=!empty($msg)?$msg:"您不是該類別的管理者或是該類別不存在！";
      $url=isset($url)?$url:"javascript:history.back()";
      include("showMsg.php");
      return false;
  }
  return true;
}

function chkHasCate($msg="",$url=null){
  global $user_info,$db;
  $result=$db->query("select cate_id, cate_name from categories where creator_id=".$user_info["id"]);
  if ($result->num_rows==0){
      if (!isset($msg)) //msg==null，不呼叫showMsg
        return false;
      $msg=!empty($msg)?$msg:"您還沒有新增任何類別，請先新增類別才可以新增專題！";
      $url=isset($url)?$url:"editCategory.php?new=1"; //預設為新增類別的url
      include("showMsg.php");
      return false;
  }
  return true;     
}
?>