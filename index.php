<html>
<head>
    <meta charset="utf-8">
    <title>邊緣服務系統</title>
    <link rel="stylesheet" type='text/css' href="css/style_v1-1.css?v=<?=time()?>" >
</head>
<body>
    <h1>邊緣服務系統</h1>
    <h3>可以用來儲存您想分享的資料、並和他人以文字對話！</h3>
    <form id='loginForm' method='post' action='login.php'>
        <font size=3 style='font-weight: bold;'>帳號: </font><input type='text' name='userName' value='test'><br>
        <font size=3 style='font-weight: bold;'>密碼: </font><input type='password' name='userPw' value='test2252'><br>
        <a href='editUser.php?register=1'>註冊</a> ／ <a href='changePassword.htm'>忘記密碼？</a>
        <p><input type='submit' value='登入' onClick='if(checkLoginVal()){feedback.innerHTML="<img src=img/loading.gif width=20>資料傳送中，請稍候...";}'></p>
    </form>
    <h4>您的IP位址：<?php $ip=$_SERVER['REMOTE_ADDR']; echo $ip=='::1' ? '127.0.0.1' : $ip;?></h4>
    <div id='feedback'></div>
</body>
<script>
var userName=document.getElementsByName('userName');
var userPw=document.getElementsByName('userPw');
var feedback=document.getElementById('feedback');

function checkLoginVal(){
  if (userName.value==''){
      alert("請輸入您的帳號!!");
      userName.focus();
      return false;
  }
  if (userPw.value==''){
       alert("請輸入您的密碼!!");
       userPw.focus();
       return false;
  }
return true;
}
</script>
</html>