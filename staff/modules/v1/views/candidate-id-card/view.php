<html>
<head>
    <title>ID Card</title>
    <style>
        html,body {background: #fff;padding:0;margin:0;}
        .front-card {width:566px; float:left;margin-right: 22px;margin-bottom: 50px}
        .top-part{background: #3D4AB8;min-height:491px;text-align: center;position: relative;}
        .code{position: absolute;left: 9px;writing-mode: vertical-rl;text-orientation: mixed;top: 20px;font-size: 31px;color: #E0E0E0;}
        .image{width: 198px; overflow: hidden; border-radius: 50%;position: absolute;top: 20%;left: 33%;border: 5px solid #fff;}
        .name-top{font-size: 60px; position: absolute; right: 42px; bottom: 1px; color: #fff;}
        .bottom-part{background: #fff; height: 378px; position: relative;}
        .name-bottom{position: absolute; right: 42px; top: 4px; font-size: 60px;}
        .back-card {width:566px;float: left;background: #fff;margin-bottom: 50px;position: relative;padding-bottom: 7px;}
        .qr-code {text-align: center;padding-top: 72px;}
        .qr-code h3 {font-size: 28px;margin-top: 9px;font-weight: normal;color: #2A2728;}
        .address{text-align: right;margin-right: 40px;}
        .contact-detail{line-height: 0;font-size: 22px;margin-top: 85px;}
        .secondry-address{font-size: 25px;line-height: 2.1px;margin-top: 92px;}
        .main-address{font-size: 25px;margin-top: 54px;}
        .uni-label{margin: 0;  font-size: 35px;  font-weight: bold;}
        .uni-data{margin: 0;font-size: 32px;font-weight: normal;}
        .civil-lbl{margin-bottom: 0;font-weight: bold;font-size: 35px;margin-top: 17px;}
        .logo-1 {position: absolute; bottom: 44px;left: 17%;}
        .qr{width: 234px;}
    </style>
</head>
<body>
<?php
mb_internal_encoding("UTF-8");
$nameSections = mb_split( ' ',$model->candidate->candidate_name_ar);
?>
<div class="front-card">
    <div class="top-part">
        <span class="code"><?=$model->candidate->candidate_uid?></span>
        <div class="image">
            <?php if ($model->candidate->candidate_personal_photo) { ?>
                <img onerror="this.src='../../../img/no_image.png';"  src="https://sh-payroll.s3.eu-west-2.amazonaws.com/"<?=$model->candidate->candidate_personal_photo; ?> style="width: 100%">
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
<div class="back-card">
    <div class="qr-code">
        <?php echo \yii\helpers\Html::img($qrCode->writeDataUri(),['class'=>'qr'])?>
        <h3>https://studenthub.co</h3>
    </div>
    <div class="address">
        <div class="main-address">
            <p class="uni-label">طالب</p>
            <p class="uni-data"><?=$model->candidate->university->university_name_ar?></p>
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
        <?php echo \yii\helpers\Html::img('@web/img/back-logo.png',['style'=>'position: absolute;left: 9px;bottom: 12px;z-index: 999;'])?>
    </div>
</div>
</body>
</html>
