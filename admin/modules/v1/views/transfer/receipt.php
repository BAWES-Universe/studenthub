<?php

use yii\helpers\Url;

$result = [];

foreach ($invoice->transfer->transferCandidates as $key => $value) {  
    
    if(empty($result[$value->company_hourly_rate]))
    {
        $result[$value->company_hourly_rate] = [
            'totalHours' => 0,
            'totalBonus' => 0,
            'totalAmount' => 0
        ];        
    }
    
    $result[$value->company_hourly_rate]['totalHours'] += $value->hours;
    $result[$value->company_hourly_rate]['totalBonus'] += $value->bonus;
    $result[$value->company_hourly_rate]['totalAmount'] += ($value->hours * $value->company_hourly_rate);
}
?>
    <div class="row">
        <div class="col-sm-12" style="margin-top:30px; text-align:center;">
            <?=\yii\helpers\Html::img('images/bawes.jpg',['style'=>'width:100px; margin-bottom:0;'])?>
            <h1>Receipt</h1>
            <hr>
        </div>
    </div>
    <div class="row" style="padding: 1.85714286em;">
        <table class="table" cellpadding="3" cellspacing="3">
            <tr>
                <td style="width: 56%;">
                    <table cellpadding="2">
                        <tr><td><h3>Bill to<br></h3></td></tr>
                        <tr><td><p><?= $invoice->transfer->company->company_name ?></p></td></tr>
                        <tr><td><p><?= (isset($invoice->transfer->company->parentCompany->company_email)) ? $invoice->transfer->company->parentCompany->company_email :  $invoice->transfer->company->company_email ?></p></td></tr>
                    </table>
                </td>
                <td>
                    <table cellpadding="2" class="table">
                        <tr><td><h3>Details<br></h3></td></tr>
                        <tr><td>Invoice number: <?=$invoice->invoice_id?></td></tr>
                        <tr><td><p>Transfer number: <?= $invoice->transfer->parent_transfer_id? $invoice->transfer->parent_transfer_id : $invoice->transfer->transfer_id ?></p></td></tr>
                        <tr><td><p>Payment date: <?=date('F d,Y',strtotime($invoice->invoice_date))?></p></td></tr>
                        <tr><td><h5 style="margin-bottom:0; font-weight:bold; border-bottom:1px solid blue; padding: 1.85714286em;">Amount paid in KWD: <?= $invoice->transfer->company_total ?></h5></td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
    <div class="row" style="border:1px solid #ececec;border-radius:6px; position: relative;overflow: hidden;padding: 1.85714286em;margin-bottom: 30px;">
        <table class="table" cellpadding="3" cellspacing="3">
            <?php foreach ($result as $hourly_rate => $row) { ?>
                <tr>
                    <td align="left" style="text-align: left">
                        <span><b><?= $row['totalHours'] ?> hours</b> worked x <b><?= $hourly_rate ?> KD</b> per hour</span>
                    </td>
                    <td align="right" style="text-align: right">
                        <span>KWD <?= number_format($row['totalAmount'], 3) ?></span>
                    </td>
                </tr>            
                <?php if($row['totalBonus'] > 0) { ?>
                <tr>
                    <td align="left" style="text-align: left">
                        <span><b>Bonus payment</b></span>
                    </td>
                    <td align="right" style="text-align: right">
                        <span>KWD <?= number_format($row['totalBonus'], 3) ?></span>
                    </td>
                </tr>
                <?php } ?>
            <?php } ?>
            <?php if ($invoice->transfer->start_date && $invoice->transfer->end_date) { ?>
                <tr>
                    <td align="left" style="text-align: left">
                        For time period: <?=date('F j, Y',strtotime($invoice->transfer->start_date));?> to <?=date('F j, Y',strtotime($invoice->transfer->end_date));?>
                    </td>
                </tr>
            <?php } ?>
        </table>
        <hr/>
        <table class="table" >
            <tr>
                <td align="left" style="text-align: left">
                    <span class="h5" style="font-size: 1em;line-height: 1.85714286em;">
                        Amount paid for <?= $invoice->transfer->getTransferCandidates()->willGetPaid()->count() ?> interns
                    </span>
                </td>
                <td align="right" style="text-align: right">
                    <span class="h5">
                        KWD <?= number_format($invoice->transfer->company_total, 3); ?>
                    </span>
                </td>
            </tr>
        </table>
    </div>
    <!-- end of summary -->
    <div class="col-xs-12" style="">
        <p style="margin-bottom:0;">No payment is required. This is a confirmation that we have received the requested amount.</p>

        <p style="margin-bottom:0;">Sincerely yours,</p>
        <p style="margin-bottom:0;">
            Khalid Al-Mutawa<br/>
            <?=\yii\helpers\Html::img('images/signature.jpg', ['style'=>'width:150px;'])?>
        </p>
    </div>
