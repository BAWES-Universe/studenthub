<?php 

mb_internal_encoding("UTF-8");

$nameSections = mb_split( ' ',$model->candidate->candidate_name_ar);

?>
<html>
    <head>
        <title>ID Card</title>
        <style>

            /*
             * Droid Arabic Kufi (Arabic) http://www.google.com/fonts/earlyaccess
             */
             @font-face {
              font-family: 'Droid Arabic Kufi';
              font-style: normal;
              font-weight: 400;
              src: url(//fonts.gstatic.com/ea/droidarabickufi/v6/DroidKufi-Regular.eot);
              src: url(//fonts.gstatic.com/ea/droidarabickufi/v6/DroidKufi-Regular.eot?#iefix) format('embedded-opentype'),
                   url(//fonts.gstatic.com/ea/droidarabickufi/v6/DroidKufi-Regular.woff2) format('woff2'),
                   url(//fonts.gstatic.com/ea/droidarabickufi/v6/DroidKufi-Regular.woff) format('woff'),
                   url(//fonts.gstatic.com/ea/droidarabickufi/v6/DroidKufi-Regular.ttf) format('truetype');
            }

            @font-face {
              font-family: 'Droid Arabic Kufi';
              font-style: normal;
              font-weight: 700;
              src: url(//fonts.gstatic.com/ea/droidarabickufi/v6/DroidKufi-Bold.eot);
              src: url(//fonts.gstatic.com/ea/droidarabickufi/v6/DroidKufi-Bold.eot?#iefix) format('embedded-opentype'),
                   url(//fonts.gstatic.com/ea/droidarabickufi/v6/DroidKufi-Bold.woff2) format('woff2'),
                   url(//fonts.gstatic.com/ea/droidarabickufi/v6/DroidKufi-Bold.woff) format('woff'),
                   url(//fonts.gstatic.com/ea/droidarabickufi/v6/DroidKufi-Bold.ttf) format('truetype');
            }

            html,body {
                font-family: 'Droid Arabic Kufi', 'effra', sans-serif;
                background: #fff;padding:0;margin:0;
            }
            .front-card {width:1132px; float:left;margin-right: 44px;margin-bottom: 100px}
            .top-part{background: #3D4AB8;min-height:982px;text-align: center;position: relative;}
            .code{position: absolute;left: 18px;writing-mode: vertical-rl;text-orientation: mixed;top: 40px;font-size: 62px;color: #E0E0E0;}
            .image{width: 396px; overflow: hidden; border-radius: 50%;position: absolute;top: 20%;left: 33%;border: 10px solid #fff;}
            .name-top{font-size: 120px; position: absolute; right: 84px; bottom: 2px; color: #fff;}
            .bottom-part{background: #fff; height: 756px; position: relative;}
            .name-bottom{position: absolute; right: 84px; top: 8px; font-size: 120px;}
            .back-card {width:1132px;float: left;background: #fff;margin-bottom: 100px;position: relative;padding-bottom: 14px;}
            .qr-code {text-align: center;padding-top: 144px;}
            .qr-code h3 {font-size: 56px;margin-top: 18px;font-weight: normal;color: #2A2728;}
            .address{text-align: right;margin-right: 80px;}
            .contact-detail{line-height: 0;font-size: 44px;margin-top: 170px;}
            .secondry-address{font-size: 50px;line-height: 4.2px;margin-top: 184px;}
            .main-address{font-size: 50px;margin-top: 108px;}
            .uni-label{margin: 0;  font-size: 70px;  font-weight: bold;}
            .uni-data{margin: 0;font-size: 64px;font-weight: normal;}
            .civil-lbl{margin-bottom: 0;font-weight: bold;font-size: 70px;margin-top: 34px;}
            .logo-1 {
                position: absolute; 
                bottom: 88px;
                left: calc((100% - 738px)/2);
                width: 738px;
            }
            .qr{width: 468px;}
        </style>
    </head>
    <body>
        <?php if(!$side || $side == 'front') { ?>

        <div class="front-card">
            <div class="top-part">
                <span class="code"><?= $model->candidate->employeeId ?></span>
                <div class="image">
                    <?php if ($model->candidate->candidate_personal_photo) { ?>
                        <img onerror="this.src='../../../img/no_image.png';"  src="https://sh-payroll.s3.eu-west-2.amazonaws.com/<?=$model->candidate->candidate_personal_photo; ?>" style="width: 100%">
                    <?php } else  {
                        echo \yii\helpers\Html::img('@web/img/no_image.png',['style'=>'width: 100%']);
                    } ?>
                </div>
                <span class="name-top">
                    <?=(isset($nameSections[0])) ? $nameSections[0] : '-'?>
                </span>
            </div>
            <div class="bottom-part">
                <span class="name-bottom">
                    <?=(isset($nameSections[1])) ? $nameSections[1] : '-'?>
                </span>
                <?php echo \yii\helpers\Html::img('@web/img/logo.png',['class'=>'logo-1'])?>
            </div>
        </div>

        <?php } ?> 

        <?php if(!$side || $side == 'back') { ?>

        <div class="back-card">
            <div class="qr-code">
                <?php echo \yii\helpers\Html::img($qrCode->writeDataUri(),['class'=>'qr'])?>
                <h3>https://studenthub.co</h3>
            </div>
            <div class="address">
                <div class="main-address">
                    <p class="uni-label">طالب</p>
                    <p class="uni-data"><?= $model->candidate->university->university_name_ar ?></p>
                    <p class="civil-lbl">الرقم المدني</p>
                    <p style="margin: 0;"><?=$model->candidate->candidate_civil_id;?></p>
                </div>
                <div class="secondry-address">
                    <p>إذا تم العثور على هذه البطاقة</p>
                    <p>يرجى إبلاغ شركة باوس لبرمجة</p>
                    <p>الكمبيوتر</p>
                </div>
                <div class="contact-detail">
                    <p>contact@bawes.net</p>
                    <p>+965 98009771</p>
                </div>
                <?php echo \yii\helpers\Html::img('@web/img/back-logo.png',['style'=>'position: absolute;left: 80px;bottom: 24px;z-index: 999;'])?>
            </div>
        </div>

        <?php } ?>

    </body>
</html>
