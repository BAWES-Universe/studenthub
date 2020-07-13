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
            .front-card {width:638px; height:1011px; float:left;margin-right: 44px;margin-bottom: 100px;}
            .top-part{background: #3D4AB8;min-height:599px;text-align: center;position: relative;}
            .code{position: absolute;left: 18px;writing-mode: vertical-rl;text-orientation: mixed;top: 40px;font-size: 36px;letter-spacing: normal;color: #ffffff;}
            .image{width: 319px;overflow: hidden;border-radius: 50%;position: absolute;top: 10%;left: 23%;border: 10px solid #fff;}
            .name-top{position: absolute; right: 69px; bottom: 25px; font-family: DroidArabicKufi;   font-size: 64px;   font-weight: bold;   font-stretch: normal;   font-style: normal;   line-height: normal;   letter-spacing: normal;   color: #ffffff;}
            .name-bottom{position: absolute; right: 69px; top: 25px; font-family: DroidArabicKufi;   font-size: 64px;   font-weight: bold;   font-stretch: normal;   font-style: normal;   line-height: normal;   letter-spacing: normal;   color: #000000;}
            .bottom-part{background: #fff; height: 412px; position: relative;}
            .back-card {width:638px;height:1011px;float: left;background: #fff;margin-bottom: 100px;position: relative;}
            .qr-code {text-align: center;padding-top: 93px;}
            .qr-code h3 {font-size: 56px;margin-top: 18px;font-weight: normal;color: #2A2728;}
            .address{text-align: right;margin-right: 50px;}
            .contact-detail{line-height: 0;font-size: 44px;margin-top: 170px;}
            .secondry-address{font-size: 24px;line-height: 0.59;margin-top: 36px;}
            .main-address{font-size: 50px;margin-top: 108px;}
            .uni-label{margin: 0;  font-size: 36px;  font-weight: bold;}
            .uni-data{margin: 0;font-size: 36px;font-weight: normal;}
            .civil-lbl{margin-bottom: 0;font-weight: bold;font-size: 36px;margin-top: 9px;}
            .logo-1 {position: absolute;bottom: 61px;left: calc((100% - 437px)/2);}
            .qr{width: 367px;margin: 0 auto;padding: 53px 0;}
            .qr img{width: 74%;}
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
                <?php echo \yii\helpers\Html::img('@web/img/logo.svg',['class'=>'logo-1'])?>
            </div>
        </div>

        <?php } ?> 

        <?php if(!$side || $side == 'back') { ?>

        <div class="back-card">
            <div class="qr-code">
                <div class="qr-inner qr">
                    <?php echo \yii\helpers\Html::img($qrCode->writeDataUri())?>
                </div>
<!--                <h3>https://studenthub.co</h3>-->
            </div>
            <div class="address">
                <div class="main-address">
                    <p class="uni-label">طالب</p>
                    <p class="uni-data"><?= $model->candidate->university->university_name_ar ?></p>
                    <p class="civil-lbl">الرقم المدني</p>
                    <p style="margin: 0;font-size: 36px;"><?=$model->candidate->candidate_civil_id;?></p>
                </div>
                <div class="secondry-address">
                    <p>إذا تم العثور على هذه البطاقة</p>
                    <p>يرجى إبلاغ شركة باوس لبرمجة</p>
                    <p>الكمبيوتر</p>
                </div>
<!--                <div class="contact-detail">-->
<!--                    <p>contact@bawes.net</p>-->
<!--                    <p>+965 98009771</p>-->
<!--                </div>-->
                <?php echo \yii\helpers\Html::img('@web/img/back-logo.svg',['style'=>'position: absolute;left: 80px;left: 36px;bottom: 34px'])?>
            </div>
        </div>

        <?php } ?>

    </body>
</html>
