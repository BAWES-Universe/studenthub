<?php
$path = (YII_ENV == 'prod') ?  "candidate-photo/" : "dev/candidate-photo/";
$pathVideo = (YII_ENV == 'prod') ?  "candidate-video/" : "dev/candidate-video/";
use yii\helpers\Html;
?>
<div class="row">
    <p class="pull-right" style="text-align: right;font-size: 14px;font-weight: normal;font-stretch: normal;font-style: normal;line-height: normal;letter-spacing: normal; color: #4f4f4f;">
        <?php echo $candidate->employeeId; ?>
        <?php echo ($candidate->candidate_civil_id)?'<br/>'.$candidate->candidate_civil_id:''; ?>
        <?php echo '<br/>'. 'Prepared by '.Yii::$app->user->identity->staff_name; ?>
    </p>
</div>
<div class="row">
    <?= Html::img('images/logo.png',['style'=>'width:200px; margin-bottom:0;']) ?>
</div>
<div class="row" style="margin-top: 42px;">

    <div class="col-lg-4 col-md-4 col-xl-4 col-xs-4 text-left" style="padding: 0px;">
        
        <?php if($candidate->candidate_personal_photo) { ?>
            <!-- c_thumb,g_face -->
            <img src="https://res.cloudinary.com/studenthub/image/upload/w_400/v1596525812/<?= $path.$candidate->candidate_personal_photo ?>" 
            style='width:200px' />
        <?php } ?>
        
        <div class="" style="margin-top: 47px;">
            <?php if (isset($candidate->nationality)) { ?>
            <div style="margin-bottom: 19px;">
                <div class="pull-left"  style="width: 18%">
                    <?=Html::img('images/globe.png')?>
                </div>
                <div class="pull-left"  style="width: 70%">
                    <p style="font-size: 16px;color: #333333;  padding-left: 15px;padding-top: 8px">
                        <?= $candidate->nationality->country_nationality_name_en ?>
                    </p>
                </div>
            </div>
            <?php } ?>
            <?php if (isset($candidate->university)) { ?>
                <div style="margin-bottom: 19px;">
                    <div class="pull-left" style="width: 18%">
                        <?=Html::img('images/university.png')?>

                    </div>
                    <div class="pull-left"  style="width: 70%">
                        <p style="font-size: 16px;color: #333333;  padding-left: 15px;padding-top: 8px">
                            <?=$candidate->university->university_name_en?>
                        </p>
                    </div>
                </div>
            <?php } if ($candidate->candidate_driving_license) { ?>
                <div>
                    <div class="pull-left" style="width: 18%">
                        <?=Html::img('images/car_icons.png')?>
                    </div>
                    <div class="pull-left"  style="width: 70%">
                        <p style="font-size: 16px;color: #333333;  padding-left: 15px;">
                            Has driving <br/>license
                        </p>
                    </div>
                </div>
            <?php } ?>
        </div>

    </div>
    <div class="col-lg-6 col-md-6 col-xl-6 col-xs-6">
        <h1 style="padding:0; margin:0; height: 50px;font-size: 36px;font-weight: bold;color: #000000;"><?=$candidate->candidate_name?></h1>
        <p style="padding:0; margin:0; font-size: 18px;color: #000000;">
            <?php
                $from = new DateTime($candidate->candidate_birth_date);
                $to   = new DateTime('today');
                echo $from->diff($to)->y.' years old';
            ?>
        </p>
        <p style="margin-top:18px;font-size: 20px;font-weight: bold;color: #000000;">
            "<?=$candidate->candidate_objective ?>"
        </p>
        <?php if ($candidate->candidate_phone) { ?>
            <p style="margin-top:18px;">
                <?=Html::img('images/ic_phone@3x.png',['width'=>'33'])?>&nbsp;&nbsp;&nbsp;&nbsp;
                <span style=" font-size: 21px;color: #000000;"><?=$candidate->candidate_phone?></span>
            </p>
        <?php } if ($candidate->candidate_video) { ?>
        <div class="video" style="margin-top: 36px;">
            <?=Html::a(Html::img('images/video_img.png',['width'=>302]),Yii::$app->params['candidate_video'].$pathVideo.$candidate->candidate_video,['target'=>'_blank'])?>
        </div>
        <?php } ?>
        <div class="row" style="margin-top: 40px;">
            <h1 style="font-size: 24px;font-weight: bold;color: #000000;">Work Experience</h1>
            <?php if ($candidate->getCandidateExperiences()->count() > 0 ) { ?>
                <ul>
                    <?php foreach ($candidate->getCandidateExperiences()->all() as $exp) { ?>
                        <li style=" font-size: 16px;color: #000000;"><?=$exp->experience;?></li>
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
                    <li style=" font-size: 16px;color: #000000;"><?=$skill->skill;?></li>
                <?php }
                echo "</ul>";
            } else { ?>
                No Skills before
            <?php } ?>
        </div>

    </div>
</div>
