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
     alert("�п�J�q��X!!");
     frm.tmp_pwd.focus();
     return false;
  }
  if(frm.tmp_pwd.value!=frm.tmp_pwd2.value){
     alert("�q��X���@�P!!");
     frm.tmp_pwd2.focus();
     return false;
  }
return true;
}
function check_value(frm){
<?php if($new_mode) { ?>
  if(!frm._login_name.value) {
    alert("�п�J�n�J�ѧO�X");
    return false;
  }
  if(!check_pwd(frm))
    return false;
<?php }
?>  if(!frm._realName.value) {
    alert("�п�J�u��m�W");
    return false;
  }
  return true;
}
</script>
<body background="images/backgnd.gif">
<table width=100% bgcolor=#333366 cellpadding=0>
 <tr>
  <td nowrap class=a><a href=help/edit_user.htm>�ϥλ���</a></td>
  <td nowrap class=a><a href=edit_user.php?new=1>�s�W�b��</a></td>
  <td nowrap class=a><a href=user_mgr.php>�b���޲z</a></td>
 </tr>
</table>
<h2 align=center>�b����ƽs��</h2>
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
      echo "<h3>��Ʈw�R�O����</h3>";
    else {
      echo "�w�]�w $login_name ���{�ɱK�X(���]�K�X)�A�n�J��|�j����";
      addUserLog($user_id,$login_name,'���]�K�X','');
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
    if($dbt->val['lock_status']==1) { // ��w
      if(IsSet($_GET['unlock'])) {
        mssql_query("update sUser set lock_status=0,pwd_fail_cnt=0 where user_id=$user_id and lock_status=1");
        addUserLog($user_id,$login_name,'����','');
        echo '<h3>�w�Ѱ���w</h3>';
      }
      else
        echo "<font class=blu_bg><a href=edit_user.php?key0=$user_id&unlock=1>�Ѱ���w</a></font>";
    }
    else if($dbt->val['lock_status']==2) { // ���P
      if(IsSet($_GET['restore'])) {
        mssql_query("update sUser set lock_status=0,pwd_fail_cnt=0,deactivateTime=NULL,chg_pwd_time=NULL where user_id=$user_id and lock_status=2");
        addUserLog($user_id,$login_name,'�٭���P','');
        echo '<h3>�w�٭���P</h3>';
      }
      else
        echo "<font class=blu_bg><a href=edit_user.php?key0=$user_id&restore=1>�٭���P</a></font>";
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
  if($dbt->val['userMgr']) { //�b���޲z�̤���ݷ~�Ȩ���
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
    $msg='�s�W';
  }
  else {
    $user_id=$_POST['_user_id'];
    $sql=$dbt->mk_update('sUser',array('user_id'=>$user_id));
    $msg='��s';
    $chg_memo=GetChgMemo(0,array('realName','main_dept_id','sysAdmin','compChk','businessChk','userLevel','extraAuth','business_code','can_edit','can_del','can_create','userMgr'),
    $dbt->val,"user_id=$user_id");
  }
  if(!($result=@mssql_query($sql))) {
    echo "<h3>��Ʈw�R�O����(�i��O�]�b������)</h3>";
    $rtn=0;
    $new_mode=true;
  }
  else {
   $rtn=mssql_rows_affected($db_link);
   if ($ins_mode) {
     $row=mssql_fetch_row($result);
     $user_id=$row[0];
     addUserLog($user_id,$login_name,'�s�W');
   }
   else {
     addUserLog($user_id,$login_name,'�ק�',$chg_memo);
     if($user_id==$user_info['user_id'])
       echo "<h3>�еn�X�HŪ���̷s�]�w</h3>";
   }
  }
  echo "<h3>�Τ�\"$login_name\"���$msg �@ $rtn ��</h3>\n";
  if(!$new_mode)
    $dbt->AddOwnVal('user_id',$user_id);
  $dbt->AddOwnVal('login_name',$login_name);
}
?><form method="POST" action="edit_user.php" name="frmUser">
<p><b>�ϥΪ̰򥻸��</b></p>
<table border="0" cellspacing="1">
 <tr>
   <td>�ѧO�X</td>
   <td><?php
if($new_mode)
  db_field('login_name',FD_TEXTBOX,20);
else {
  echo "<font color=#ff0000>$login_name (�s��$user_id)</font>";
  db_field('user_id',FD_HIDDEN,''); db_field('login_name',FD_HIDDEN,'');
}
?></td></tr><?php
if($new_mode) { ?>
  <tr><td>��l�q��X</td><td><input type=password name=tmp_pwd size=15></td></tr>
  <tr><td>�q��X�T�{</td><td><input type=password name=tmp_pwd2 size=15></td></tr>
<?php }
?><tr><td class=b>�u��m�W</td><td><?php  db_field('realName',FD_TEXTBOX,20); ?></td></tr>
<tr><td class=b>¾��</td><td><?php  db_field('job_title',FD_TEXTBOX,20); ?></td></tr>
<tr><td class=b>���ݳ��</td><td><?php  db_field('main_dept_id',FD_SELECTONE,$_SESSION['dept_ary']); ?></td></tr>
<tr><td class=b>�����v��</td><td><?php  db_field('sysAdmin',FD_CHECKBOX,'�t�κ޲z�� '); db_field('userMgr',FD_CHECKBOX,'�b���޲z�� ');
db_field('compChk',FD_CHECKBOX,'�q���]�֤H�� '); db_field('businessChk',FD_CHECKBOX,'�~�Ƚ]�֤H�� ');
 ?></td></tr>
<tr><td class=b>�~�Ȩ���</td><td><?php  db_radio('userLevel',$usrLevelAry); ?></td></tr>
 <tr><td class=b>�涵�v��</td><td><?php  db_field('can_edit',FD_CHECKBOX,'�i�s��(�t�b�U�g�ޤH��) ');
db_field('can_del',FD_CHECKBOX,'�i�R�� '); db_field('can_create',FD_CHECKBOX,'�i�ؤJ ');
 ?></td></tr>
<tr><td class=b>�~�ȧO</td><td><?php  db_field('business_code',FD_SELECTONE,$bussCodeAry); ?></td></tr>
<tr><td class=b>�B�~�v������</td><td><?php  db_field('extraAuth',FD_TEXTBOX,30); ?></td></tr>
<tr><td colspan=2>�g��i���v������1������A�Ю֥i��1,2�A����i��1,2,3�A���D�ޥi��1,2,3,4<br>�B�~�v���п�J�Ʀr�A�h�ӮɥH�r�����j�A�Ҧp"4,6"�A
��ܸӨϥΪ̰��F�H�W���ť~�A�٥i�ݨ쵥��4��6������<br>�Y�L�B�~�v���A�h�d�ťաI</td></tr>
</table>
<?php
if(!$new_mode)
  echo "<input type=submit value=\"��s\" name=btnUpdate>";
else
  echo "<input type=submit value=\"�s�W\"  name=\"btnInsert\" onClick=\"return check_value(document.frmUser)\">";
?>
 <input type="reset" value="���s�]�w" name="B2"></p>
<?php if(!$new_mode) {
  echo "<table width=18% cellpadding=0><tr><td nowrap class=blu_bg><a href=edit_user.php?new=1&id=$user_id>�H�ثe��Ƭ��d���s�W�@�����</a></td></tr></table>";
?>
<fieldset>
<br>
<legend><span style="background-color: #FFFF93"><b>�{�ɳq��X�]�w</b></span></legend>
<table border=0 cellspacing=1>
 <tr><td width=90>�{�ɳq��X</td><td><input type=password name="tmp_pwd" size=15></td></tr>
 <tr><td>�q��X�T�{</td><td><input type=password name="tmp_pwd2" size=15></td></tr></table>
<input type=submit value="�]�w�{�ɳq��X" name=btnSetTmpPwd onClick="return check_pwd(document.frmUser)">
</fieldset><?php } ?>
</form>
</body></html>
