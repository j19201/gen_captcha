<?php
$alphabets_img = imagecreatefrompng("alphabets2.png");
//アルファチャンネルを保存するための処理群
//ブレンドモードを無効にする
imagealphablending($alphabets_img, false);
//完全なアルファチャネル情報を保存するフラグをonにする
imagesavealpha($alphabets_img, true);

$alphabets = range("A","Z");
$answer_str = "AKGDO";
$answer_str_array = str_split($answer_str);

//$ox = array_search($answer_str_array[0],$alphabets) * 40;
//$alphabets_img = imagesetclip($alphabets_img,$ox,0,$ox+39,39);

$width = 48*4+40;
$result = imagecreatetruecolor($width,40);
//アルファチャンネルを保存するための処理群
//ブレンドモードを無効にする
imagealphablending($result, false);
//完全なアルファチャネル情報を保存するフラグをonにする
imagesavealpha($result, true);
//背景を設定（透明度100％）
$bg = imagecolorallocatealpha($result,0,0,0,127);
//背景を塗りつぶす
imagefilledrectangle($result,0,0,$width,40,$bg);

foreach($answer_str_array as $index => $ans){
    //echo $index.":".$ans."->".array_search($ans,$alphabets)."<br>";
    imagecopy(
        $result,
        $alphabets_img,
        $index * 48,
        0,
        array_search($ans,$alphabets) * 40,
        0,
        40,
        40
    );
}

ob_start();
ImagePNG($result);
$res = base64_encode(ob_get_contents());
ob_end_clean();
imagedestroy($result);

?>
<img src="data:image/png;base64,<?php echo $res?>">

