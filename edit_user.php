<?php include('chk_login.php');
chk_userMgr();
include('db_util.php');
include('pwd_utils.php');
$ins_mode=IsSet($_POST['btnInsert']);
$post_mode=$ins_mode || IsSet($_POST['btnUpdate']);
$new_mode=!$post_mode && (IsSet($_GET['new']) || !IsSet($_GET['key0']));
?><html><head>
<meta http-equiv="Content-Type" content="text/html; charset=big5">
<link rel="stylesheet" type="text/css" href="styles.css?v=1">
</head>
<script language="javascript">
function check_pwd(frm){
  if(!frm.tmp_pwd.value ){
     alert("請輸入通行碼!!");
     frm.tmp_pwd.focus();
     return false;
  }
  if(frm.tmp_pwd.value!=frm.tmp_pwd2.value){
     alert("通行碼不一致!!");
     frm.tmp_pwd2.focus();
     return false;
  }
return true;
}
function check_value(frm){
<?php if($new_mode) { ?>
  if(!frm._login_name.value) {
    alert("請輸入登入識別碼");
    return false;
  }
  if(!check_pwd(frm))
    return false;
<?php }
?>  if(!frm._realName.value) {
    alert("請輸入真實姓名");
    return false;
  }
  return true;
}
</script>
<body background="images/backgnd.gif">
<table width=100% bgcolor=#333366 cellpadding=0>
 <tr>
  <td nowrap class=a><a href=help/edit_user.htm>使用說明</a></td>
  <td nowrap class=a><a href=edit_user.php?new=1>新增帳戶</a></td>
  <td nowrap class=a><a href=user_mgr.php>帳號管理</a></td>
 </tr>
</table>
<h2 align=center>帳號資料編輯</h2>
<?php
include_once('log_utils.php');
$bussCodeAry=$_SESSION['bussCodeAry'];
if(!$post_mode){
  if(IsSet($_POST['btnSetTmpPwd']) && !empty($_POST['tmp_pwd'])) {
    $user_id=$_POST['_user_id'];
    $login_name=$_POST['_login_name'];
    $pwd_hash=QuoteStr(AcctPwdHash($login_name.$_POST['tmp_pwd']));
    $sql="update sUser set pwd_hash=$pwd_hash,chg_pwd_time=NULL where user_id=$user_id";
    if(!($result=mssql_query($sql)))
      echo "<h3>資料庫命令失敗</h3>";
    else {
      echo "已設定 $login_name 的臨時密碼(重設密碼)，登入後會強制更改";
      addUserLog($user_id,$login_name,'重設密碼','');
    }
    $dbt->AddValues(array('user_id','login_name','realName','main_dept_id','sysAdmin','userMgr','compChk','businessChk','userLevel',
    'business_code','can_edit','can_create','can_del'));
    $new_mode=false;
  }
  else if(!$new_mode) {
    $user_id=$_GET['key0'];
    $result=mssql_query("select * from sUser where user_id=$user_id");
    $dbt->fetch_row($result);
    $login_name=$dbt->val['login_name'];
    if($dbt->val['lock_status']==1) { // 鎖定
      if(IsSet($_GET['unlock'])) {
        mssql_query("update sUser set lock_status=0,pwd_fail_cnt=0 where user_id=$user_id and lock_status=1");
        addUserLog($user_id,$login_name,'解鎖','');
        echo '<h3>已解除鎖定</h3>';
      }
      else
        echo "<font class=blu_bg><a href=edit_user.php?key0=$user_id&unlock=1>解除鎖定</a></font>";
    }
    else if($dbt->val['lock_status']==2) { // 註銷
      if(IsSet($_GET['restore'])) {
        mssql_query("update sUser set lock_status=0,pwd_fail_cnt=0,deactivateTime=NULL,chg_pwd_time=NULL where user_id=$user_id and lock_status=2");
        addUserLog($user_id,$login_name,'還原註銷','');
        echo '<h3>已還原註銷</h3>';
      }
      else
        echo "<font class=blu_bg><a href=edit_user.php?key0=$user_id&restore=1>還原註銷</a></font>";
    }
  }
  else {
    if(IsSet($_GET['id'])) {
      $user_id=$_GET['id'];
      $result=mssql_query("select * from sUser where user_id=$user_id");
      $dbt->fetch_row($result);
      unset($dbt->val['login_name']);
    }
  }
}
else {
  $dbt->AddValues(array('realName','job_title','main_dept_id','sysAdmin','userMgr','compChk','businessChk','can_edit','can_del','can_create',
    'userLevel','extraAuth','business_code'));
  $dbt->SetFieldType(array('job_title','extraAuth'));
  $login_name=$_POST['_login_name'];
  if($dbt->val['userMgr']) { //帳號管理者不能兼業務角色
    $dbt->val['compChk']=0;
    $dbt->val['businessChk']=0;
    $dbt->val['userLevel']=0;
    $dbt->val['extraAuth']='';
  }
  if($ins_mode) {
    $tmp_pwd=$_POST['tmp_pwd'];
    $dbt->AddValues(array('login_name'));
    $dbt->AddOwnVal('pwd_hash',AcctPwdHash($login_name.$tmp_pwd));
    $dbt->SetFieldType(array('login_name','pwd_hash'),0); // force to string fields
    $dbt->AddOwnVal('createTime',cur_t()->format($fmt_db_d));
    $dbt->AddOwnVal('lock_status',0);
    $sql=$dbt->mk_insert('sUser',true);
    $msg='新增';
  }
  else {
    $user_id=$_POST['_user_id'];
    $sql=$dbt->mk_update('sUser',array('user_id'=>$user_id));
    $msg='更新';
    $chg_memo=GetChgMemo(0,array('realName','main_dept_id','sysAdmin','compChk','businessChk','userLevel','extraAuth','business_code','can_edit','can_del','can_create','userMgr'),
    $dbt->val,"user_id=$user_id");
  }
  if(!($result=@mssql_query($sql))) {
    echo "<h3>資料庫命令失敗(可能是因帳號重複)</h3>";
    $rtn=0;
    $new_mode=true;
  }
  else {
   $rtn=mssql_rows_affected($db_link);
   if ($ins_mode) {
     $row=mssql_fetch_row($result);
     $user_id=$row[0];
     addUserLog($user_id,$login_name,'新增');
   }
   else {
     addUserLog($user_id,$login_name,'修改',$chg_memo);
     if($user_id==$user_info['user_id'])
       echo "<h3>請登出以讀取最新設定</h3>";
   }
  }
  echo "<h3>用戶\"$login_name\"資料$msg 共 $rtn 筆</h3>\n";
  if(!$new_mode)
    $dbt->AddOwnVal('user_id',$user_id);
  $dbt->AddOwnVal('login_name',$login_name);
}
?><form method="POST" action="edit_user.php" name="frmUser">
<p><b>使用者基本資料</b></p>
<table border="0" cellspacing="1">
 <tr>
   <td>識別碼</td>
   <td><?php
if($new_mode)
  db_field('login_name',FD_TEXTBOX,20);
else {
  echo "<font color=#ff0000>$login_name (編號$user_id)</font>";
  db_field('user_id',FD_HIDDEN,''); db_field('login_name',FD_HIDDEN,'');
}
?></td></tr><?php
if($new_mode) { ?>
  <tr><td>初始通行碼</td><td><input type=password name=tmp_pwd size=15></td></tr>
  <tr><td>通行碼確認</td><td><input type=password name=tmp_pwd2 size=15></td></tr>
<?php }
?><tr><td class=b>真實姓名</td><td><?php  db_field('realName',FD_TEXTBOX,20); ?></td></tr>
<tr><td class=b>職稱</td><td><?php  db_field('job_title',FD_TEXTBOX,20); ?></td></tr>
<tr><td class=b>隸屬單位</td><td><?php  db_field('main_dept_id',FD_SELECTONE,$_SESSION['dept_ary']); ?></td></tr>
<tr><td class=b>角色權限</td><td><?php  db_field('sysAdmin',FD_CHECKBOX,'系統管理者 '); db_field('userMgr',FD_CHECKBOX,'帳號管理者 ');
db_field('compChk',FD_CHECKBOX,'電腦稽核人員 '); db_field('businessChk',FD_CHECKBOX,'業務稽核人員 ');
 ?></td></tr>
<tr><td class=b>業務角色</td><td><?php  db_radio('userLevel',$usrLevelAry); ?></td></tr>
 <tr><td class=b>單項權限</td><td><?php  db_field('can_edit',FD_CHECKBOX,'可編輯(含帳冊經管人員) ');
db_field('can_del',FD_CHECKBOX,'可刪除 '); db_field('can_create',FD_CHECKBOX,'可建入 ');
 ?></td></tr>
<tr><td class=b>業務別</td><td><?php  db_field('business_code',FD_SELECTONE,$bussCodeAry); ?></td></tr>
<tr><td class=b>額外權限等級</td><td><?php  db_field('extraAuth',FD_TEXTBOX,30); ?></td></tr>
<tr><td colspan=2>經辦可看權限等級1的報表，覆核可看1,2，科長可看1,2,3，單位主管可看1,2,3,4<br>額外權限請輸入數字，多個時以逗號分隔，例如"4,6"，
表示該使用者除了以上等級外，還可看到等級4及6的報表<br>若無額外權限，則留空白！</td></tr>
</table>
<?php
if(!$new_mode)
  echo "<input type=submit value=\"更新\" name=btnUpdate>";
else
  echo "<input type=submit value=\"新增\"  name=\"btnInsert\" onClick=\"return check_value(document.frmUser)\">";
?>
 <input type="reset" value="重新設定" name="B2"></p>
<?php if(!$new_mode) {
  echo "<table width=18% cellpadding=0><tr><td nowrap class=blu_bg><a href=edit_user.php?new=1&id=$user_id>以目前資料為範本新增一筆資料</a></td></tr></table>";
?>
<fieldset>
<br>
<legend><span style="background-color: #FFFF93"><b>臨時通行碼設定</b></span></legend>
<table border=0 cellspacing=1>
 <tr><td width=90>臨時通行碼</td><td><input type=password name="tmp_pwd" size=15></td></tr>
 <tr><td>通行碼確認</td><td><input type=password name="tmp_pwd2" size=15></td></tr></table>
<input type=submit value="設定臨時通行碼" name=btnSetTmpPwd onClick="return check_pwd(document.frmUser)">
</fieldset><?php } ?>
</form>
</body></html>
