<?php

use yii\helpers\Html;

$path = (YII_ENV == 'prod') ?  "candidate-photo/" : "dev/candidate-photo/";

$resumeUrl = Yii::$app->urlManagerVerification->createAbsoluteUrl(['view/resume/'. $candidate->candidate_uid], 'https');

$videoUrl = Yii::$app->urlManagerVerification->createAbsoluteUrl(['view/video/'. $candidate->candidate_uid], 'https');

?>
<div class="outer-border">
    <div class="inner-border">

        <div class="footer">
            <div class="row">
                <div class="col col-xs-2 border text-right">
                    <?=Html::img('images/gold-seal-1.jpg',['style'=>'float:right;']) ?>
                </div>
                <div class="col col-xs-7 text-center border">
                    <h1 style="padding-left: 22px;">Certificate of Appreciation</h1>
                    <h3 class="awarded-to">Awarded to, </h3>
                </div>
                <div class="col col-xs-2 text-right border">
                    <?=Html::img('images/gold-seal-1.jpg',['style'=>'float:right;']) ?>
                </div>
            </div>
        </div>
        <div>
<!--            <h1>Certificate of Appreciation</h1>-->
            <h2 class="name"><?=$candidate->candidate_name?></h2>
            <div class="working-for">IN RECOGNITION OF WORKING FOR</div>
            <div class="company"><?php
                if ($workHistory && $workHistory->company) {
                    $company = (isset($workHistory->company->parentCompany)) ? $workHistory->company->parentCompany : $workHistory->company;
                    echo ($company->company_common_name_en) ? $company->company_common_name_en :  $company->company_name;
                } else {
                    echo 'no company detail';
                }
            ?></div>
            <div class="at">At</div>
            <div class="store"><?=($workHistory && $workHistory->store) ? $workHistory->store->store_name : '-';?></div>
            <div class="date">From <?=date('F j, Y',strtotime($workHistory->start_date))?> to <?=($workHistory->end_date) ? date('F j, Y',strtotime($workHistory->end_date)) : 'Present'?></div>
        </div>
        <div class="footer">
            <div class="row">
                <div class="col col-xs-5">
                    <div class="col-xs-offset-1 text-left">
                        <?= Html::img('images/signature.jpg',['style'=>'width:200px; margin-bottom:0;']) ?><br/>
                        <span class="auth">Khalid Almutawa<br/>
                        CEO - StudentHub</span>
                    </div>
                </div>
                <div class="col col-xs-6 text-right">
                    <div style="margin-top: 32px;" class="text-right">
                        <?= Html::img('images/logo.png',['style'=>'width:200px; margin-bottom:0;']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
