<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Ticket */

?>

Ticket assigned for <?= $model->candidate->candidate_name ?>

<br />

<?= $model->ticket_detail ?>
