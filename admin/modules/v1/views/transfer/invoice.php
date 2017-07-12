<?php

use yii\helpers\Url;

$totalHours = 0;
$totalBonus = 0;
$totalAmount = 0;
foreach ($invoice->transfer->transferCandidates as $key => $value) {
    $totalHours += $value->hours;
    $totalBonus += $value->bonus;
    $totalAmount += ($value->hours * $value->company_hourly_rate);
}
?>
<div class="row">
    <div class="col-sm-12" style="margin-top:30px; text-align:center;">
        <?=\yii\helpers\Html::img('images/bawes.jpg',['style'=>'width:100px; margin-bottom:0;'])?>
        <div style="text-align: center"> <span style="margin-top:10px;font-size:25px; color:#252525;">Invoice</span></div>
        <hr>
    </div>
</div>
<div class="row" style="padding: 1.85714286em;">
    <table class="table" cellpadding="3" cellspacing="3">
        <tr>
            <td style="width: 56%;">
                <table cellpadding="2">
                    <tr><td><h3 style="font-weight: 100;">Bill to<br></h3></td></tr>
                    <tr><td><p><?= $invoice->transfer->company->company_name ?></p></td></tr>
                    <tr><td><p><?= (isset($invoice->transfer->company->parentCompany->company_email)) ? $invoice->transfer->company->parentCompany->company_email :  $invoice->transfer->company->company_email ?></p></td></tr>
                </table>
            </td>
            <td>
                <table cellpadding="2" class="table">
                    <tr><td><h3 style="font-weight: 100;">Details<br></h3></td></tr>
                    <tr><td>Invoice number: <?=$invoice->invoice_id?></td></tr>
                    <tr><td><p>Transfer number: <?=$invoice->transfer_id?></p></td></tr>
                    <tr><td><p>Issue date: <?=date('F d,Y',strtotime($invoice->invoice_date))?></p></td></tr>
                    <tr><td><p>Payment terms: Due immediately</p></td></tr>
                    <tr><td><h5 style="margin-bottom:0; font-weight:bold; border-bottom:1px solid blue; padding: 1.85714286em;">Amount due in KWD: <?= $invoice->transfer->company_total ?></h5></td></tr>
                </table>
            </td>
        </tr>
    </table>
</div>
<div class="row" style="border:1px solid #ececec;border-radius:6px; position: relative;overflow: hidden;padding: 1.85714286em;margin-bottom: 30px;">
    <table class="table" cellpadding="3" cellspacing="3">
        <tr>
            <td align="left" style="text-align: left">
                <span><b><?=number_format($totalHours)?> hours</b> worked x <b>2 KD</b> per hour</span>
            </td>
            <td align="right" style="text-align: right">
                <span>KWD <?=number_format($totalAmount)?></span>
            </td>
        </tr>
        <?php if($totalBonus > 0) { ?>
        <tr>
            <td align="left" style="text-align: left">
                <span><b>Bonus to be sent to interns</b></span>
            </td>
            <td align="right" style="text-align: right">
                <span>KWD <?=number_format($totalBonus)?></span>
            </td>
        </tr>
        <?php } ?>
    </table>
    <hr/>
    <table class="table" >
        <tr>
            <td align="left" style="text-align: left">
                <span class="h5" style="font-size: 1em;line-height: 1.85714286em;">Amount Due for <?=count($invoice->transfer->transferCandidates)?> interns</span>
            </td>
            <td align="right" style="text-align: right">
                <span class="h5">KWD <?= $invoice->transfer->company_total ?></span>
            </td>
        </tr>
    </table>
</div>
<!-- end of summary -->
<div class="col-xs-12" style="">
    <p style="margin-bottom:0;">Thank you for your business. It’s a pleasure working with you.</p>
    <p style="margin-bottom:0;">Sincerely yours,</p>
    <p style="margin-bottom:0;">
        Khalid Al-Mutawa<br/>
        <?=\yii\helpers\Html::img('images/signature.jpg',['style'=>'width:150px;'])?>
    </p>
</div>
<br/>
<br/>
<div class="col-xs-12" style="">
    <h3 style="font-weight: 100;">Bank Info</h3>
    BAWES FOR COMPUTER PROGRAMMING AND WEBSITE DESIGN AND DEVELOPMENT COMPANY<br/>
    شركة باوس لبرمجة وتشغيل الكمبيوتر وتصميم وادارة مواقع الانترنت
    <br/>National Bank of Kuwait
    <br/>IBAN: KW07NBOK0000000000002009288593
    <br/>Account #: 2009288593
    <br/>Swift: NBOKKWKW
</div>
