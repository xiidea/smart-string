<?php
include_once "lib/SmartString.php";

$str = new SmartString("This&nbsp;&nbsp;is &nbsp; &nbsp;    a smart string  with random words for testing", array(
    'font_name' => 'arial',
    ));

echo $str->type('width')->limit(100);
