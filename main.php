<?php include('chkLogin.php');
require_once("cfg.php");
if (!empty($_GET['logout'])){   //登出
//session_unset(); //unset all session variables(not necessarily before session_destroy())
session_destroy();
$msg='登出成功！歡迎再次使用';
$url='index.php';
include('showMsg.php');
exit;
}
?><html>
<head>
    <meta charset='utf-8'>
    <title>歡迎<?php echo $user_info['nickname']; ?>使用邊緣服務系統！</title>
    <link rel="stylesheet" type='text/css' href="css/style_v1-1.css?v=<?=time()?>">
</head>
<body>
    <?php include("navbar.php");
    //var_dump($cate_prj_grp);?>
</body>
</html>