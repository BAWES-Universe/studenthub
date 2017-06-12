<?php
$content = (count($invoices)>1) ? 'receipts' : 'receipt';
?>
<div class="password-reset">
    <p><?=Yii::t('app','Hello,'); ?>
    <br /><br/>
    <?=Yii::t('app','Please find your '.$content.' attached with this mail.'); ?><br/>
    <?=Yii::t('app','Thank you for your business. It’s a pleasure working with you.'); ?>
	<br /><br/>
    <?=Yii::t('app','Sincerely yours,'); ?><br />
    <?=Yii::t('app','Khalid Al-Mutawa'); ?></p>
</div>
