<?php
/* @var $this yii\web\View */
/* @var $model common\models\CompanyRequest */

$webUrl = Yii::$app->params['staffAppUrl'] . 'company-registration-request-view/' . $model->company_request_uuid;

?>

Good day,

New company account request for <?= $model->company_name ?>

Contact name
<?= $model->contact_name ?>

Contact position
<?= $model->contact_position ?>

Email
<?= $model->company_email ?>

Contact number
<?= $model->phone_number ?>

Requesting for
<?= $model->requesting_for ?>


Check now <?= $webUrl ?>