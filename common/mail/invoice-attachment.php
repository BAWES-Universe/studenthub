<?php
$content = (count($invoices)>1) ? 'invoices' : 'invoice';
?>
<div class="password-reset">
    <p>
        <?=Yii::t('app','Hello,'); ?><br/><br/>
        <?=Yii::t('app','Please find the attached '.$content.' for your payment in order to proceed with the transfers.'); ?><br/><br/>
        <?=Yii::t('app','Sincerely yours,'); ?><br />
        <?=Yii::t('app','Khalid Al-Mutawa'); ?>
    </p>
</div>
