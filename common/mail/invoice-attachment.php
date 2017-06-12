<div class="password-reset">
    <p>
        <?=Yii::t('app','Hello,'); ?><br/><br/>
        <?=Yii::t('app','Please find the attached {numInvoices, plural, =1{invoice} other{invoices}} for your payment in order to proceed with the transfers.', ['numInvoices' => count($invoices)]); ?>

        <?=Yii::t('app','Sincerely yours,'); ?><br />
        <?=Yii::t('app','Khalid Al-Mutawa'); ?>
    </p>
</div>
