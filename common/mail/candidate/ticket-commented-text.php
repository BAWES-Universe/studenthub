<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\TicketComment */

?>

    <?php if($model->candidate) { ?>
        New comment from <?= Html::encode($model->candidate->candidate_name) ?>,
    <?php } else if($model->staff) { ?>
        New comment from <?= Html::encode($model->staff->staff_name) ?>,
    <?php } ?>

    "<?= $model->ticket_comment_detail ?>"

