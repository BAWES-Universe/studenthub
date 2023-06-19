<?php

/* @var $this yii\web\View */

$this->title = 'STUDENTHUB';

Yii::$app->formatter->locale = 'ar-KW';

mb_internal_encoding("UTF-8");

$nameSections = mb_split(' ', $candidate->candidate_name_ar);
?>

<div class="main-container" dir="rtl">
 
    <div class="id-container">

        <img class="logo" src="images/logo.svg" />

        <p class="sub-title">بالتعاون مع</p>

        <img class="brand-2" src="images/brand-2.jpg" />

        <img class="brand-1" src="images/brand-1.jpg" />
    </div>

    <?php if($id) { ?>

        <div class="txt-id txt-valid-id">
            <div class="container">
                <span class="pull-left">هوية صالحه لمدة ٣ شهور</span>
            هوية صالحه
            </div>
        </div>
        <?php } else { ?>
        <div class="txt-id txt-invalid-id">
            هوية غير صالحه
        </div>
    <?php } ?>

    <div class="id-container id-detail">
       
        <div class="image">
            <?php if ($candidate->candidate_personal_photo) {
                $path = (YII_ENV == 'prod') ? "candidate-photo/" : "dev/candidate-photo/" ;
                ?>
                <img onerror="this.src='<?= Yii::getAlias("@web") ?>/images/no_image.png';"  src="https://res.cloudinary.com/studenthub/image/upload/w_319,h_319,c_thumb,g_face/v1596453482/<?= $path.$candidate->candidate_personal_photo; ?>" style="width: 100%;min-height: : 100%">
            <?php } else  {
                echo \yii\helpers\Html::img('@web/images/no_image.png',['style'=>'width: 100%;min-height: : 100%']);
            } ?>
        </div>

        <h3 class="txt-first-name"><?=(isset($nameSections[0])) ? $nameSections[0] : '-'?></h3>
        <h3 class="txt-last-name"><?=(isset($nameSections[1])) ? $nameSections[1] : '-'?></h3>

        <?php if($candidate->university) { ?>
        <h3> اسم الجامعة </h3>
        <p><?= $candidate->university->university_name_ar ?></p>
        <?php } ?>

        <h3> الرقم المدنية </h3>
        <p><?= $candidate->candidate_civil_id ?></p>

        <?php if($id) { ?>

            <?php if($candidate->store) { ?>
            <h3>يتدرب في محل</h3>
            <p><?= $candidate->store->store_name ?></p>
            <?php } ?>

            <?php if($candidate->company) { ?>
            <h3>  التابع إلى  </h3>
            <p><?= $candidate->company->company_name ?></p>
            <?php } ?>

            <h3> من تاريخ </h3>
            <p><?= Yii::$app->formatter->asDate($id->updated_at); ?></p>
            <h3>إلى تاريخ</h3>
            <p><?= Yii::$app->formatter->asDate($id->expiry_date); ?></p>
        <?php } ?>
    </div>
   
</div>

<!--<div class="loader"></div>-->
