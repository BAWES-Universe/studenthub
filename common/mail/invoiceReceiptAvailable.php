<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user common\models\User */

?>
<div class="password-reset">

    <p>Hello <?= $transfer->company->company_name ?>,</p>

    <p>Invoice available to download for your payment on <?= $transfer->payment_received_on ?> for transfer #<?= $transfer->transfer_id ?>...</p>


</div>
