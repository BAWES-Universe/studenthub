<?php

use yii\helpers\Html;

$path = (YII_ENV == 'prod') ?  "candidate-photo/" : "dev/candidate-photo/";

?>
<div class="row text-left">
    <?= Html::img('images/logo.png',['style'=>'width:200px; margin-bottom:0;']) ?>
</div>
<div class="row">
    <h4 style="text-align: center;">Candidate Report</h4><br/>
    <h4 style="text-align: center;">Department: <strong><?=$report->getDepartment()?></strong> About <strong><?=strtolower($report->candidate->candidate_name)?></strong></h4><br/>
    <h4 style="text-align: center;">Duration : <?=Yii::$app->formatter->asDate($report->start_date)?> - <?=Yii::$app->formatter->asDate($report->end_date)?></h4><br/>
    <div class="">
        <div>Created On: <b><?=Yii::$app->formatter->asDate($report->created_at)?></b></div>
        <div>Created By: <b><?=$report->staff->staff_name?></b></div>
    </div>
    <br/>
    <br/>
    <div>
        <table width="100%" border="1" cellpadding="12">
            <tr>
                <th>Question</th>
                <th>Rating</th>
                <th>Answer</th>
            </tr>
            <?php foreach($report->questionAnswer as $answer) { ?>
                <tr>
                    <td><?=$answer->question?></td>
                    <td><?=$answer->rating?></td>
                    <td><?=$answer->answer?></td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>
