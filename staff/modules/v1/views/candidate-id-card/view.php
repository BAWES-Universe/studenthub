<?php
mb_internal_encoding("UTF-8");

$nameSections = mb_split(' ', $model->candidate->candidate_name_ar);
$photoSrc = $model->candidate->getPersonalPhotoDataUriForIdCard();

// Base URL for assets (important for Chromium)
$baseUrl =  Yii::getAlias("@web");
?>
<html>

<head>
  <meta charset="UTF-8">
  <title>ID Card</title>
  <style>
    @font-face {
      font-family: 'effra';
      src: url('<?= $baseUrl ?>/fonts/effra_std_bd-webfont.woff2') format('woff2'),
        url('<?= $baseUrl ?>/fonts/effra_std_bd-webfont.woff') format('woff'),
        url('<?= $baseUrl ?>/fonts/effra_std_bd-webfont.ttf') format('truetype');
      font-weight: 700;
      font-style: normal;
    }

    @font-face {
      font-family: 'effra';
      src: url('<?= $baseUrl ?>/fonts/effra_std_rg-webfont.woff2') format('woff2'),
        url('<?= $baseUrl ?>/fonts/effra_std_rg-webfont.woff') format('woff'),
        url('<?= $baseUrl ?>/fonts/effra_std_rg-webfont.ttf') format('truetype');
      font-weight: 400;
      font-style: normal;
    }

    @font-face {
      font-family: 'Droid Arabic Kufi';
      font-style: normal;
      font-weight: 400;
      src: url('<?= $baseUrl ?>/fonts/Droid-Arabic-Kufi/DroidKufi-Regular.ttf') format('truetype');
    }

    @font-face {
      font-family: 'Droid Arabic Kufi';
      font-style: normal;
      font-weight: 700;
      src: url('<?= $baseUrl ?>/fonts/Droid-Arabic-Kufi/DroidKufi-Bold.ttf') format('truetype');
    }

    html,
    body {
      font-family: 'Droid Arabic Kufi', 'effra', sans-serif;
      background: #fff;
      padding: 0;
      margin: 0;
      overflow: hidden;
    }

    .front-card {
      width: 638px;
      height: 1011px;
      float: left;
      margin-bottom: 100px;
      position: relative;
      /* Add relative positioning to contain absolute children */
      overflow: hidden;
      /* Prevent overflow causing blank spaces */
    }

    .name-top {
      position: absolute;
      right: 69px;
      bottom: 25px;
      font-size: 64px;
      font-weight: bold;
      color: #ffffff;
      max-width: 50%;
      /* Limit width to prevent overflow */
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .name-bottom {
      position: absolute;
      right: 69px;
      top: 25px;
      font-size: 55px;
      color: #000000;
      max-width: 50%;
      /* Limit width to prevent overflow */
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .back-card {
      width: 638px;
      height: 1011px;
      float: left;
      background: #fff;
      margin-bottom: 0;
      position: relative;
      /* Contain absolute children */
      overflow: hidden;
      /* Prevent overflow */
    }

    .address {
      text-align: right;
      margin-right: 50px;
      padding-bottom: 50px;
      /* Ensure space for bottom elements */
    }

    .contact-detail {
      margin-top: 15px;
      margin-bottom: 10px;
      font-size: 26px;
      font-weight: 150;
      line-height: 1.40;
    }

    .logo-1 {
      position: absolute;
      bottom: 30px;
      left: calc((100% - 403px)/2);
      z-index: 1;
      /* Ensure it stays on top */
    }

    .top-part {
      background: #3D4AB8;
      min-height: 599px;
      text-align: center;
      position: relative;
    }

    .code {
      position: absolute;
      left: 10px;
      writing-mode: vertical-rl;
      text-orientation: mixed;
      top: 30px;
      font-size: 36px;
      color: #ffffff;
    }

    .image {
      width: 319px;
      height: 319px;
      overflow: hidden;
      border-radius: 50%;
      position: absolute;
      top: 98px;
      left: calc((100% - 319px)/2);
      border: 5px solid #fff;
    }

    .bottom-part {
      background: #fff;
      height: 412px;
      position: relative;
    }

    .qr-code {
      text-align: center;
      padding-top: 93px;
    }

    .secondry-address {
      font-size: 24px;
      line-height: 1.59;
      margin-top: 40px;
    }

    .secondry-address p {
      margin: 0px;
    }

    .main-address {
      font-size: 50px;
      margin-top: 43px;
    }

    .uni-label {
      margin: 0;
      font-size: 36px;
      font-weight: bold;
    }

    .uni-data {
      margin: -9px 0 -4px 0;
      font-size: 36px;
    }

    .civil-lbl {
      margin-bottom: 0;
      font-weight: bold;
      font-size: 36px;
      margin-top: 9px;
    }

    .qr {
      width: 367px;
      margin: 0 auto;
      padding: 0;
    }

    .qr img {
      width: 289px;
      height: 289px
    }

    .txt-url {
      font-size: 28px;
      margin-top: 7px;
    }

    .contact-detail p {
      margin: 0
    }
  </style>
</head>

<body>

  <?php if (!$side || $side == 'front') { ?>
    <div class="front-card">
      <div class="top-part">
        <span class="code"><?= $model->candidate->employeeId ?></span>
        <div class="image">
          <?php if ($photoSrc) { ?>
            <img src="<?= $photoSrc; ?>" style="width: 100%; min-height: 100%">
          <?php } else { ?>
            <?= \yii\helpers\Html::img('@web/images/no_image.png', ['style' => 'width: 100%;min-height: : 100%']); ?>
          <?php } ?>
        </div>
        <span class="name-top"><?= (isset($nameSections[0])) ? $nameSections[0] : '-' ?></span>
      </div>
      <div class="bottom-part">
        <span class="name-bottom"><?= (isset($nameSections[1])) ? $nameSections[1] : '-' ?></span>
        <?= \yii\helpers\Html::img('@web/images/logo.svg', ['class' => 'logo-1']) ?>
      </div>
    </div>
  <?php } ?>

  <?php if (!$side || $side == 'back') { ?>
    <div class="back-card">
      <div class="qr-code">
        <div class="qr-inner qr">
          <?php if ($qrCode) { ?>
            <img src="<?= $qrCode ?>">
          <?php } ?>
        </div>
        <h3 class="txt-url">https://studenthub.co</h3>
      </div>
      <div class="address">
        <div class="main-address">
          <p class="uni-label">طالب</p>
          <p class="uni-data">
            <?php
            $edu = $model->candidate->candidateEducations[0] ?? null;
            if (!$edu) {
              echo "University (not set)";
            } elseif ($edu->education_type === 'standard') {
              echo $edu->university->university_name_ar;
            } else {
              echo $edu->custom_institution_name;
            }
            ?>
          </p>
          <p class="civil-lbl">الرقم المدني</p>
          <p style="margin: -7px 0 0 0;font-size: 36px;"><?= $model->candidate->candidate_civil_id; ?></p>
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
        <?= \yii\helpers\Html::img('@web/images/back-logo.svg', ['style' => 'position: absolute;left: 35px;bottom: 30px']) ?>
      </div>
    </div>
  <?php } ?>

</body>

</html>