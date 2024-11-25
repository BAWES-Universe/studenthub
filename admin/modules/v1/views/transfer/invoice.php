<?php

use yii\helpers\Url;

$result = [];

$main_transfer = $invoice->transfer->parent_transfer_id? $invoice->transfer->parentTransfer : $invoice->transfer;

if (!$main_transfer->contract_type || $main_transfer->contract_type == 'HOURLY') {
    foreach ($invoice->transfer->transferCandidates as $key => $value) {

        if (empty($result[$value->company_hourly_rate])) {
            $result[$value->company_hourly_rate] = [
                'totalHours' => 0,
                'totalMinutes' => 0,
                'totalSeconds' => 0,
                'totalBonus' => 0,
                'totalAmount' => 0,
                "totalTransferCost" => 0
            ];
        }

        $result[$value->company_hourly_rate]['totalHours'] += $value->hours;
        $result[$value->company_hourly_rate]['totalMinutes'] += $value->minutes;
        $result[$value->company_hourly_rate]['totalSeconds'] += $value->seconds;

        // round up
        if ($result[$value->company_hourly_rate]['totalSeconds'] > 59) {
            $result[$value->company_hourly_rate]['totalSeconds'] = $result[$value->company_hourly_rate]['totalSeconds'] % 60;
            $result[$value->company_hourly_rate]['totalMinutes'] += floor($result[$value->company_hourly_rate]['totalSeconds'] / 60);
        }

        if ($result[$value->company_hourly_rate]['totalMinutes'] > 59) {
            $result[$value->company_hourly_rate]['totalMinutes'] = $result[$value->company_hourly_rate]['totalMinutes'] % 60;
            $result[$value->company_hourly_rate]['totalHours'] += floor($result[$value->company_hourly_rate]['totalMinutes'] / 60);
        }

        $result[$value->company_hourly_rate]['totalBonus'] += $value->bonus;
        $result[$value->company_hourly_rate]['totalTransferCost'] += $value->transfer_cost;
        $result[$value->company_hourly_rate]['totalAmount'] += $value->company_total - $value->bonus;

        //($value->hours * $value->company_hourly_rate) + $value->transfer_cost;
    }
} else if (isset($invoice->transfer->transferCandidates[0])) {
    $result[] = [
         "totalBonus" => 0, //not having bonus on contract
        'totalAmount' => $invoice->transfer->company_total,
        "totalTransferCost" => $invoice->transfer->transfer_cost,
        "perCandidate" => $invoice->transfer->transferCandidates[0]['company_total']
    ];
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
                    <tr><td>Invoice number: <?= $invoice->invoice_id ?></td></tr>
                    <tr><td><p>Transfer number: <?= $invoice->transfer->parent_transfer_id? $invoice->transfer->parent_transfer_id : $invoice->transfer->transfer_id ?></p></td></tr>
                    <tr><td><p>Issue date: <?=date('F d,Y',strtotime($invoice->invoice_date))?></p></td></tr>
                    <tr><td><p>Payment terms: Due immediately</p></td></tr>
                    <tr><td><h5 style="margin-bottom:0; font-weight:bold; border-bottom:1px solid blue; padding: 1.85714286em;">Amount due in <?= $invoice->transfer->currency_code ?>: <?= number_format($invoice->transfer->company_total, 3) ?></h5></td></tr>
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
                    <?php if (!$main_transfer->contract_type || $main_transfer->contract_type == 'HOURLY') { ?>
                    <span>
                        <b>
                            <?= $row['totalHours'] > 0 ?$row['totalHours'] . " hours": "" ?>
                            <?= $row['totalMinutes'] > 0 ? $row['totalMinutes'] . " minutes": "" ?>
                            <?= $row['totalSeconds'] > 0 ? $row['totalSeconds'] . " seconds": "" ?>
                        </b> worked x <b><?= $hourly_rate ?> KD</b> per hour
                        <?php if ($row['totalTransferCost'] > 0) { ?>
                            + <?= $row['totalTransferCost'] ?> KD transfer cost
                        <?php } ?>
                    </span>
                    <?php } else { ?>
                    <span>
                        <b>Per candidate</b>
                    </span>
                    <?php } ?>
                </td>
                <td align="right" style="text-align: right">
                    <?php if (!$main_transfer->contract_type || $main_transfer->contract_type == 'HOURLY') { ?>
                        <span><?= $invoice->transfer->currency_code ?> <?= number_format($row['totalAmount'], 3)?></span>
                    <?php } else { ?>
                        <span><?= $invoice->transfer->currency_code ?> <?= number_format($row['perCandidate'], 3)?></span>
                    <?php } ?>
                </td>
            </tr>
            <?php if($row['totalBonus'] > 0) { ?>
            <tr>
                <td align="left" style="text-align: left">
                    <span><b>Bonus payment</b></span>
                </td>
                <td align="right" style="text-align: right">
                    <span><?= $invoice->transfer->currency_code ?> <?= number_format($row['totalBonus'], 3)?></span>
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
                    Amount Due for <?= $invoice->transfer->getTransferCandidates()->willGetPaid()->count() ?> interns
                </span>
            </td>
            <td align="right" style="text-align: right">
                <span class="h5"><?= $invoice->transfer->currency_code ?> <?= number_format($invoice->transfer->company_total, 3) ?></span>
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
    <?= Yii::$app->params['bankInfo']['accountName'] ?><br/>
    <?= Yii::$app->params['bankInfo']['accountNameArabic'] ?>
    <br/><?= Yii::$app->params['bankInfo']['bankName'] ?>
    <br/>IBAN: <?= Yii::$app->params['bankInfo']['iban'] ?>
    <br/>Account #: <?= Yii::$app->params['bankInfo']['accountNumber'] ?>
    <br/>Swift: <?= Yii::$app->params['bankInfo']['swiftCode'] ?>
</div>
