<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Ticket */

?>
<div class="ticket-assigned">

    Ticket assigned for <?= $model->candidate->candidate_name ?>

    <br />

    <?= $model->ticket_detail ?>

</div>

