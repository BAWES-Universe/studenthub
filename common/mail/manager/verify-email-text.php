<?php
/* @var $this yii\web\View */
/* @var $manager common\models\StoreManager */

?>

Hi, <?= $manager->name ?>

Thanks for joining StudentHub. Your code to verify your email is

<?= $manager->auth_key ?>
