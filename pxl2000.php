<?php 

error_reporting(0);

function readAmp($fd,$pos=0){
  $v = ord($fd{$pos});
  // $v = ($v < 127) ? 127 - $v : $v; // Solarize
  return $v;
}

function drawImg($lines,$filename=false){
  global $finalw, $finalh;
  $h = count($lines);
  $w = count($lines[0]);
  $img = imagecreatetruecolor($w,$h);
  foreach($lines as $y => $line){
    foreach($line as $x => $l){
      $l = ($l > 255) ? 255 : $l;
      $l = ($l < 0) ? 0 : $l;
      $l = intval($l);
      $c = imagecolorallocate($img,$l,$l,$l);
      imagesetpixel($img,$x,$y,$c);
    }    
  }
  $newimg = imagecreatetruecolor($finalw,$finalh);
  imagecopyresampled($newimg,$img,0,0,0,0,$finalw,$finalh,$w,$h);
  if(!empty($filename)){
    imagepng($newimg,$filename);
  } else {   
    header('Content-type: image/png');
    imagepng($newimg);
  }
  imagedestroy($img);
  imagedestroy($newimg);
}

function scaleLine($d,$w){
  if(empty($d)) return false;
  $r = ceil($w / count($d));
  $line = Array();
  if($r > 1){ // If data is smaller than desired line width (scale up)
    for($i=0;$i<$w;$i++){
      for($k=0;($k<$r && $k<$w);$k++){
        if(isset($d[$i])) $line[] = $d[$i];
      }
    }
  } else { // If data is larger than desired line width (scale down)
    for($i=0;$i<$w;$i++){
      if(isset($d[$i])) $line[] = $d[$i]; // TODO:  Do scaling later.
    }

  }
  return $line;  
}

function scaleLineExact($d,$w){
  if(empty($d)) return false;
  $ow = count($d);
  $img = imagecreatetruecolor($ow,1);
  for($i=0;$i<$ow;$i++){
    $l = $d[$i];
    $l = ($l > 255) ? 255 : $l;
    $l = ($l < 0) ? 0 : $l;
    $l = intval($l);
    $c = imagecolorallocate($img,$l,$l,$l);
    imagesetpixel($img,$i,0,$c);
  }
  $d = Array(); // Reset pixel data
  $newimg = imagecreatetruecolor($w,1);
  imagecopyresampled($newimg,$img,0,0,0,0,$w,1,$ow,1);
  imagedestroy($img);
  for($i=0;$i<$w;$i++){
    $c = imagecolorat($newimg,$i,0);
    $d[] = ($c & 0xFF); // Just use blue channel since all channels are equal values.
  }
  return $d;
}

$finalw = 640;
$finalh = 480;

$fh = fopen('./input.raw','r');
$fl = 26580;
$fpos = 0;

$fo = 0;

$line_limit = 96; // Signal vertical resolution 

$threshold = 75;
$min_line_length = 100;
$max_line_length = 1200;

while($fd = fread($fh,$fl)){

  $lines = Array();
  $line = Array();
  $cpos = 0;

  for($i=0;$i<$fl;$i++){
    $p = readAmp($fd,$i);
    $fpos++;
    $cpos++;
    $delta = ($i > 0) ? abs($p - readAmp($fd,$i-1)) : 0;
    // $delta2 = ($i > 4) ? round((abs($p - readAmp($fd,$i-1)) + abs($p - readAmp($fd,$i-2)) + abs($p - readAmp($fd,$i-3)) + abs($p - readAmp($fd,$i-4))) / 4) : 0; // Experimental.  Average of past four deltas.
    $delta2 = ($i > 4) ? abs((abs($p - readAmp($fd,$i-1)) - abs($p - readAmp($fd,$i-4)))) : 0; // Experimental.  Difference between (t-1) and (t-4).
    if(($delta < $threshold) && (count($line) < $max_line_length)){
      // $line[] = $p;
      //$line[] = round((1/$delta2) * 255); // Experimental.  Using the averages of the past four deltas instead of immediate input value.
      // $p = $delta2;
      $v = ($p > 127) ? $p : 127 + (127-$p);
      $v = 255 - ($v * 1.5);
      $line[] = $v;
    } else if(count($line) > $min_line_length){    
      $lines[] = scaleLineExact($line,$max_line_length);
      $line = Array();
      if(count($lines) >= $line_limit){
        $fpos = $fpos - ($fl - $cpos);
        fseek($fh,$fpos);
        break;
      }
    }  
  }

  $filename = 'output/'.sprintf("%02d",$fo).'.png';
  
  drawImg($lines,$filename);
  
  $fo++;

}














?>