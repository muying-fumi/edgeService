<html>
<meta charset='utf-8'>
<title>訊息</title>
<body style='background-color: rgb(255, 245, 230);' align='center'>
<p>
<?php
if(is_array($msg)) {
  foreach($msg as $s) {
	echo HtmlSpecialChars($s),"<br>\n";
  }
}
else echo nl2br(HtmlSpecialChars($msg)); ?>
</p>
<?php
 if (!Empty($url)) {
   echo "<meta http-equiv='refresh' content='5;url=$url'>\n";
   echo "<p><a href='$url'>請點選這裡返回<a>，或是五秒後自動返回</p>";
 }
?>
</body>
</html>