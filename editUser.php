<?php
require_once('cfg.php');
include('db_util.php');

define ('MODE_REG',0);
define ('MODE_NEW',1);
define ('MODE_EDIT',2);
define ('MODE_UPDATE',3);
define ('MODE_INSERT',4);
define ('MODE_ERROR',5);

if(!empty($_GET['register'])){ //註冊無須check login
    $mode=MODE_REG;
}
else{
    include('chkLogin.php');
    if (!empty($_GET['new'])){ //和註冊大致行為一樣，但只有系統管理者可以新增使用者(允許在這個頁面直接指定為系統管理者)。
        $mode=MODE_NEW;
    }
    else if (!empty($_GET['key0'])){
        $mode=MODE_EDIT;
    }
    else if (isSet($_POST['btnUpdate'])){
        $mode=MODE_UPDATE;
    }
    else if (isSet($_POST['btnInsert'])){
        $mode=MODE_INSERT;
    }
    else{
        $mode=MODE_ERROR;
    }    
}

if (!empty($_GET['check'])){
    $result=$db->query("select count(*) from users where username=".quoteStr($_GET['check']));
    $row=$result->fetch_row();
    echo $row[0];
    exit;
}
if (!empty($_GET['activate'])){
    if (!chkSysAdmin())
        die;
    $result=$db->query("update users set active=1 where user_id=".quoteStr($_GET['activate']));
    if (!$result){
        $msg='激活帳號失敗:(';
        $url='listUsers.php';
        include('showMsg.php');
        die;
    }
    $msg='激活帳號成功:D';
    $url='listUsers.php';
    include('showMsg.php');
    exit;
}
if (!empty($_GET['deactivate'])){   //凍結使用者>>active=0
    //系統管理者可以凍結所有人的帳號，但使用者只能凍結自己的，故須根據session裡的id來檢查
    if (!chkSysAdmin("","listUsers.php"))
        die;
    $result=$db->query("update users set active=0 where user_id=".quoteStr($_GET['deactivate']));
    if (!$result){
        $msg='凍結帳號失敗';
        $url='listUsers.php';
        include('showMsg.php');
        die;
    }
    $msg='凍結帳號成功';
    $url='listUsers.php';
    include('showMsg.php');
    exit;
}
if (!empty($_GET['cate_admin'])){
    if (!chkSysAdmin("","listUsers.php"))
        die;
    $result=$db->query("update users set cate_admin=1 where user_id=".quoteStr($_GET['cate_admin']));
    if (!$result){
        $msg='指派為類別管理者失敗';
        $url='listUsers.php';
        include('showMsg.php');
        die;
    }
    $msg='指派為類別管理者成功';
    $url='listUsers.php';
    include('showMsg.php');
    exit;
}
if (!empty($_GET['cate_adminX'])){  //撤銷類別管理者，GET到欲撤銷者的id
    if (!chkSysAdmin("","listUsers.php"))
        die;
    $result=$db->query("update users set cate_admin=0 where user_id=".quoteStr($_GET['cate_adminX']));
    if (!$result){
        $msg='撤銷類別管理者資格失敗';
        $url='listUsers.php';
        include('showMsg.php');
        die;
    }
    $msg='撤銷類別管理者資格成功';
    $url='listUsers.php';
    include('showMsg.php');
    exit;
}

$mode_name=array(MODE_REG=>'註冊',MODE_NEW=>'新增用戶',MODE_EDIT=>'編輯用戶資料',MODE_UPDATE=>'執行編輯',MODE_INSERT=>'執行新增',MODE_ERROR=>'URL有誤');
?><html>
<head>
    <meta charset="utf-8">
    <title><?php echo $mode_name[$mode];?></title>
    <link rel="stylesheet" type='text/css' href="css/style_v1-1.css?v=<?=time()?>" >
</head>
<body>
<?php if ($mode==MODE_ERROR) {?>
<h1>URL有誤</h1>
<?php } else{if ($mode!=MODE_REG) include("navbar.php"); //not error ?>
<!--招呼語-->
<?php if ($mode==MODE_REG){?>
<h1>歡迎註冊！請填寫以下資訊：</h1>
<?php }?>
<?php if ($mode==MODE_NEW){?>
<h1>請填寫新用戶資訊：</h1>
<?php }?>
<h1><?php if ($mode==MODE_EDIT){
$result=$db->query('select * from users where user_id='.$_GET['key0']);
if (!$row=$result->fetch_assoc()){
    echo '帳戶不存在';
    $mode=MODE_ERROR;
}
else{
    echo '目前編輯帳戶: 使用者id='.$row['user_id']; //$row['user_id']==$_GET['key0']
    $dbt->val=$row;    //db抓回來的資料放入$dbt
} ?></h1>
<?php } ?>
<?php if ($mode==MODE_INSERT){ //執行新增?>
<h1><?php
$mode=chkSysAdmin(null)?MODE_NEW:MODE_REG;     //萬一中間哪裡失敗，切換成可新增/註冊使用者的模式(根據是/否為系統管理者)
$dbt->AddValues(array('username','user_pw','id_no','nickname','email'));  //將post上來的這幾個資料加進$dbt裡面
$dbt->AddOwnVal('join_time',date($db_time_fmt));    //將透過cfg.php的date()取得的當前日期時間加進$dbt
$result=$db->query('select count(*) from users where username='.quoteStr($dbt->val['username'])); //$dbt->val['username']在這邊同$_POST['_username']
if ($result->fetch_row()[0]>0){  //query下成功，但是使用者帳號重複
    echo '使用者帳號重複，請更換！<br>';
}
else{   //使用者帳號沒有重複，可嘗試新增使用者資料
    $sql=$dbt->mk_insert('users');
    if(!$db->query($sql)){
        echo 'db有誤<br>';  //可持續嘗試新增資料，可能資料有誤
    }
    else{
        echo '新增用戶成功！<br>';
        $dbt->AddOwnVal('user_id',$db->insert_id);  //加入user_id至$dbt，並可作為type hidden的db_field(方便post的時候傳上去)
        $mode=MODE_EDIT;   //切換為edit模式，可以讓新增資料成功的用戶改資料
    }    
}
} else if ($mode==MODE_UPDATE){  //執行更新
$mode=MODE_EDIT;    //無論更新是否成功，都切換為edit模式(可重複嘗試修改資料)，而非"執行"更新
$dbt->AddValues(array('user_id','username','user_pw','id_no','nickname','email'));  //post上來的這幾個資料放到$dbt(username雖不會更動、但是為了在下面表格持續顯示username，所以仍把他放入$dbt裡面)
if (chkSysAdmin(null)){    //系統管理者有權將該使用者設為/取消系統管理者身分
    if (isSet($_POST['_sys_admin'])) //設為系統管理者
        $dbt->AddOwnVal('sys_admin',1);
    else                           //取消系統管理者(因為沒有把sys_admin POST上來，所以不能用AddValues一次把POST上來的資料放進$dbt。)
        $dbt->AddOwnVal('sys_admin',0);
}
$sql=$dbt->mk_update('users',array('user_id'=>$dbt->val['user_id']),array('user_id','username'));   //user_id、username這兩個不要放到update的set裡(因為這些欄位不會更動)
if (!$db->query($sql))
    echo 'db有誤';
else
    echo '更新用戶資料成功<br>';    
}?>
</h1>
<?php if ($mode!=MODE_ERROR){ ?>
<font style='font-weight:bold;' size=4><u><font color='red'>*</font>為必填</font></u><br><br>
<form id='userInfoForm' method='post' action='editUser.php'>
    <table align='center'>
        <tr><td>帳號(英數字15字以內)<font color='red'>*</font></td><td><?php if($mode!=MODE_EDIT)db_field('username',FD_TEXTBOX,30); else db_field('username',FD_HIDDEN,"<font style='font-weight:bold'>".$dbt->val['username']."</font>");?></td><?php if ($mode!=MODE_EDIT){ ?><td>可用? <input type=button onclick='checkUserNameAvailable(false);' value='check'> <span id='span_check'></span></td><?php } ?></tr>
        <tr><td>密碼(英數字15字以內)<font color='red'>*</font></td><td colspan=2><?php db_field('user_pw',FD_PASSWORD,30);?></td></tr>
        <tr><td>密碼確認<font color='red'>*</font></td><td colspan=2><input type='password' id='userPwConfirm' name='userPwConfirm' size=30></td></tr>
        <tr><td>證件號(如學號)</td><td colspan=2><?php db_field('id_no',FD_TEXTBOX,30);?></td></tr>
        <tr><td>暱稱/短名稱</td><td colspan=2><?php db_field('nickname',FD_TEXTBOX,30);?></td></tr>
        <tr><td>Email</td><td colspan=2><?php db_field('email',FD_TEXTBOX,30);?></td></tr>
        <?php if ($mode==MODE_EDIT || $mode==MODE_NEW){ if (isSet($user_info) && chkSysAdmin(null)){?><tr><td>設為系統管理者</td><td colspan=2><?php db_field('sys_admin',FD_CHECKBOX,'');?></td></tr><?php }}?>
    </table><br>
    <?php if ($mode==MODE_NEW || $mode==MODE_REG){?>
    <input type='submit' name='btnInsert' value='提交' onClick='return checkInput();'>
    <?php } else if ($mode==MODE_EDIT) {  db_field('user_id',FD_HIDDEN,'');?>
    <input type='submit' name='btnUpdate' value='提交' onClick='return checkInput();'>    
    <?php }} ?>
</form><br>
<?php if ($mode!=MODE_REG){?>
<a href='main.php'>返回主畫面</a>
<?php } ?>
<?php } //not error ?>
<script>
//db_field產生的html語法會將id、name前面都加上底線
//其餘的就不加底線
var userName=document.getElementById('_username');
var userPw=document.getElementById('_user_pw');
var userPwConfirm=document.getElementById('userPwConfirm');
var userEmail=document.getElementById('_email');

var span_check=document.getElementById('span_check');

function checkUserNameAvailable(isAlert){
if (userName.value==''){
    alert('帳號不得為空！');
    return false;
}
//ajax
var xhttp = new XMLHttpRequest();
xhttp.onreadystatechange = function() {
    if (this.readyState == 4) {
        if (this.status == 200){
            if (this.responseText=='1'){
                if (isAlert)
                    alert('該帳號已經有人使用，請更改(可利用旁邊的check按鈕檢查帳號是否可用)');
                else
                    span_check.innerHTML='不可使用';
            }
            else if (this.responseText=='0'){
                if (!isAlert)
                    span_check.innerHTML='可使用';
                return true;
            }
            else{
                if (isAlert)
                    alert('系統有誤，請稍後再試');
                else
                    span_check.innerHTML='有誤';
            }
        }
        else{
            if (isAlert)
                alert('網頁無正常回應，錯誤代碼: '+this.status);
            else    
                span_check.innerHTML="error code: "+this.status;
        }
    }
};
if (!isAlert)
    span_check.innerHTML="<img src='img/loading.gif' width=20>";
xhttp.open("GET", "editUser.php?check="+encodeURIComponent(userName.value), true);
xhttp.send();
}

function checkInput(){
//檢查必填的欄位是否都已經填寫了?
if (userName.value==''){
      alert("帳號還沒有填寫！");
      userName.focus();
      return false;
}
if (userPw.value==''){
    alert("密碼還沒有填寫！");
    userPw.focus();
    return false;
}
if (userPwConfirm.value==''){
    alert("密碼確認還沒有填寫！");
    userPwConfirm.focus();
    return false;
}
//檢查密碼和密碼確認是否一樣
if (userPw.value!=userPwConfirm.value){
    alert("輸入的密碼和密碼確認不相同，請重新輸入");
    userPw.select();
    userPwConfirm.value="";
    return false;
}
//簡單檢查email是否有@
if (!userEmail.value.includes('@')){
    alert("請輸入正確的email格式");
    userEmail.select();
    return false;
}
return true;
}
</script>
</body>
</html>