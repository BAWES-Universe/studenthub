<?php
/* @var $this yii\web\View */
/* @var $candidate common\models\Candidate */

?>

Hi, <?= $candidate->candidate_name ?>

Thanks for joining StudentHub. Your code to verify your email is 

<?= $candidate->candidate_auth_key ?>