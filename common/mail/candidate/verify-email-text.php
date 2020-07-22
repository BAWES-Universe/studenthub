<?php
/* @var $this yii\web\View */
/* @var $candidate common\models\Candidate */

?>

Hi, <?= $candidate->firstname ?>

Thanks for joining Pogi. Your code to verify your email is 

<?= $candidate->auth_key ?>