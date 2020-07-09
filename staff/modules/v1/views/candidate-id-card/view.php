<html>
<head>
    <title>ID Card</title>
</head>
<body style="background: #000">
<?php
mb_internal_encoding("UTF-8");
$nameSections = mb_split( ' ',$model->candidate->candidate_name_ar);
?>
<div style="width:566px; float:left;margin-right: 22px;margin-bottom: 50px" class="front-card">
    <div class="top-part" style="background: #3D4AB8;min-height:491px;text-align: center;position: relative">
        <span class="code" style="position: absolute;left: 9px;writing-mode: vertical-rl;text-orientation: mixed;top: 20px;font-size: 31px;color: #E0E0E0;"><?=$model->candidate->candidate_uid?></span>
        <div class="image" style="    width: 198px; overflow: hidden; border-radius: 50%;position: absolute;top: 20%;left: 33%;border: 5px solid #fff;">
            <?php if ($model->candidate->candidate_personal_photo) { ?>
                <img onerror="this.src='../../../img/no_image.png';"  src="https://sh-payroll.s3.eu-west-2.amazonaws.com/"<?=$model->candidate->candidate_personal_photo; ?> style="width: 100%">
            <?php } else  {
                echo \yii\helpers\Html::img('@web/img/no_image.png',['style'=>'width: 100%']);
            } ?>
        </div>
        <span class="name-top" style="font-size: 60px; position: absolute; right: 42px; bottom: 1px; color: #fff;">
            <?=(isset($nameSections[0])) ? $nameSections[0] : '-'?>
        </span>
    </div>
    <div class="bottom-part" style="background: #fff; height: 378px; position: relative;">
        <span class="name-bottom" style="position: absolute; right: 42px; top: 4px; font-size: 60px;">
            <?=(isset($nameSections[1])) ? $nameSections[1] : '-'?>
        </span>
        <?php echo \yii\helpers\Html::img('@web/img/logo.png',['style'=>'position: absolute; bottom: 44px;left: 17%;'])?>
    </div>
</div>
<div style="width:566px;float: left;background: #fff;margin-bottom: 50px;position: relative;padding-bottom: 7px;" class="back-card">
    <div class="qr-code" style="
    text-align: center;
    padding-top: 72px;
">
        <?php echo \yii\helpers\Html::img($qrCode->writeDataUri(),['style'=>'width: 234px'])?>
        <h3 style="
    font-size: 28px;
    margin-top: 9px;
    font-weight: normal;
    color: #2A2728;
">https://studenthub.co</h3>
    </div>
    <div class="address" style="
    text-align: right;
    margin-right: 40px;
">
        <div class="main-address" style="
    font-size: 25px;
    margin-top: 54px;
">
            <p style="
    margin: 0;
    font-size: 35px;
    font-weight: bold;
">طالب</p>
            <p style="margin: 0;font-size: 32px;font-weight: normal;"><?=$model->candidate->university->university_name_ar?></p>
            <p style="
    margin-bottom: 0px;
    font-weight: bold;
    font-size: 35px;
    margin-top: 17px;
">الرقم المدني</p>
            <p style="
    margin: 0;
"><?=$model->candidate->candidate_civil_id;?></p>
        </div>
        <div class="secondry-address" style="
    font-size: 25px;
    line-height: 2.1px;
    margin-top: 92px;
">
            <p>إذا تم العثور على هذه البطاقة</p>
            <p>يرجى إبلاغ شركة باوس لبرمجة</p>
            <p>الكمبيوتر</p>
        </div>
        <div class="contact-detail" style="
    line-height: 0;
    font-size: 22px;
    margin-top: 85px;
">
            <p>contact@bawes.net</p>
            <p>+965 98009771</p>
        </div>
        <?php echo \yii\helpers\Html::img('@web/img/back-logo.png',['style'=>'position: absolute;left: 9px;bottom: 12px;z-index: 999;'])?>
    </div>
</div>

</body>
</html>
