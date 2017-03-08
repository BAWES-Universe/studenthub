<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user common\models\User */

?>
<div class="password-reset">
    <p>Hello <?= Html::encode($name) ?>,</p>

    <p>Use below token to reset password:</p>

    <p><?= $token ?></p>
</div>
