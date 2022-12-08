<?php 
require_once("gen_captcha.php");

$res = gen_captcha::gen_image("ABCDE");
$script = gen_captcha::return_javascript();
?>
<script type="text/javascript"><?php echo $script ?></script>
<img src="data:image/png;base64,<?php echo $res["base"]?>" cmanOMat="move">
<br>
<img src="data:image/png;base64,<?php echo $res["cover"]?>" cmanOMat="move">