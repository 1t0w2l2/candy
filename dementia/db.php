<?php
// if(!isset($_SESSION)){ session_start();}
// $link=mysqli_connect("127.0.0.1","memories","y9xBn@66fHx","memories");
// if(!$link) die('連線失敗'); 
// mysqli_query($link,"set charset utf8");
if(!isset($_SESSION)){ session_start();}
$link=mysqli_connect("127.0.0.1","root","","0819");
if(!$link) die('連線失敗'); 
mysqli_query($link,"set charset utf8");
?>
