<?php
$path = (YII_ENV == 'prod') ?  "/" : "dev/";
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <title>
        <?=$title?>
    </title>
    <!--[if !mso]><!-- -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!--<![endif]-->
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style type="text/css">
        #outlook a { padding:0; }
        .ReadMsgBody { width:100%; }
        .ExternalClass { width:100%; }
        .ExternalClass * { line-height:100%; }
        body { margin:0;padding:0;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%; }
        table, td { border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt; }
        img { border:0;height:auto;line-height:100%; outline:none;text-decoration:none;-ms-interpolation-mode:bicubic; }
        p { display:block;margin:13px 0; }
    </style>
    <!--[if !mso]><!-->
    <style type="text/css">
        @media only screen and (max-width:480px) {
            @-ms-viewport { width:320px; }
            @viewport { width:320px; }
        }
    </style>
    <!--<![endif]-->
    <!--[if mso]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->
    <!--[if lte mso 11]>
    <style type="text/css">
        .outlook-group-fix { width:100% !important; }
    </style>
    <![endif]-->


    <style type="text/css">
        @media only screen and (min-width:480px) {
            .mj-column-per-100 { width:100% !important; max-width: 100%; }
            .mj-column-per-33 { width:33.333333333333336% !important; max-width: 33.333333333333336%; }
            .mj-column-per-50 { width:50% !important; max-width: 50%; }
        }
    </style>


    <style type="text/css">



        @media only screen and (max-width:480px) {
            table.full-width-mobile { width: 100% !important; }
            td.full-width-mobile { width: auto !important; }
        }

    </style>


</head>
<body style="background-color:#ffffff;">


<div
        style="background-color:#ffffff;"
>


    <!--[if mso | IE]>
    <table
            align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:700px;" width="700"
    >
        <tr>
            <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
    <![endif]-->


    <div  style="Margin:0px auto;max-width:700px;">

        <table
                align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;"
        >
            <tbody>
            <tr>
                <td
                        style="direction:ltr;font-size:0px;padding:15px;text-align:center;vertical-align:top;"
                >
                    <!--[if mso | IE]>
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">

                        <tr>
                            <td
                                    class="" width="700px"
                            >

                                <table
                                        align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:670px;" width="670"
                                >
                                    <tr>
                                        <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
                    <![endif]-->


                    <div  style="Margin:0px auto;max-width:670px;">

                        <table
                                align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;"
                        >
                            <tbody>
                            <tr>
                                <td
                                        style="direction:ltr;font-size:0px;padding:0px;text-align:center;vertical-align:top;"
                                >
                                    <!--[if mso | IE]>
                                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">

                                        <tr>

                                            <td
                                                    class="" style="vertical-align:top;width:670px;"
                                            >
                                    <![endif]-->

                                    <div
                                            class="mj-column-per-100 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;"
                                    >

                                        <table
                                                border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%"
                                        >

                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;"
                                                >

                                                    <table
                                                            border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;"
                                                    >
                                                        <tbody>
                                                        <tr>
                                                            <td  style="width:190px;">

                                                                <img height="auto" src="<?= $logo ?>" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;" width="190">

                                                            </td>
                                                        </tr>
                                                        </tbody>
                                                    </table>

                                                </td>
                                            </tr>

                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:15px;word-break:break-word;"
                                                >

                                                    <div
                                                            style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:16px;font-weight:bold;line-height:24px;text-align:center;color:#000000;"
                                                    >
                                                        <?=$title?>
                                                    </div>

                                                </td>
                                            </tr>
                                            <!--  $model->total_candidate > 0 || $model->is_request_updates_in_30_days > 0 || 
                                                    $model->no_of_active_requests > 0 -->
                                            <?php if ($model->company_status == \common\models\Company::STATUS_ACTIVE) { ?>
                                                <tr>
                                                    <td align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:15px;word-break:break-word;">
                                                        <div style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:16px;font-weight:bold;line-height:24px;text-align:center;color:green;">
                                                            Active Account
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php } else  { ?>
                                                <tr>
                                                    <td align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:15px;word-break:break-word;">
                                                        <div style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:16px;font-weight:bold;line-height:24px;text-align:center;color:red;">
                                                            Inactive Account
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </table>
                                    </div>
                                    <!--[if mso | IE]>
                                    </td>

                                    </tr>

                                    </table>
                                    <![endif]-->
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>


                    <!--[if mso | IE]>
                    </td>
                    </tr>
                    </table>

                    </td>
                    </tr>
                    <![endif]-->
                    <!-- Changes -->
                    <!--[if mso | IE]>
                    <tr>
                        <td
                                class="" width="700px"
                        >

                            <table
                                    align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:670px;" width="670"
                            >
                                <tr>
                                    <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
                    <![endif]-->


                    <div  style="background:white;background-color:white;Margin:0px auto;max-width:670px;">

                        <table
                                align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:white;background-color:white;width:100%;"
                        >
                            <tbody>
                            <tr>
                                <td
                                        style="border:1px solid #d8e2e7;direction:ltr;font-size:0px;padding:15px;text-align:center;vertical-align:top;"
                                >
                                    <!--[if mso | IE]>
                                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">

                                        <tr>

                                            <td
                                                    class="" style="vertical-align:middle;width:212.66666666666669px;"
                                            >
                                    <![endif]-->

                                    <div
                                            class="mj-column-per-33 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:middle;width:100%;"
                                    >

                                        <table
                                                border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:middle;" width="100%"
                                        >

                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;"
                                                >

                                                    <table
                                                            border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;"
                                                    >
                                                        <tbody>
                                                        <tr>
                                                            <td  style="width:100px;">

                                                                <img
                                                                        height="auto" src="<?=Yii::$app->params['candidate_photo'].$path.$model->company_logo?>" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;" width="100"
                                                                />

                                                            </td>
                                                        </tr>
                                                        </tbody>
                                                    </table>

                                                </td>
                                            </tr>

                                        </table>

                                    </div>

                                    <!--[if mso | IE]>
                                    </td>

                                    <td
                                            class="" style="vertical-align:middle;width:212.66666666666669px;"
                                    >
                                    <![endif]-->

                                    <div
                                            class="mj-column-per-33 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:middle;width:100%;"
                                    >

                                        <table
                                                border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:middle;" width="100%"
                                        >
                                            <!-- Block -->
                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:0px;word-break:break-word;"
                                                >

                                                    <div
                                                            style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:14px;line-height:24px;text-align:center;color:#828585;"
                                                    >
                                                        Official Name
                                                    </div>

                                                </td>
                                            </tr>

                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:15px;word-break:break-word;"
                                                >

                                                    <div
                                                            style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:16px;line-height:24px;text-align:center;color:#000000;"
                                                    >
                                                        <?=$model->company_name?>
                                                    </div>

                                                </td>
                                            </tr>

                                        </table>

                                    </div>

                                    <!--[if mso | IE]>
                                    </td>

                                    <td
                                            class="" style="vertical-align:middle;width:212.66666666666669px;"
                                    >
                                    <![endif]-->

                                    <div
                                            class="mj-column-per-33 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:middle;width:100%;"
                                    >

                                        <table
                                                border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:middle;" width="100%"
                                        >
                                            <!-- Block -->
                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:0px;word-break:break-word;"
                                                >

                                                    <div
                                                            style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:14px;line-height:24px;text-align:center;color:#828585;"
                                                    >
                                                        Followup
                                                    </div>

                                                </td>
                                            </tr>

                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:15px;word-break:break-word;"
                                                >

                                                    <div
                                                            style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:16px;line-height:24px;text-align:center;color:#000000;"
                                                    >
                                                        <?php
                                                        if ($model->company_followup) {
                                                            if ($model->company_followup_interval_weeks == 1) {
                                                                echo 'Every '.$model->company_followup_interval_weeks.' week';
                                                            } else  {
                                                                echo 'Every '.$model->company_followup_interval_weeks.' weeks';
                                                            }
                                                        } else {
                                                            echo 'No';
                                                        }
                                                        ?>
                                                    </div>

                                                </td>
                                            </tr>

                                        </table>

                                    </div>

                                    <!--[if mso | IE]>
                                    </td>

                                    </tr>

                                    </table>
                                    <![endif]-->
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>


                    <!--[if mso | IE]>
                    </td>
                    </tr>
                    </table>

                    </td>
                    </tr>

                    <tr>
                        <td
                                class="" width="700px"
                        >

                            <table
                                    align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:670px;" width="670"
                            >
                                <tr>
                                    <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
                    <![endif]-->


                    <div  style="Margin:0px auto;max-width:670px;">

                        <table
                                align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;"
                        >
                            <tbody>
                            <tr>
                                <td
                                        style="direction:ltr;font-size:0px;padding:4px;text-align:center;vertical-align:top;"
                                >
                                    <!--[if mso | IE]>
                                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">

                                        <tr>

                                        </tr>

                                    </table>
                                    <![endif]-->
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>


                    <!--[if mso | IE]>
                    </td>
                    </tr>
                    </table>

                    </td>
                    </tr>

                    <tr>
                        <td
                                class="" width="700px"
                        >

                            <table
                                    align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:670px;" width="670"
                            >
                                <tr>
                                    <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
                    <![endif]-->


                    <div  style="background:white;background-color:white;Margin:0px auto;max-width:670px;">

                        <table
                                align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:white;background-color:white;width:100%;"
                        >
                            <tbody>
                            <tr>
                                <td
                                        style="border:1px solid #d8e2e7;direction:ltr;font-size:0px;padding:15px;text-align:center;vertical-align:top;"
                                >
                                    <!--[if mso | IE]>
                                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">

                                        <tr>

                                            <td
                                                    class="" style="vertical-align:middle;width:319px;"
                                            >
                                    <![endif]-->

                                    <div
                                            class="mj-column-per-50 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:middle;width:100%;"
                                    >

                                        <table
                                                border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:middle;" width="100%"
                                        >
                                            <!-- Block --><!-- Block -->
                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:0px;word-break:break-word;"
                                                >

                                                    <div
                                                            style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:14px;line-height:24px;text-align:center;color:#828585;"
                                                    >
                                                        Common Name [English]
                                                    </div>

                                                </td>
                                            </tr>

                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:15px;word-break:break-word;"
                                                >

                                                    <div
                                                            style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:16px;line-height:24px;text-align:center;color:#000000;"
                                                    >
                                                        <?=$model->company_common_name_en?>
                                                    </div>

                                                </td>
                                            </tr>

                                        </table>

                                    </div>

                                    <!--[if mso | IE]>
                                    </td>

                                    <td
                                            class="" style="vertical-align:middle;width:319px;"
                                    >
                                    <![endif]-->

                                    <div
                                            class="mj-column-per-50 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:middle;width:100%;"
                                    >

                                        <table
                                                border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:middle;" width="100%"
                                        >
                                            <!-- Block -->
                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:0px;word-break:break-word;"
                                                >

                                                    <div
                                                            style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:14px;line-height:24px;text-align:center;color:#828585;"
                                                    >
                                                        Common Name [Arabic]
                                                    </div>

                                                </td>
                                            </tr>

                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:15px;word-break:break-word;"
                                                >

                                                    <div
                                                            style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:16px;line-height:24px;text-align:center;color:#000000;"
                                                    >
                                                        <?=$model->company_common_name_ar?>
                                                    </div>

                                                </td>
                                            </tr>

                                        </table>

                                    </div>

                                    <!--[if mso | IE]>
                                    </td>

                                    </tr>

                                    </table>
                                    <![endif]-->
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>


                    <!--[if mso | IE]>
                    </td>
                    </tr>
                    </table>

                    </td>
                    </tr>

                    <tr>
                        <td
                                class="" width="700px"
                        >

                            <table
                                    align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:670px;" width="670"
                            >
                                <tr>
                                    <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
                    <![endif]-->


                    <div  style="Margin:0px auto;max-width:670px;">

                        <table
                                align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;"
                        >
                            <tbody>
                            <tr>
                                <td
                                        style="direction:ltr;font-size:0px;padding:4px;text-align:center;vertical-align:top;"
                                >
                                    <!--[if mso | IE]>
                                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">

                                        <tr>

                                        </tr>

                                    </table>
                                    <![endif]-->
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>


                    <!--[if mso | IE]>
                    </td>
                    </tr>
                    </table>

                    </td>
                    </tr>

                    <tr>
                        <td
                                class="" width="700px"
                        >

                            <table
                                    align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:670px;" width="670"
                            >
                                <tr>
                                    <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
                    <![endif]-->


                    <div  style="background:white;background-color:white;Margin:0px auto;max-width:670px;">

                        <table
                                align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:white;background-color:white;width:100%;"
                        >
                            <tbody>
                            <tr>
                                <td
                                        style="border:1px solid #d8e2e7;direction:ltr;font-size:0px;padding:15px;text-align:center;vertical-align:top;"
                                >
                                    <!--[if mso | IE]>
                                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">

                                        <tr>

                                            <td
                                                    class="" style="vertical-align:middle;width:319px;"
                                            >
                                    <![endif]-->

                                    <div
                                            class="mj-column-per-50 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:middle;width:100%;"
                                    >

                                        <table
                                                border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:middle;" width="100%"
                                        >
                                            <!-- Block --><!-- Block -->
                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:0px;word-break:break-word;"
                                                >

                                                    <div
                                                            style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:14px;line-height:24px;text-align:center;color:#828585;"
                                                    >
                                                        Hourly Rate
                                                    </div>

                                                </td>
                                            </tr>

                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:15px;word-break:break-word;"
                                                >

                                                    <div
                                                            style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:16px;line-height:24px;text-align:center;color:#000000;"
                                                    >
                                                        <?= $model->company_hourly_rate ?> <?= $model->currency_code ?>
                                                    </div>

                                                </td>
                                            </tr>

                                        </table>

                                    </div>

                                    <!--[if mso | IE]>
                                    </td>

                                    <td
                                            class="" style="vertical-align:middle;width:319px;"
                                    >
                                    <![endif]-->

                                    <div
                                            class="mj-column-per-50 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:middle;width:100%;"
                                    >

                                        <table
                                                border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:middle;" width="100%"
                                        >
                                            <!-- Block -->
                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:0px;word-break:break-word;"
                                                >

                                                    <div
                                                            style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:14px;line-height:24px;text-align:center;color:#828585;"
                                                    >
                                                        Bonus Commission
                                                    </div>

                                                </td>
                                            </tr>

                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:15px;word-break:break-word;"
                                                >

                                                    <div
                                                            style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:16px;line-height:24px;text-align:center;color:#000000;"
                                                    >
                                                        <?=$model->company_bonus_commission?>%
                                                    </div>

                                                </td>
                                            </tr>

                                        </table>

                                    </div>

                                    <!--[if mso | IE]>
                                    </td>

                                    </tr>

                                    </table>
                                    <![endif]-->
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>


                    <!--[if mso | IE]>
                    </td>
                    </tr>
                    </table>

                    </td>
                    </tr>

                    <tr>
                        <td
                                class="" width="700px"
                        >

                            <table
                                    align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:670px;" width="670"
                            >
                                <tr>
                                    <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
                    <![endif]-->


                    <div  style="Margin:0px auto;max-width:670px;">

                        <table
                                align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;"
                        >
                            <tbody>
                            <tr>
                                <td
                                        style="direction:ltr;font-size:0px;padding:4px;text-align:center;vertical-align:top;"
                                >
                                    <!--[if mso | IE]>
                                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">

                                        <tr>

                                        </tr>

                                    </table>
                                    <![endif]-->
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>


                    <!--[if mso | IE]>
                    </td>
                    </tr>
                    </table>

                    </td>
                    </tr>

                    <tr>
                        <td
                                class="" width="700px"
                        >

                            <table
                                    align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:670px;" width="670"
                            >
                                <tr>
                                    <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
                    <![endif]-->


                    <div  style="background:white;background-color:white;Margin:0px auto;max-width:670px;">

                        <table
                                align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:white;background-color:white;width:100%;"
                        >
                            <tbody>
                            <tr>
                                <td
                                        style="border:1px solid #d8e2e7;direction:ltr;font-size:0px;padding:15px;text-align:center;vertical-align:top;"
                                >
                                    <!--[if mso | IE]>
                                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">

                                        <tr>

                                            <td
                                                    class="" style="vertical-align:middle;width:319px;"
                                            >
                                    <![endif]-->

                                    <div
                                            class="mj-column-per-50 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:middle;width:100%;"
                                    >

                                        <table
                                                border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:middle;" width="100%"
                                        >
                                            <!-- Block --><!-- Block -->
                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:0px;word-break:break-word;"
                                                >

                                                    <div
                                                            style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:14px;line-height:24px;text-align:center;color:#828585;"
                                                    >
                                                        Email
                                                    </div>

                                                </td>
                                            </tr>

                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:15px;word-break:break-word;"
                                                >

                                                    <div
                                                            style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:16px;line-height:24px;text-align:center;color:#000000;"
                                                    >
                                                        <?=$model->company_email?>
                                                    </div>

                                                </td>
                                            </tr>

                                        </table>

                                    </div>

                                    <!--[if mso | IE]>
                                    </td>

                                    <td
                                            class="" style="vertical-align:middle;width:319px;"
                                    >
                                    <![endif]-->

                                    <div
                                            class="mj-column-per-50 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:middle;width:100%;"
                                    >

                                        <table
                                                border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:middle;" width="100%"
                                        >
                                            <!-- Block -->
                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:0px;word-break:break-word;"
                                                >

                                                    <div
                                                            style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:14px;line-height:24px;text-align:center;color:#828585;"
                                                    >
                                                        Website
                                                    </div>

                                                </td>
                                            </tr>

                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:15px;word-break:break-word;"
                                                >

                                                    <div
                                                            style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:16px;line-height:24px;text-align:center;color:#000000;"
                                                    >
                                                        <?=$model->company_website?>
                                                    </div>

                                                </td>
                                            </tr>

                                        </table>

                                    </div>

                                    <!--[if mso | IE]>
                                    </td>

                                    </tr>

                                    </table>
                                    <![endif]-->
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>


                    <!--[if mso | IE]>
                    </td>
                    </tr>
                    </table>

                    </td>
                    </tr>

                    <tr>
                        <td
                                class="" width="700px"
                        >

                            <table
                                    align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:670px;" width="670"
                            >
                                <tr>
                                    <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
                    <![endif]-->


                    <div  style="Margin:0px auto;max-width:670px;">

                        <table
                                align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;"
                        >
                            <tbody>
                            <tr>
                                <td
                                        style="direction:ltr;font-size:0px;padding:4px;text-align:center;vertical-align:top;"
                                >
                                    <!--[if mso | IE]>
                                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">

                                        <tr>

                                        </tr>

                                    </table>
                                    <![endif]-->
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>


                    <!--[if mso | IE]>
                    </td>
                    </tr>
                    </table>

                    </td>
                    </tr>

                    <tr>
                        <td
                                class="" width="700px"
                        >

                            <table
                                    align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:670px;" width="670"
                            >
                                <tr>
                                    <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
                    <![endif]-->


                    <div  style="background:white;background-color:white;Margin:0px auto;max-width:670px;">

                        <table
                                align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:white;background-color:white;width:100%;"
                        >
                            <tbody>
                            <tr>
                                <td
                                        style="border:1px solid #d8e2e7;direction:ltr;font-size:0px;padding:15px;text-align:center;vertical-align:top;"
                                >
                                    <!--[if mso | IE]>
                                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">

                                        <tr>

                                            <td
                                                    class="" style="vertical-align:middle;width:319px;"
                                            >
                                    <![endif]-->

                                    <div
                                            class="mj-column-per-50 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:middle;width:100%;"
                                    >

                                        <table
                                                border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:middle;" width="100%"
                                        >
                                            <!-- Block --><!-- Block -->
                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:0px;word-break:break-word;"
                                                >

                                                    <div
                                                            style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:14px;line-height:24px;text-align:center;color:#828585;"
                                                    >
                                                        Description [English]
                                                    </div>

                                                </td>
                                            </tr>

                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:15px;word-break:break-word;"
                                                >

                                                    <div
                                                            style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:16px;line-height:24px;text-align:center;color:#000000;"
                                                    >
                                                        <?=$model->company_description_en?>
                                                    </div>

                                                </td>
                                            </tr>

                                        </table>

                                    </div>

                                    <!--[if mso | IE]>
                                    </td>

                                    <td
                                            class="" style="vertical-align:middle;width:319px;"
                                    >
                                    <![endif]-->

                                    <div
                                            class="mj-column-per-50 outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:middle;width:100%;"
                                    >

                                        <table
                                                border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:middle;" width="100%"
                                        >
                                            <!-- Block -->
                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:0px;word-break:break-word;"
                                                >

                                                    <div
                                                            style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:14px;line-height:24px;text-align:center;color:#828585;"
                                                    >
                                                        Description [Arabic]
                                                    </div>

                                                </td>
                                            </tr>

                                            <tr>
                                                <td
                                                        align="center" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:15px;word-break:break-word;"
                                                >

                                                    <div
                                                            style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:16px;line-height:24px;text-align:center;color:#000000;"
                                                    >
                                                        <?=$model->company_description_ar?>
                                                    </div>

                                                </td>
                                            </tr>

                                        </table>

                                    </div>

                                    <!--[if mso | IE]>
                                    </td>

                                    </tr>

                                    </table>
                                    <![endif]-->
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>


                    <!--[if mso | IE]>
                    </td>
                    </tr>
                    </table>

                    </td>
                    </tr>

                    </table>
                    <![endif]-->
                </td>
            </tr>
            </tbody>
        </table>

    </div>


    <!--[if mso | IE]>
    </td>
    </tr>
    </table>

    <table
            align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:700px;" width="700"
    >
        <tr>
            <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
    <![endif]-->


    <div  style="Margin:0px auto;max-width:700px;">

        <table
                align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;"
        >
            <tbody>
            <tr>
                <td
                        style="direction:ltr;font-size:0px;padding:0px;padding-bottom:24px;padding-top:0;text-align:center;vertical-align:top;"
                >
                    <!--[if mso | IE]>
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">

                        <tr>

                        </tr>

                    </table>
                    <![endif]-->
                </td>
            </tr>
            </tbody>
        </table>

    </div>


    <!--[if mso | IE]>
    </td>
    </tr>
    </table>
    <![endif]-->


</div>

</body>
</html>
