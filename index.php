<?php 
require_once("gen_captcha.php");

$config = new stdClass;
$config->nobreak = "1";
$config->nonoise = "1";
$config->norandomspace = "1";
$res = gen_captcha::gen_image("ABCDE",$config);
$script = gen_captcha::return_javascript();
?>
<script type="text/javascript"><?php echo $script ?></script>
<img src="data:image/png;base64,<?php echo $res["base"]?>" cmanOMat="move">
<br>
<img src="data:image/png;base64,<?php echo $res["cover"]?>" cmanOMat="move">