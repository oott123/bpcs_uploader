#!/usr/bin/php -d disable_functions -d safe_mode=Off
<?php
$a='md5sum ./2.txt';
$b=popen($a,'r');
//$r=substr(fread($b,1024),0,7);
//echo $r;
?>
