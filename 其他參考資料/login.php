<?php require_once("cfg.php");

function decodePwd($hex){
	$key='Author:Micahel Lee';
	$key_len=strlen($key);
	$big5='';
	for ($i=0,$j=1;$i<strlen($hex);$i+=2){
		$n=hexdec(substr($hex,$i,2));
		$n=$n ^ ord(subStr($key,$j,1));
		$n=$n-ord(substr($key,$key_len-$j-1,1));
		if ($n<0)
			$n+=256;
		$j++;
		if($j>=$key_len)
		  $j=0;
		$big5.=chr($n);
	}
	return $big5;
//	return mb_convert_encoding($big5,'utf-8','big5');
}
$url="index.php";
if(!empty($_POST["login_id"]) && !empty($_POST["login_pw"])) {
  $id=$_POST["login_id"];
  $pwd=$_POST["login_pw"];
}
else {
  $msg="不正常的網頁呼叫";
  include("showmsg.php");
  die;
}
$sql='select * from hrEmployee where user_id='.QuoteStr($id);
$result=$db->query($sql);
if(!$result)
  die('SQL Error');
if($result->num_rows==0) {
   $msg="無此員工！";
   include("showmsg.php");
   die;                                                                       
}
$row = $result->fetch_assoc();
if ($row['user_pass']!='' && decodePwd($row['user_pass'])!=$pwd) {
  $msg="密碼錯誤!";
  include("showmsg.php");
  die;
}
$user_info['id']=$row['emp_id'];
$user_info['name']=$row['emp_name'];
$sql="SELECT IP FROM nlog.hrLogin where login_t>=subtime(now(),'8:00:00') and logout_t is null and op_id=".$user_info['id'].' ORDER BY login_t DESC';
$result=$db->query($sql);
if($row=$result->fetch_row())
  $user_info['ip']=$row[0];
else
  $user_info['ip']='';
setCookie("user_list",$id,time()+86400000);	// remember the last user, 1000 days cookie life time
$_SESSION['user_info']=$user_info;

?><html>
<head>
<title>卡之屋條碼系統 - <?php echo $user_info["name"]; ?></title>
</head>
<frameset rows="28,*" framespacing="0" border="0" frameborder="1">
  <frame name="toolbar" marginwidth="0" marginheight="0" scrolling="no" noresize target="bottom" src="toolbar.php">
  <frame name="bottom"  marginwidth="3" marginheight="3" src="main.php">
  <noframes><body><p>此網頁使用框架,但是您的瀏覽器並不支援.</p></body></noframes>
</frameset>
</html>