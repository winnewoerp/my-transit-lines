<?php

session_start();
unset($_SESSION['rand_code']);
$string = '';

for ($i = 0; $i < 5; $i++) {
	// this numbers refer to numbers of the ascii table (lower case)
	$letters='ABDEFGHJLMNRTabdefhjmnqrt2345678';
	$string .= substr($letters,rand(0,strlen($letters)),1);
}

$_SESSION['rand_code'] = $string;

$dir = 'fonts/';

$imgwidth=200;
$imgheight=90;

$image = imagecreatetruecolor($imgwidth, $imgheight);
$bgr=rand(203,241);
$bgg=rand(203,241);
$bgb=rand(203,241);
$background = imagecolorallocate($image, $bgr, $bgg, $bgb);


imagefilledrectangle($image,0,0,399,99,$background);
$font=array('ahg-bold.ttf','swissbo.ttf');
for($i=0;$i<=strlen($string);$i++) {
$rline=rand(102,203);
$gline=rand(102,203);
$bline=rand(102,203);
$linecolor=imagecolorallocate($image,$rline,$bline,$gline);
$x1=rand(0,$imgwidth);
$x2=rand(0,$imgwidth);
imageline($image, $x1, 0, $x2, $imgheight, $linecolor);
$r=rand(51,153);
$g=rand(51,153);
$b=rand(51,153);
$color = imagecolorallocate($image, $r, $g, $b);
$angle=rand(-32,32);
$randfont=rand(0,sizeof($font)-1);
$fontsize=rand(21,41);
imagettftext ($image,$fontsize, $angle, 10+$i*38, 60, $color, $dir.$font[$randfont], substr($string,$i,1));
}

header("Content-type: image/png");
imagepng($image);

?>
