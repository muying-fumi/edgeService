<?php require_once("cfg.php");
include ("chkLogin.php");

?><html>
<head>
    <meta charset='utf-8'>
    <title>查看專案</title>
    <link rel='stylesheet' type='text/css' href="css/style_v1-1.css?v=<?=time()?>">
</head>
<body>
<?php include("navbar.php"); ?>
<h1>查看專案</h1>
<table align=center>
<tr><th>專案id</th><th>專案全名</th><th>專案簡稱</th><th>隸屬類別id</th><th>隸屬類別名稱</th><th>激活專題與否</th><th>建立時間</th><th>編輯專案</th><th>查看組員</th></tr>
<!--可以手動凍結專案，使得該專案不會出現在主畫面；反之也可以從凍結的狀態激活，可重新在主畫面點選該專案-->
<?php
$result=$db->query("SELECT projects.prj_id,full_name,short_name,cate_id,cate_name,active,projects.create_time,user_prj_rel.admin FROM `projects`
    inner join user_prj_rel on projects.prj_id=user_prj_rel.prj_id
    inner join categories on category_id=cate_id
    where user_prj_rel.user_id=".$user_info["id"]);
while ($row=$result->fetch_assoc()){
    echo "<tr onMouseOver=\"this.style.background='#EAD9FF';\" onMouseOut=\"this.style.background='';\">";
    foreach($row as $key=>$val){
        if ($key=="active")
            echo !$val?"<td><font color=red style='font-weight:bold'>NO</font> <a href=''>激活</a></td>":"<td><font color=green style='font-weight:bold'>Yes</font> <a href=''>凍結</a></td>";
        else if ($key=="admin")
            $admin=$val;
        else
            echo "<td>$val</td>";
    }
    echo $admin?"<td><a href='editProject.php?edit=1&key0=".$row["prj_id"]."'>GO</a></td>":"<td>無編輯權限</td>"; //透過admin看是否有權限編輯專題
    echo "<td><a href='#'>GO</a></td>";
//    echo "<td><a href='editCategory.php?key0=$row[0]'>GO</a></td>";
    echo "</tr>";
}
?>
</table><br><br>
<a href='main.php'>回到主畫面</a>
</body>
</html>