<?php
/* @var $this yii\web\View */
/* @var $contact company\models\Contact */

?>

Hi, <?= $contact->contact_name ?>

Thanks for joining StudentHub. Your code to verify your email is

<?= $contact->contact_auth_key ?>
