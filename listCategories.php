<?php include("chkLogin.php");
require_once("cfg.php");
chkCateAdmin(); //若非category管理者，不應進得來這個畫面。
?><html>
<head>
    <meta charset='utf-8'>
    <title>查看類別</title>
    <link rel='stylesheet' type='text/css' href="css/style_v1-1.css?v=<?=time()?>">
</head>
<body>
<?php include("navbar.php"); ?>
<h1>查看類別</h1>
<table align=center>
<tr><th>類別id</th><th>類別名稱</th><th>建立時間</th><th>編輯類別名稱</th><th>新增專案</th></tr>
<?php
$result=$db->query("select cate_id, cate_name, create_time from categories where creator_id=".$user_info["id"]);
while ($row=$result->fetch_row()){
    echo "<tr onMouseOver=\"this.style.background='#EAD9FF';\" onMouseOut=\"this.style.background='';\">";
    foreach($row as $val)
        echo "<td>$val</td>";
    echo "<td><a href='editCategory.php?key0=$row[0]'>GO</a></td>";
    echo "<td><a href='editProject.php?new=1&key1=$row[0]'>GO</a></td>";
    echo "</tr>";
}
?>
</table><br><br>
<a href='main.php'>回到主畫面</a>
</body>
</html>