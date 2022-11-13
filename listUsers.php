<?php require_once('cfg.php');
include('chkLogin.php');
include('db_util.php');
//權限不同，列出的使用者資料也不同，可以看到的欄位也可能不同。
//權限紀錄在session的user_info裡面(sys_admin)
//*目前只有想到系統管理員和非系統管理員，前者所有資料都顯示，後者只顯示自己的資料
//*應該會透過GET排除掉不需要顯示的欄位，如果都不傳預設就是全部的欄位都要
//sys_admin有權限讀取每一個人的每一個欄位，否則的話這裡只能看到自己的。

if (isset($_POST['btnSearch'])){    //使用者按下查詢，檢查是否有要搜尋資料
    require_once("searching.php");
    $search_ary=array();
    if (!empty($_POST['_user_id']))
        $search_ary[]=inOrEqualOrBetweenSQL('user_id',$_POST['_user_id']);
    if (!empty($_POST['_username']))
        $search_ary[]="username LIKE '".$_POST['_username']."'";
    if (!empty($_POST['_nickname']))
        $search_ary[]="nickname LIKE '".$_POST['_nickname']."'";
    if (!empty($_POST['_active']))  //勾選這個代表只搜尋未激活者
        $search_ary[]="active=0"; //所以active=0
    $dbt->AddValues(array("user_id","username","nickname","active"));   //如果有沒post上來的值，TEXTBOX會帶空值，CHECKBOX會不勾選。
}

//$header_list"一定"要和DB順序相同
$header_list=array('user_id'=>'使用者id','username'=>'使用者名稱','user_pw'=>'密碼','id_no'=>'證件號','email'=>'信箱','nickname'=>'暱稱','sys_admin'=>'系統管理者','cate_admin'=>'類別管理者','join_time'=>'註冊時間','active'=>'激活帳號','last_login_ip'=>'上次登入IP位址');
?>
<html>
<head>
    <meta charset='utf-8'>
    <title>查看使用者詳細資料</title>
    <link rel='stylesheet' type='text/css' href="css/style_v1-1.css?v=<?=time()?>">
</head>
<body>
<?php include("navbar.php"); ?>
<h1>查看使用者詳細資料</h1>
<?php if (chkSysAdmin(null)){ //可輸入過濾條件?>
<div class="center" style="text-align:left; font-weight:bold">
<ul><li>若要過濾id，可輸入以半形逗號(,)分隔的清單、以dash(-)分隔的區間或是單一的數字</li>
<li>若要過濾文字，請輸入要查找的文字片段，並可用*、?代表任意字元</li></ul>
</div>
<form method='post' action='listUsers.php'>
<table align=center class='blueTb'>
    <tr class='blueTb'><th class='blueTb'>依<font color=red>id</font>搜尋</th><th class='blueTb'>依<font color=red>帳號</font>搜尋</th><th class='blueTb'>依<font color=red>暱稱</font>搜尋</th><th class='blueTb'>依是否<font color=red>激活</font>搜尋</th><th class='blueTb'></th></tr>
    <tr class='blueTb'><td class='blueTb'><?php db_field('user_id',FD_TEXTBOX,7);?></td><td class='blueTb'><?php db_field('username',FD_TEXTBOX,20);?></td><td class='blueTb'><?php db_field('nickname',FD_TEXTBOX,20);?></td><td class='blueTb'><?php db_field('active',FD_CHECKBOX,'(勾選代表僅搜尋未激活者)');?></td>
    <td class='blueTb'><input name='btnSearch' type='submit' value='查詢'></td></tr>
</table>
</form>
<?php } ?>
<table align=center>
<?php 
//取得欄位名稱
$result=$db->query('describe users');
//產生標題
//*現在是預設全部欄位都要的情況，如果有傳"不要哪些欄位"，下面while迴圈會去檢查如果$row[0]是傳上來的參數就會unset header_list並continue。
echo '<tr>';
while ($row=$result->fetch_row())
    echo '<th>'.$header_list[$row[0]].'</th>';
echo '<th colspan=2></th>';   //空的<th>代表可擴充欄位(可以在這個header下放非db撈回來內容的選項，如編輯、凍結使用者等(目前就這兩個所以colspan=2))
echo '</tr>';

//*下面這邊是寫死的情況，如果沒有情況是只顯示其中幾個欄位的，就用下面這樣寫死的(這樣的話$header_list有點多此一舉)。
//echo '<tr><th>使用者id</th><th>使用者名稱</th><th>密碼</th><th>證件號</th><th>信箱</th><th>暱稱</th><th>系統管理者</th><th>類別管理者</th><th>註冊時間</th><th>激活帳號</th></tr>';

//select指定資料
if (!chkSysAdmin(null))    //不是系統管理者
    $result=$db->query('select * from users where user_id='.$user_info['id']);        
else{
    if (!empty($search_ary)){//有過濾條件
        $result=$db->query('select * from users where '.implode(" and ",$search_ary));
    }
    else   //無過濾條件
        $result=$db->query('select * from users');
}
while ($row=$result->fetch_assoc()){
    echo "<tr onMouseOver=\"this.style.background='#EAD9FF';\" onMouseOut=\"this.style.background='';\">";
    foreach($header_list as $key=>$val){
        if ($key=='active'){
            if (chkSysAdmin(null)) //系統管理者：有權激活所有使用者
                echo $row[$key]=='1'?'<td><font color=green style="font-weight:bold">YES</font></td>':'<td><font color=red style="font-weight:bold">NO</font> <a href="editUser.php?activate='.$row['user_id'].'">激活</a></td>';    
            else                            //非系統管理者，僅能看有無被激活
                echo $row[$key]=='1'?'<td><font color=green style="font-weight:bold">YES</font></td>':'<td><font color=red style="font-weight:bold">NO</font></td>';
        }
        else if ($key=='sys_admin')
            echo $row[$key]=='1'?'<td><font color=green style="font-weight:bold">YES</font></td>':'<td><font color=red style="font-weight:bold">NO</font></td>';
        else if ($key=='cate_admin'){
            if (chkSysAdmin(null)) //僅系統管理者有權限可以指派/撤銷他人的類別管理者身分(*自己撤銷自己的類別管理者身分也不行)
                echo $row[$key]=='1'?'<td><font color=green style="font-weight:bold">YES</font> <a href="editUser.php?cate_adminX='.$row['user_id'].'">撤銷</a></td>':'<td><font color=red style="font-weight:bold">NO</font> <a href="editUser.php?cate_admin='.$row['user_id'].'">指派</a></td>';
            else
                echo $row[$key]=='1'?'<td><font color=green style="font-weight:bold">YES</font></td>':'<td><font color=red style="font-weight:bold">NO</font></td>';
        }
            
        else
            echo "<td>".$row[$key]."</td>";
    }
    echo '<td><a href="editUser.php?key0='.$row['user_id'].'">編輯</a></td>';
    echo "<td><a onclick='return confirm(\"確定要凍結使用者id=".$row['user_id']."的帳號，使其暫時失效嗎？\");' href='editUser.php?deactivate=".$row['user_id']."'>凍結</a></td>";
    echo '</tr>';
}
?>
</table>
<?php if (!empty($search_ary)){ //可能輸入的條件有誤，導致!$result?>
<h2><?php echo 'where '.implode(" and ",$search_ary); ?></h2>
<?php }?>
<br><br>
<a href='main.php'>返回主畫面</a>
</body>
</html>