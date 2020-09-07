<?php
$path = (YII_ENV == 'prod') ?  "candidate-photo/" : "dev/candidate-photo/";
?>
<div class="row">
    <p class="pull-right" style="text-align: right;font-size: 14px;font-weight: normal;font-stretch: normal;font-style: normal;line-height: normal;letter-spacing: normal; color: #4f4f4f;">
        <?=$candidate->employeeId .'<br/>'. 'Prepared by '.Yii::$app->user->identity->staff_name?>
    </p>
</div>
<div class="row">
    <?=\yii\helpers\Html::img('images/logo.png',['style'=>'width:200px; margin-bottom:0;'])?>
</div>
<div class="row" style="margin-top: 42px;">
    <div class="col-lg-4 col-md-4 col-xl-4 col-xs-4">
        <?=\yii\helpers\Html::img(Yii::$app->params['candidate_photo'].$path.$candidate->candidate_personal_photo)?>
    </div>
    <div class="col-xs-6">
        <h1 style="padding:0; margin:0; height: 50px;font-size: 36px;font-weight: bold;color: #000000;"><?=$candidate->candidate_name?></h1>
        <p style="padding:0; margin:0; font-size: 18px;color: #000000;">
            <?php
                $from = new DateTime($candidate->candidate_birth_date);
                $to   = new DateTime('today');
                echo $from->diff($to)->y.' Years Old';
            ?>
        </p>
        <p style="margin-top:18px;font-size: 24px;font-weight: bold;font-stretch: normal;font-style: normal;line-height: normal;letter-spacing: normal;color: #000000;">
            "<?=$candidate->candidate_objective ?>"
        </p>
        <?php if ($candidate->candidate_phone) { ?>
            <p style="margin-top:18px;">
                <?=\yii\helpers\Html::img('images/ic_phone@3x.png',['width'=>'33'])?>&nbsp;&nbsp;&nbsp;&nbsp;
                <span style=" font-size: 21px;color: #000000;"><?=$candidate->candidate_phone?></span>
            </p>
        <?php } ?>
    </div>
</div>

<div class="row" style="margin-top: 47px;">
    <?php if ($candidate->university) { ?>
    <div class="col-xs-6">
        <div class="pull-left" style="width: 18%">
            <?=\yii\helpers\Html::img('images/university.png',['style'=>'width:70px'])?>

        </div>
        <div class="pull-left"  style="width: 70%">
            <p style="font-size: 18px;color: #333333;  padding-left: 15px;padding-top: 8px">
                <?=$candidate->university->university_name_en?>
            </p>
        </div>
    </div>
    <?php } if ($candidate->candidate_driving_license) { ?>
    <div class="col-xs-4">
        <div class="pull-left" style="width: 28%">
            <?=\yii\helpers\Html::img('images/car_icons.png')?>

        </div>
        <div class="pull-left"  style="width: 70%">
            <p style="font-size: 18px;color: #333333;  padding-left: 15px;">
                Has driving <br/>license
            </p>
        </div>
    </div>
    <?php } ?>
</div>

<div class="row" style="margin-top: 40px;">
    <h1 style="font-size: 24px;font-weight: bold;color: #000000;">Work Experience</h1>
        <?php if ($candidate->getCandidateExperiences()->count() > 0 ) { ?>
            <ul>
                <?php foreach ($candidate->getCandidateExperiences()->all() as $exp) { ?>
                    <li style=" font-size: 18px;color: #000000;"><?=$exp->experience;?></li>
                <?php } ?>
            </ul>
        <?php } else { ?>
            No working Experience before.
        <?php } ?>

</div>

<div class="row">
    <h1 style="font-size: 24px;font-weight: bold;color: #000000;">Hobbies and Skills</h1>
    <?php
        if ($candidate->getCandidateSkills()->count() > 0 ) {
            echo "<ul>";
            foreach ($candidate->getCandidateSkills()->all() as $skill) { ?>
                <li style=" font-size: 18px;color: #000000;"><?=$skill->skill;?></li>
            <?php }
            echo "</ul>";
        } else { ?>
        No Skills before
    <?php } ?>
</div>
