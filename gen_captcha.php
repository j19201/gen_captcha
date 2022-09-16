<?php
class gen_captcha{
    public static function gen_image($answer){
        //ファイルから直接アルファベット画像を読み込めないのでbase64から変換する
        $alphabets_img = base64_decode(self::gen_alphabets_img());
        $alphabets_img = imagecreatefromstring($alphabets_img);
        //アルファチャンネルを保存するための処理群
        //ブレンドモードを無効にする
        imagealphablending($alphabets_img, false);
        //完全なアルファチャネル情報を保存するフラグをonにする
        imagesavealpha($alphabets_img, true);

        $alphabets = range("A","Z");
        $answer_array = str_split($answer);
        $answer_length = count($answer_array)-1;
        $blank_count_array = [4, 4, 3, 3, 4, 3, 4, 3, 1, 2, 2, 2, 4, 3, 4, 3, 4, 4, 4, 2, 3, 2, 4, 2, 1, 3];//欠損部形状数の最大値をあらかじめ設定しておく

        $width = 48*$answer_length+40;
        //欠損画像用の空画像を生成
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
        
        //カバー画像用の空画像を生成
        $cover = imagecreatetruecolor($width,40);
        //アルファチャンネルを保存するための処理群
        //ブレンドモードを無効にする
        imagealphablending($cover, false);
        //完全なアルファチャネル情報を保存するフラグをonにする
        imagesavealpha($cover, true);
        //背景を塗りつぶす
        imagefilledrectangle($cover,0,0,$width,40,$bg);
        //カバー画像用の塗りつぶし色を設定
        $cover_color = imagecolorallocatealpha($cover,125,125,125,0);

        foreach($answer_array as $index => $ans){
            //アルファベットを貼り付け
            imagecopy(
                $result,//dst_image
                $alphabets_img,//src_image
                $index * 48,//dst_x
                0,//dst_y
                array_search($ans,$alphabets) * 40,//src_x
                0,//src_y
                40,//src_width
                40//src_height
            );
            $blank_count = mt_rand(1,$blank_count_array[array_search($ans,$alphabets)]);
            //一部を欠損させる
            for($i=0;$i<$blank_count;$i++){
                //黒色が出るまでランダムに座標を取得
                $x = null;
                $y = null;
                while(True){
                    $x = mt_rand($index * 48,$index * 48 + 39);
                    $y = mt_rand(0,39);
                    $rgb = imagecolorat($result, $x, $y);
                    $colors = imagecolorsforindex($result, $rgb);
                    //$colors["alpha"]が0の時は黒
                    if($colors["alpha"] == 0){
                        break;
                    }
                }
                //座標を中心に16*16を透明で塗りつぶす
                $ox = 0;
                $oy = 0;
                if($ox-8 < 0){
                    $ox = $x - 8;
                }
                if($oy-8 < 0){
                    $oy = $y - 8;
                }
                imagefilledrectangle(
                    $result,
                    $ox,
                    $oy,
                    $ox+16,
                    $oy+16,
                    $bg
                );
                //カバー画像に透明になった場所を塗りつぶす
                imagefilledrectangle(
                    $cover,
                    $ox,
                    $oy,
                    $ox+16,
                    $oy+16,
                    $cover_color
                );

            }
        }
        //ランダムな幅にリサイズする
        $str_widths = [];
        $spaces = [];
        $width = 0;
        //欠損画像の文字幅をランダムに決定する
        for($i=0;$i<$answer_length+1;$i++){
            $tmp = mt_rand(20,80);
            $str_widths[] = $tmp;
            $width+=$tmp;
        }
        //欠損画像の空白幅をランダムに決定する
        for($i=0;$i<$answer_length;$i++){
            $tmp = mt_rand(4,16);
            $spaces[] = $tmp;
            $width+=$tmp;
        }
        //画像をリサイズ
        //欠損画像用の空画像を生成
        $resized_result= imagecreatetruecolor($width,40);
        //アルファチャンネルを保存するための処理群
        //ブレンドモードを無効にする
        imagealphablending($resized_result, false);
        //完全なアルファチャネル情報を保存するフラグをonにする
        imagesavealpha($resized_result, true);
        //背景を塗りつぶす
        imagefilledrectangle($resized_result,0,0,$width,40,$bg);

        //カバー画像用の空画像を生成
        $resized_cover= imagecreatetruecolor($width,40);
        //アルファチャンネルを保存するための処理群
        //ブレンドモードを無効にする
        imagealphablending($resized_cover, false);
        //完全なアルファチャネル情報を保存するフラグをonにする
        imagesavealpha($resized_cover, true);
        //背景を塗りつぶす
        imagefilledrectangle($resized_cover,0,0,$width,40,$bg);
        //貼り付け
        $dst_x = 0;
        foreach($answer_array as $index => $ans){
            //文字をコピー
            imagecopyresized(
                $resized_result,//$dst_image
                $result,//$src_image
                $dst_x,//$dst_x
                0,//$dst_y
                $index * 48,//$src_x
                0,//$src_y
                $str_widths[$index],//$dst_width
                40,//$dst_height
                40,//$src_width
                40//$src_height
            );
            //カバー画像をコピー
            imagecopyresized(
                $resized_cover,//$dst_image
                $cover,//$src_image
                $dst_x,//$dst_x
                0,//$dst_y
                $index * 48,//$src_x
                0,//$src_y
                $str_widths[$index],//$dst_width
                40,//$dst_height
                40,//$src_width
                40//$src_height
            );
            $dst_x = $dst_x + $str_widths[$index] + $spaces[$index];
        }

        //元画像をbase64にする
        ob_start();
        ImagePNG($result);
        $img = base64_encode(ob_get_contents());
        ob_end_clean();
        imagedestroy($result);

        //カバー画像をbase64にする
        ob_start();
        ImagePNG($cover);
        $cover_img = base64_encode(ob_get_contents());
        ob_end_clean();
        imagedestroy($cover);

        //縮小された画像をbase64にする
        ob_start();
        ImagePNG($resized_result);
        $resized_result_img = base64_encode(ob_get_contents());
        ob_end_clean();
        imagedestroy($resized_result);

        //縮小されたカバー画像をbase64にする
        ob_start();
        ImagePNG($resized_cover);
        $resized_cover_img = base64_encode(ob_get_contents());
        ob_end_clean();
        imagedestroy($resized_cover);


        return $img.",".$cover_img.",".$resized_result_img.",".$resized_cover_img;
    }

    //base64であらかじめエンコードされた画像を返す（元データ：alphabets.png）
    static function gen_alphabets_img(){
        $img = "iVBORw0KGgoAAAANSUhEUgAABBAAAAAoCAYAAABHAwkjAAAMzklEQVR4Ae3BwY3kABDDQDL/oHUZ6CM0PN5zlUD4NNKFTyNd+PzP5FboZBM6+W3hltwKG3lWeDe5FT6fz/9KboWN3Aob6UInXdjIrbCRW+HFBMKnkS58GunC538mt0Inm9DJbwu35FbYyLPCu8mt8Pl8/ldyK2zkVthIFzrpwkZuhY3cCi8mED6NdOHTSBc+/zO5FTrZhE5+W7glt8JGnhXeTW6Fz+fzv5JbYSO3wka60EkXNnIrbORWeDGB8GmkC59GuvD5n8mt0MkmdPLbwi25FTbyrPBucit8Pp//ldwKG7kVNtKFTrqwkVthI7fCiwmETyNd+DTShc//TG6FTjahk98WbsmtsJFnhXeTW+Hz+fyv5FbYyK2wkS500oWN3AobuRVeTCB8GunCp5EufP5ncit0sgmd/LZwS26FjTwrvJvcCp/P538lt8JGboWNdKGTLmzkVtjIrfBiAuHTSBc+jXTh8z+TW6GTTejkt4Vbcits5Fnh3eRW+Hw+/yu5FTZyK2ykC510YSO3wkZuhRcTCJ9GuvBppAuf/5ncCp1sQie/LdySW2EjzwrvJrfC5/P5X8mtsJFbYSNd6KQLG7kVNnIrvJhA6ORW2MgmdNKFTp4VOrkVNvKs0MmzQifPCp3cCp1sQifvFjayCZ10oZNnhU660EkXbskmbORW2MitsJFbYSO3wkbeLXSyCZ3cChu5FTbShU660EkXOulCJ5vQSRc6eVbopBAIndwKG9mETrrQybNCJ7fCRp4VOnlW6ORZoZNboZNN6OTdwkY2oZMudPKs0EkXOunCLdmEjdwKG7kVNnIrbORW2Mi7hU42oZNbYSO3wka60EkXOulCJ13oZBM66UInzwqdFAKhk1thI5vQSRc6eVbo5FbYyLNCJ88KnTwrdHIrdLIJnbxb2MgmdNKFTp4VOulCJ124JZuwkVthI7fCRm6FjdwKG3m30MkmdHIrbORW2EgXOulCJ13opAudbEInXejkWaGTQiB0citsZBM66UInzwqd3AobeVbo5Fmhk2eFTm6FTjahk3cLG9mETrrQybNCJ13opAu3ZBM2cits5FbYyK2wkVthI+8WOtmETm6FjdwKG+lCJ13opAuddKGTTeikC508K3RSCIROboWNbEInXejkWaGTW2EjzwqdPCt08qzQya3QySZ08m5hI5vQSRc6eVbopAuddOGWbMJGboWN3AobuRU2cits5N1CJ5vQya2wkVthI13opAuddKGTLnSyCZ10oZNnhU4KgdDJrbCRTeikC508K3RyK2zkWaGTZ4VOnhU6uRU62YRO3i1sZBM66UInzwqddKGTLtySTdjIrbCRW2Ejt8JGboWNvFvoZBM6uRU2citspAuddKGTLnTShU42oZMudPKs0EkhEDq5FTayCZ10oZNnhU5uhY08K3TyrNDJs0Int0Inm9DJu4WNbEInXejkWaGTLnTShVuyCRu5FTZyK2zkVtjIrbCRdwudbEInt8JGboWNdKGTLnTShU660MkmdNKFTp4VOikEQie3wkY2oZMudPKs0MmtsJFnhU6eFTp5VujkVuhkEzp5t7CRTeikC508K3TShU66cEs2YSO3wkZuhY3cChu5FTbybqGTTejkVtjIrbCRLnTShU660EkXOtmETrrQybNCJ4VA+FySZ4VOboXPk6QL/zfZhE7eLWykC51sQifPCp10oZMubKQLnXRhI7fC3ya3wueSbEInm9DJrdBJFzrpQidd6KQLnXShk03o5Fmhk2eFTgYC4XNJnhU6uRU+T5Iu/N9kEzp5t7CRLnSyCZ08K3TShU66sJEudNKFjdwKf5vcCp9LsgmdbEInt0InXeikC510oZMudNKFTjahk2eFTp4VOhkIhM8leVbo5Fb4PEm68H+TTejk3cJGutDJJnTyrNBJFzrpwka60EkXNnIr/G1yK3wuySZ0sgmd3AqddKGTLnTShU660EkXOtmETp4VOnlW6GQgED6X5Fmhk1vh8yTpwv9NNqGTdwsb6UInm9DJs0InXeikCxvpQidd2Mit8LfJrfC5JJvQySZ0cit00oVOutBJFzrpQidd6GQTOnlW6ORZoZOBQPhckmeFTm6Fz5OkC/832YRO3i1spAudbEInzwqddKGTLmykC510YSO3wt8mt8LnkmxCJ5vQya3QSRc66UInXeikC510oZNN6ORZoZNnhU4GAuFzSZ4VOrkVPk+SLvzfZBM6ebewkS50sgmdPCt00oVOurCRLnTShY3cCn+b3AqfS7IJnWxCJ7dCJ13opAuddKGTLnTShU42oZNnhU6eFToZCITPJXlW6ORW+DxJuvB/k03o5N3CRrrQySZ08qzQSRc6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";
        
        return $img;
    }
}
$res = explode(",",gen_captcha::gen_image("ABCDE"));
?>
<script src="cmanObjMove_v091.js" charset="utf-8"></script>
<img src="data:image/png;base64,<?php echo $res[0]?>" cmanOMat="move">
<br>
<img src="data:image/png;base64,<?php echo $res[1]?>" cmanOMat="move">
<br>
<img src="data:image/png;base64,<?php echo $res[2]?>" cmanOMat="move">
<br>
<img src="data:image/png;base64,<?php echo $res[3]?>" cmanOMat="move">

