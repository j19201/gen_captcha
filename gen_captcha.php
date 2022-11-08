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
        $answer_length = count($answer_array);
        $blank_count_array = [4, 4, 3, 3, 4, 3, 4, 3, 1, 2, 2, 2, 4, 3, 4, 3, 4, 4, 4, 2, 3, 2, 4, 2, 1, 3];//欠損部形状数の最大値をあらかじめ設定しておく

        $width = 48*($answer_length-1)+40;
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
            //反転ノイズを追加する（一文字につき一つ＆文字と文字の間も含む）
            //文字を対象とするか、空白を対象とするかを決定（0=文字,1=空白）
            $str_or_space = mt_rand(0,1);
            //最後の文字かどうか
            if($index == $answer_length-1){  
                //最後の文字の場合は強制的に文字を対象とする
                $str_or_space = 0;
            }
            echo $str_or_space."<br>";
            $noise_x = null;
            $noise_y = null;
            //対象が文字ならば
            if($str_or_space == 0){
                //元イメージで白の領域を選択するまでランダム
                while(True){
                    //位置をランダムに決定（ドット毎）
                    $noise_x = mt_rand(0,4);
                    $noise_y = mt_rand(0,4);
                    $rgb = imagecolorat($alphabets_img, 
                        (array_search($ans,$alphabets)*40) + ($noise_x*8), 
                        $noise_y*8);
                    $colors = imagecolorsforindex($alphabets_img, $rgb);
                    //$colors["alpha"]が127の時は白
                    if($colors["alpha"] == 127){
                        break;
                    }
                }
            //空白ならば
            }else{
                $noise_x = 5;
                $noise_y = mt_rand(0,4);
            }
            $noise_ox = ($index * 48) + ($noise_x * 8);
            $noise_oy = $noise_y * 8;
            //塗りつぶし用の色を用意する
            $noise_color = imagecolorallocatealpha($result,0,0,0,0);
            //実際に塗りつぶす
            imagefilledrectangle(
                $result,
                $noise_ox,
                $noise_oy,
                $noise_ox+8,
                $noise_oy+8,
                $noise_color
            );
            //カバー画像に透明になった場所を塗りつぶす
            imagefilledrectangle(
                $cover,
                $noise_ox,
                $noise_oy,
                $noise_ox+8,
                $noise_oy+8,
                $cover_color
            );
        }
        //ランダムな幅にリサイズする
        $str_widths = [];
        $spaces = [];
        $width = 0;
        //文字幅をランダムに決定する
        for($i=0;$i<$answer_length*6-1;$i++){
            $tmp = mt_rand(2,12);
            $str_widths[] = $tmp;
            $width+=$tmp;
        }

        //スクレイピングによる被害を軽減するために、画像サイズをランダムに拡張
        $result_space = array(
            "x" => mt_rand(10,50),
            "y" => mt_rand(10,50)
        );
        $cover_space = array(
            "x" => mt_rand(10,50),
            "y" => mt_rand(10,50)
        );

        //文字を置く場所もランダムに決定
        $result_place = array(
            "x" => mt_rand(0,$result_space["x"]),
            "y" => mt_rand(0,$result_space["y"])
        );
        $cover_place = array(
            "x" => mt_rand(0,$cover_space["x"]),
            "y" => mt_rand(0,$cover_space["y"])
        );

        //画像をリサイズ
        //欠損画像用の空画像を生成
        $resized_result= imagecreatetruecolor($width+$result_space["x"],40+$result_space["y"]);
        //アルファチャンネルを保存するための処理群
        //ブレンドモードを無効にする
        imagealphablending($resized_result, false);
        //完全なアルファチャネル情報を保存するフラグをonにする
        imagesavealpha($resized_result, true);
        //背景を塗りつぶす
        imagefilledrectangle($resized_result,0,0,$width+$result_space["x"],40+$result_space["y"],$bg);

        //カバー画像用の空画像を生成
        $resized_cover= imagecreatetruecolor($width+$cover_space["x"],40+$cover_space["y"]);
        //アルファチャンネルを保存するための処理群
        //ブレンドモードを無効にする
        imagealphablending($resized_cover, false);
        //完全なアルファチャネル情報を保存するフラグをonにする
        imagesavealpha($resized_cover, true);
        //背景を塗りつぶす
        imagefilledrectangle($resized_cover,0,0,$width+$cover_space["x"],40+$cover_space["y"],$bg);
        //貼り付け
        $dst_x = 0;
        foreach($str_widths as $index => $dot_width){
            //文字をコピー
            imagecopyresized(
                $resized_result,//$dst_image
                $result,//$src_image
                $dst_x+$result_place["x"],//$dst_x
                $result_place["y"],//$dst_y
                $index * 8,//$src_x
                0,//$src_y
                $dot_width,//$dst_width
                40,//$dst_height
                8,//$src_width
                40//$src_height
            );
            //カバー画像をコピー
            imagecopyresized(
                $resized_cover,//$dst_image
                $cover,//$src_image
                $dst_x+$cover_place["x"],//$dst_x
                $cover_place["y"],//$dst_y
                $index * 8,//$src_x
                0,//$src_y
                $dot_width,//$dst_width
                40,//$dst_height
                8,//$src_width
                40//$src_height
            );
            $dst_x = $dst_x + $dot_width;
        }
        /*
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
        */

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

        $captcha_imgs = array(
            "base" => $resized_result_img,
            "cover" => $resized_cover_img
        );

        return $captcha_imgs;
    }

    //base64であらかじめエンコードされた画像を返す（元データ：alphabets.png）
    static function gen_alphabets_img(){
        $img = "iVBORw0KGgoAAAANSUhEUgAABBAAAAAoCAYAAABHAwkjAAAMzklEQVR4Ae3BwY3kABDDQDL/oHUZ6CM0PN5zlUD4NNKFTyNd+PzP5FboZBM6+W3hltwKG3lWeDe5FT6fz/9KboWN3Aob6UInXdjIrbCRW+HFBMKnkS58GunC538mt0Inm9DJbwu35FbYyLPCu8mt8Pl8/ldyK2zkVthIFzrpwkZuhY3cCi8mED6NdOHTSBc+/zO5FTrZhE5+W7glt8JGnhXeTW6Fz+fzv5JbYSO3wka60EkXNnIrbORWeDGB8GmkC59GuvD5n8mt0MkmdPLbwi25FTbyrPBucit8Pp//ldwKG7kVNtKFTrqwkVthI7fCiwmETyNd+DTShc//TG6FTjahk98WbsmtsJFnhXeTW+Hz+fyv5FbYyK2wkS500oWN3AobuRVeTCB8GunCp5EufP5ncit0sgmd/LZwS26FjTwrvJvcCp/P538lt8JGboWNdKGTLmzkVtjIrfBiAuHTSBc+jXTh8z+TW6GTTejkt4Vbcits5Fnh3eRW+Hw+/yu5FTZyK2ykC510YSO3wkZuhRcTCJ9GuvBppAuf/5ncCp1sQie/LdySW2EjzwrvJrfC5/P5X8mtsJFbYSNd6KQLG7kVNnIrvJhA6ORW2MgmdNKFTp4VOrkVNvKs0MmzQifPCp3cCp1sQifvFjayCZ10oZNnhU660EkXbskmbORW2MitsJFbYSO3wkbeLXSyCZ3cChu5FTbShU660EkXOulCJ5vQSRc6eVbopBAIndwKG9mETrrQybNCJ7fCRp4VOnlW6ORZoZNboZNN6OTdwkY2oZMudPKs0EkXOunCLdmEjdwKG7kVNnIrbORW2Mi7hU42oZNbYSO3wka60EkXOulCJ13oZBM66UInzwqdFAKhk1thI5vQSRc6eVbo5FbYyLNCJ88KnTwrdHIrdLIJnbxb2MgmdNKFTp4VOulCJ124JZuwkVthI7fCRm6FjdwKG3m30MkmdHIrbORW2EgXOulCJ13opAudbEInXejkWaGTQiB0citsZBM66UInzwqd3AobeVbo5Fmhk2eFTm6FTjahk3cLG9mETrrQybNCJ13opAu3ZBM2cits5FbYyK2wkVthI+8WOtmETm6FjdwKG+lCJ13opAuddKGTTeikC508K3RSCIROboWNbEInXejkWaGTW2EjzwqdPCt08qzQya3QySZ08m5hI5vQSRc6eVbopAuddOGWbMJGboWN3AobuRU2cits5N1CJ5vQya2wkVthI13opAuddKGTLnSyCZ10oZNnhU4KgdDJrbCRTeikC508K3RyK2zkWaGTZ4VOnhU6uRU62YRO3i1sZBM66UInzwqddKGTLtySTdjIrbCRW2Ejt8JGboWNvFvoZBM6uRU2citspAuddKGTLnTShU42oZMudPKs0EkhEDq5FTayCZ10oZNnhU5uhY08K3TyrNDJs0Int0Inm9DJu4WNbEInXejkWaGTLnTShVuyCRu5FTZyK2zkVtjIrbCRdwudbEInt8JGboWNdKGTLnTShU660MkmdNKFTp4VOikEQie3wkY2oZMudPKs0MmtsJFnhU6eFTp5VujkVuhkEzp5t7CRTeikC508K3TShU66cEs2YSO3wkZuhY3cChu5FTbybqGTTejkVtjIrbCRLnTShU660EkXOtmETrrQybNCJ4VA+FySZ4VOboXPk6QL/zfZhE7eLWykC51sQifPCp10oZMubKQLnXRhI7fC3ya3wueSbEInm9DJrdBJFzrpQidd6KQLnXShk03o5Fmhk2eFTgYC4XNJnhU6uRU+T5Iu/N9kEzp5t7CRLnSyCZ08K3TShU66sJEudNKFjdwKf5vcCp9LsgmdbEInt0InXeikC510oZMudNKFTjahk2eFTp4VOhkIhM8leVbo5Fb4PEm68H+TTejk3cJGutDJJnTyrNBJFzrpwka60EkXNnIr/G1yK3wuySZ0sgmd3AqddKGTLnTShU660EkXOtmETp4VOnlW6GQgED6X5Fmhk1vh8yTpwv9NNqGTdwsb6UInm9DJs0InXeikCxvpQidd2Mit8LfJrfC5JJvQySZ0cit00oVOutBJFzrpQidd6GQTOnlW6ORZoZOBQPhckmeFTm6Fz5OkC/832YRO3i1spAudbEInzwqddKGTLmykC510YSO3wt8mt8LnkmxCJ5vQya3QSRc66UInXeikC510oZNN6ORZoZNnhU4GAuFzSZ4VOrkVPk+SLvzfZBM6ebewkS50sgmdPCt00oVOurCRLnTShY3cCn+b3AqfS7IJnWxCJ7dCJ13opAuddKGTLnTShU42oZNnhU6eFToZCITPJXlW6ORW+DxJuvB/k03o5N3CRrrQySZ08qzQSRc6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";    
        return $img;
    }

    //動作に必要なjavascriptを返す
    public static function return_javascript(){
        $script = "if ( window.addEventListener ){window.addEventListener('load', cmanOM_JS_init, false);} else if( window.attachEvent ) {window.attachEvent( 'onload', cmanOM_JS_init );} var cmanOM_VAR= {}; var cmanOM_Obj= []; var cmanOM_OyaObj= []; function cmanOM_JS_init(){ var wTargetTag= [ 'img', 'div' ]; var wTagList= []; var wObjAt; cmanOM_VAR['moveOn']= false; if ('ontouchstart' in window) { cmanOM_VAR['device']='mobi';}else{ cmanOM_VAR['device']='pc';} for(var i= 0; i < wTargetTag.length; i++){ var wHtmlCollection= document.getElementsByTagName(wTargetTag[i]); for(var j= 0; j < wHtmlCollection.length; j++){ wTagList.push( wHtmlCollection[j] );}} for(var i= 0; i < wTagList.length; i++){ wObjAt= wTagList[i].getAttribute('cmanOMat'); if((wObjAt=== null)||(wObjAt=='')){ }else{ if(wObjAt.toLowerCase().match(/move/)){ cmanOM_Obj.push( wTagList[i] );}} } for(var i= 0; i < cmanOM_Obj.length; i++){ if(cmanOM_Obj[i].style.position.toLowerCase() != 'absolute'){ var wObjStyle= window.getComputedStyle(cmanOM_Obj[i], null); var wOyaDiv= document.createElement('div'); wOyaDiv.setAttribute('id', 'cmanOM_ID_DMY'+i); wOyaDiv.style.position= 'relative'; wOyaDiv.style.width= cmanOM_Obj[i].offsetWidth + 'px'; wOyaDiv.style.height= cmanOM_Obj[i].offsetHeight + 'px'; wOyaDiv.style.marginTop= wObjStyle.marginTop
            wOyaDiv.style.marginRight= wObjStyle.marginRight
            wOyaDiv.style.marginBottom= wObjStyle.marginBottom
            wOyaDiv.style.marginLeft= wObjStyle.marginLeft
            if(cmanOM_Obj[i].tagName.toLowerCase()== 'img'){ wOyaDiv.style.display= 'inline-block';} var wParentDiv= cmanOM_Obj[i].parentNode; wParentDiv.insertBefore(wOyaDiv, cmanOM_Obj[i]); var wCopyNode= cmanOM_Obj[i].cloneNode(true); wCopyNode.style.position= 'absolute'; wCopyNode.style.top= 0; wCopyNode.style.left= 0; wCopyNode.style.margin= 0; document.getElementById('cmanOM_ID_DMY'+i).appendChild(wCopyNode); cmanOM_Obj[i].parentNode.removeChild(cmanOM_Obj[i]); cmanOM_Obj[i]= wCopyNode;} wObjAt= cmanOM_Obj[i].getAttribute('cmanOMat'); if(wObjAt.toLowerCase().match(/movearea/)){ cmanOM_OyaObj[i]= ''; var wOyaObj= cmanOM_Obj[i]; for(var j= 0; j < 20; j++){ wOyaObj= wOyaObj.parentNode; if((typeof wOyaObj=== 'object')&&(wOyaObj.tagName.toLowerCase() != 'html')){ wObjAt= wOyaObj.getAttribute('cmanOMat'); if((wObjAt=== null)||(wObjAt=='')){ }else{ if(wObjAt.toLowerCase().match(/area/)){ cmanOM_OyaObj[i]= wOyaObj; break;}} }else{ break;}} } if (cmanOM_VAR['device']== 'mobi') { cmanOM_Obj[i].ontouchstart= cmanOM_JS_mdown; cmanOM_Obj[i].ontouchend= cmanOM_JS_mup; cmanOM_Obj[i].ontouchmove= cmanOM_JS_mmove;}else{ cmanOM_Obj[i].onmousedown= cmanOM_JS_mdown; cmanOM_Obj[i].onmouseup= cmanOM_JS_mup; cmanOM_Obj[i].onmousemove= cmanOM_JS_mmove; cmanOM_Obj[i].onmouseout= cmanOM_JS_mout;} cmanOM_Obj[i].style.cursor= 'pointer'; cmanOM_Obj[i].setAttribute('cmanOMno', i);}} function cmanOM_JS_mdown(e){ cmanOM_VAR['moveOn']= false; var wTarget= e.target || e.srcElement; var wObjAt= wTarget.getAttribute('cmanOMat'); if((wObjAt=== null)||(wObjAt=='')){ }else{ if(wObjAt.toLowerCase().match(/move/)){ cmanOM_VAR['moveOn']= true;}} if(!cmanOM_VAR['moveOn']){return;} for(var i= 0; i < cmanOM_Obj.length; i++){ if(cmanOM_Obj[i].style.zIndex != 1){ cmanOM_Obj[i].style.zIndex= 1;}} cmanOM_VAR['objNowImg']= wTarget; if (cmanOM_VAR['device']== 'mobi') { cmanOM_VAR['sPosX']= e.touches[0].pageX; cmanOM_VAR['sPosY']= e.touches[0].pageY;}else{ cmanOM_VAR['sPosX']= e.pageX; cmanOM_VAR['sPosY']= e.pageY;} if(cmanOM_VAR['objNowImg'].style.top== ''){ cmanOM_VAR['sTop']= 0;}else{ cmanOM_VAR['sTop']= parseInt(cmanOM_VAR['objNowImg'].style.top.replace('px', ''));} if(cmanOM_VAR['objNowImg'].style.left== ''){ cmanOM_VAR['sLeft']= 0;}else{ cmanOM_VAR['sLeft']= parseInt(cmanOM_VAR['objNowImg'].style.left.replace('px', ''));} cmanOM_VAR['objNowImg'].style.zIndex= 2; return false;} function cmanOM_JS_mup(e){ cmanOM_VAR['moveOn']= false;} function cmanOM_JS_mout(e){ cmanOM_VAR['moveOn']= false;} function cmanOM_JS_mmove(e){ if(!cmanOM_VAR['moveOn']){return;} var wObjStyle= window.getComputedStyle(cmanOM_VAR['objNowImg'].parentNode, null); var wObjNo= -1; var wObjAt= cmanOM_VAR['objNowImg'].getAttribute('cmanOMno'); if((wObjAt=== null)||(wObjAt=='')){ }else{ wObjNo= parseInt(wObjAt);} if (cmanOM_VAR['device']== 'mobi') { cmanOM_VAR['objNowImg'].style.top= cmanOM_VAR['sTop'] - ( cmanOM_VAR['sPosY'] - e.touches[0].pageY) + 'px'; cmanOM_VAR['objNowImg'].style.left= cmanOM_VAR['sLeft'] - ( cmanOM_VAR['sPosX'] - e.touches[0].pageX) + 'px';}else{ cmanOM_VAR['objNowImg'].style.top= cmanOM_VAR['sTop'] - ( cmanOM_VAR['sPosY'] - e.pageY) + 'px'; cmanOM_VAR['objNowImg'].style.left= cmanOM_VAR['sLeft'] - ( cmanOM_VAR['sPosX'] - e.pageX) + 'px';} if(wObjNo < 0){ }else{ if( typeof cmanOM_OyaObj[wObjNo]== 'object'){ var wOyaRect= cmanOM_OyaObj[wObjNo].getBoundingClientRect(); var wObjRect= cmanOM_VAR['objNowImg'].getBoundingClientRect(); var wTop= 0; var wLeft= 0; if(wOyaRect.top > wObjRect.top){ wTop += wOyaRect.top - wObjRect.top;} if(wOyaRect.left > wObjRect.left){ wLeft += wOyaRect.left - wObjRect.left;} if((wOyaRect.top + wOyaRect.height) < (wObjRect.top + wObjRect.height)){ wTop += (wOyaRect.top + wOyaRect.height) - (wObjRect.top + wObjRect.height);} if((wOyaRect.left + wOyaRect.width) < (wObjRect.left + wObjRect.width)){ wLeft += (wOyaRect.left + wOyaRect.width) - (wObjRect.left + wObjRect.width);} if(wTop != 0){cmanOM_VAR['objNowImg'].style.top= parseInt(cmanOM_VAR['objNowImg'].style.top.replace('px', '')) + wTop + 'px';} if(wLeft != 0){cmanOM_VAR['objNowImg'].style.left= parseInt(cmanOM_VAR['objNowImg'].style.left.replace('px', '')) + wLeft + 'px';}} } return false;} ";
        return $script;
    }


}
$res = gen_captcha::gen_image("ABCDE");
$script = gen_captcha::return_javascript();
?>
<script type="text/javascript"><?php echo $script ?></script>
<img src="data:image/png;base64,<?php echo $res["base"]?>" cmanOMat="move">
<br>
<img src="data:image/png;base64,<?php echo $res["cover"]?>" cmanOMat="move">

