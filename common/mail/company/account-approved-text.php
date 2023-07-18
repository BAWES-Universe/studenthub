<?php
/* @var $this yii\web\View */
/* @var $contact company\models\Contact */
/* @var $company company\models\Company */

$webUrl = Yii::$app->params['companyAppUrl'] . 'activate/' . $contact->contact_email . '/' . $contact->contact_auth_key . '/' . $company->company_id;

?>

Good day,

Your account is approved!

Activate now

<?= $webUrl ?>





