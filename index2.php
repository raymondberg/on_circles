<?php

//header("Content-Type: image/jpeg");
$width = 4000;	#original 200
$height =4000; #original 200
$ringWidth = 100; #original 1
$multiplier = 15; #original 2

$inv = false;


$centerX = intval($width / 2);
$centerY = intval($height / 2);
$cumulativeMultiplier = $multiplier;
while(true)
{
	$ringSizeX = $width;
	$ringSizeY = $height;
	// create a 200*200 image

	$img = imagecreatetruecolor($width, $height);

	if($inv)$color = 255;
	else $color = 0;
	if($inv)$stepMod = -$ringWidth;
	else $stepMod = $ringWidth;
	$step = 0;

	$w = true;
	while($ringSizeX > 0 && $ringSizeY > 0)
	{
	imagearc($img, $centerX, $centerY, $ringSizeX, $ringSizeY, 0, 360, imagecolorallocate($img, $color+$step,$color+$step,$color+$step));
		//echo intval(255 / (max(($ringSizeX+$ringSizeY)/2,1)));
		$step += ($cumulativeMultiplier / ($width + $height)) * $stepMod;
		//echo "$color \n";
		if($w) $ringSizeX -= 1;
		else $ringSizeY -= 1;
		$w = !$w;
	}
	imagegif($img, "circle$cumulativeMultiplier.gif");
	echo "\ncircle$cumulativeMultiplier.gif";
	imagedestroy($img);
	$cumulativeMultiplier = $cumulativeMultiplier * $multiplier;
	if($cumulativeMultiplier > 200000000) break;
}

// allocate some colors
// mouth
?>