<div style="margin-bottom: 20px;text-align: center">
    <?=\yii\helpers\Html::img('images/logo.png',['style'=>'width:200px; margin-bottom:0;'])?>
</div>
<table class="table">
    <tr>
        <td width="32%">
            <?=\yii\helpers\Html::img(Yii::$app->params['candidate_photo'].'candidate-photo/'.$candidate->candidate_personal_photo)?>
        </td>
        <td>
            <table class="table table-bordered" style="font-size: 14px" cellpadding="4">
                <tr>
                    <td width="30%">Name</td>
                    <td><?=$candidate->candidate_name?></td>
                </tr>
                <tr>
                    <td>Age</td>
                    <td><?=strtotime($candidate->candidate_birth_date)?></td>
                </tr>
                <tr>
                    <td>Nationality</td>
                    <td><?=$candidate->country->country_name_en?></td>
                </tr>
                <tr>
                    <td>University</td>
                    <td><?=$candidate->university->university_name_en?></td>
                </tr>
                <tr>
                    <td>Phone</td>
                    <td><?=$candidate->candidate_phone?></td>
                </tr>
                <tr>
                    <td>Driving License</td>
                    <td><?=($candidate->candidate_driving_license) ? 'Yes' : 'no'?></td>
                </tr>
                <tr>
                    <td>Gender</td>
                    <td>
                        <?php
                        if ($candidate->candidate_gender == 1) {
                            echo "Male";
                        } else if ($candidate->candidate_gender == 2) {
                            echo "Female";
                        } else if ($candidate->candidate_gender == 3) {
                            echo "Other";
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>Address</td>
                    <td><?=$candidate->candidate_address_line1?></td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td colspan="2">
            <h5>
                <?php if (!$candidate->store && $candidate->candidate_job_search_status){ ?>
                    Current Job Status : Candidate looking for a job actively
                <?php } else if (!$candidate->store && !$candidate->candidate_job_search_status) { ?>
                    Current Job Status : Candidate not looking for a job.
                <?php }?>
            </h5>
        </td>
    </tr>
    <tr>
        <td colspan="2"><hr></td>
    </tr>
    <tr>
        <td colspan="2"><strong>Objective</strong></td>
    </tr>
    <tr>
        <td><?=$candidate->candidate_objective ?></td>
    </tr>

    <tr>
        <td colspan="2"><hr></td>
    </tr>
    <tr>
        <td colspan="2"><strong>Work Experience</strong></td>
    </tr>
    <tr>
        <td>
        <?php if ($candidate->getCandidateExperiences()->count() > 0 ) { ?>
                <ul>
                <?php foreach ($candidate->getCandidateExperiences()->all() as $exp) { ?>
                    <li><?=$exp->experience;?></li>
                <?php } ?>
                </ul>
            <?php } else { ?>
            No working Experience before.
        <?php } ?>
        </td>
    </tr>
    <tr>
        <td colspan="2"><hr></td>
    </tr>
    <tr>
        <td colspan="2" ><strong>Hobbies and Skills</strong></td>
    </tr>
    <tr>
        <td>
        <?php
        if ($candidate->getCandidateSkills()->count() > 0 ) {
            echo "<ul>";
            foreach ($candidate->getCandidateSkills()->all() as $skill) { ?>
                <li><?=$skill->skill;?></li>
            <?php }
            echo "</ul>";
        } else { ?>
            No Skills before
        <?php } ?>
        </td>
    </tr>


    </thead>
</table>

