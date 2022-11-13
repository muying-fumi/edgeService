<h1>您好，<?php echo $user_info['nickname'];?>！ (使用者ID:<?php echo $user_info["id"];?>)</h1>
<a href='main.php?logout=1'><font size=4>登出系統</font></a><br><br>
<?php if (chkSysAdmin(null)){ ?>
<a href='editUser.php?new=1'>新增使用者</a>&emsp;    
<?php } ?>
<a href='editUser.php?key0=<?php echo $user_info['id'];?>'>編輯個人資料</a>&emsp;
<a href='listUsers.php'>查看使用者詳細資料</a>&emsp;
<?php if (chkCateAdmin(null)){?>
<a href='editCategory.php?new=1'>新增類別</a>&emsp;
<a href='listCategories.php'>查看類別</a>&emsp;
<a href='editProject.php?new=1'>新增專案</a>&emsp;
<?php }?>
<a href='editProject.php?edit=1'>編輯專案</a>&emsp; <!--每個人可能都有編輯某專案的權限，不像新增/編輯類別一定要有系統管理者身分，所以一律都給按鈕可以進到編輯頁面-->
<a href='listProjects.php'>查看專案</a>&emsp;
<a href='editGroup.php?new=1'>新增群組</a>&emsp;
<a href='editGroup.php?edit=1'>編輯群組</a>&emsp;
<a href=''>查看群組</a>
