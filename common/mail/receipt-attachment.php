<div class="password-reset">
    <p><?=Yii::t('app','Hello,'); ?>
    <br /><br/>
    <?=Yii::t('app','Please find your {numReceipts, plural, =1{receipt} other{receipts}} attached with this mail.', ['numReceipts' => count($invoices)]); ?><br/>
    <?=Yii::t('app','Thank you for your business. It’s a pleasure working with you.'); ?>
	<br /><br/>
    <?=Yii::t('app','Sincerely yours,'); ?><br />
    <?=Yii::t('app','Khalid Al-Mutawa'); ?></p>
</div>
