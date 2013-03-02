<?php
include_once "lib/SmartString.php";

$text = "I reworked the feed reading signature images I made a while back and added something to trim the strings to a decent length without breaking words. So here you go. A one-liner that will ";

$str = new SmartString("This&nbsp;&nbsp;is &nbsp; &nbsp;    a smart string  with random words for testing", array(
    'font_name' => 'arial',
    ));

$str2 = new SmartString($text,array(
    'line_width'=>45,
));

echo $str->type('width')->limit(100).PHP_EOL."<br>";
echo $str2->type('line')->limit(3);