<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user common\models\User */

?>
<div class="password-reset">
    <p>Hello, <?=$detail['company_name']?></p>

    <p>Please find you <?=$detail['invoice_status'] == 'paid' ? 'Receipt' : 'Invoice'?> attachment with this mail.</p>
</div>
