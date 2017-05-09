<?php

/* @var $this yii\web\View */

$this->title = 'STUDENTHUB';

Yii::$app->formatter->locale = 'ar-KW';

?>

<div class="main-container" dir="rtl">
    <section class="text-center">
        <div class="container">
            <div class="row">
                <div class="col-sm-10 col-md-8">
                    <h1><?= $candidate->candidate_name_ar ?></h1>

                    <?php if($candidate) { ?>
                    <h2>الرقم المدني <?= $candidate->candidate_civil_id ?></h2>
                    <?php } ?>

                    <?php if($university) { ?>
                    <h2>طالب في <?= $university->university_name_ar ?></h2>
                    <?php } ?>

                    <?php if($company) { ?>
                    <h2>يعمل لدى <?= $company->company_name ?></h2>
                    <?php } ?>

                    <?php if($store) { ?>
                    <h2>المحل <?= $store->store_name ?></h2>
                    <?php } ?>

                    <?php if($id) { ?>
                    <h2>من تاريخ <?= Yii::$app->formatter->asDate($id->updated_at); ?></h2>
                    <h2>إلى <?= Yii::$app->formatter->asDate($id->expiry_date); ?></h2>
                    <?php } ?>
                </div>
            </div>
            <!--end of row-->
        </div>
        <!--end of container-->
    </section>
</div>

<!--<div class="loader"></div>-->