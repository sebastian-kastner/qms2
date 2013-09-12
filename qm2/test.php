<?php

$str = " ";
$test = trim($str);
echo "<pre>";
var_dump($test);

echo "<br>";
if($test != "")
$test = explode(" ", $test);

var_dump($test);