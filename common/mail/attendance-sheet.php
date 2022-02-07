<?php

$company_name = $company->company_common_name_en ? $company->company_common_name_en:
    $company->company_name;
    $lastMonth = date(' F ', strtotime('last month'));
    $year = date(' Y ', strtotime('last month'));
?>
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
                    style="border:1px solid #d8e2e7;direction:ltr;font-size:0px;padding:15px;text-align:center;vertical-align:top;"
                >
                    <!--[if mso | IE]>
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">

                        <tr>
                            <td
                                class="" width="700px"
                            >

                                <table
                                    align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:668px;" width="668"
                                >
                                    <tr>
                                        <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
                    <![endif]-->


                    <div  style="Margin:0px auto;max-width:668px;">

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
                                                class="" style="vertical-align:top;width:668px;"
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

                                                                <img
                                                                    height="auto" src="<?= $logo ?>" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;" width="190"
                                                                />

                                                            </td>
                                                        </tr>
                                                        </tbody>
                                                    </table>

                                                </td>
                                            </tr>

                                            <tr>
                                                <td
                                                    align="left" style="font-size:0px;padding:10px 25px;padding-top:30px;padding-bottom:15px;word-break:break-word;"
                                                >

                                                    <div
                                                        style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:16px;line-height:24px;text-align:left;color:#000000;"
                                                    >
                                                        <?= Yii::t('app','Dear {company},', ['company' => $company_name]); ?>
                                                    </div>

                                                </td>
                                            </tr>

                                            <tr>
                                                <td
                                                    align="left" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:15px;word-break:break-word;"
                                                >

                                                    <div
                                                        style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:16px;line-height:24px;text-align:left;color:#000000;"
                                                    >
                                                        <?= Yii::t('app','We would appreciate it if you shared with us the attendance for the employees in {month} {year}.', ['month' => $lastMonth, 'year' => $year]); ?>

                                                    </div>

                                                </td>
                                            </tr>

                                            <tr>
                                                <td
                                                    align="left" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:0px;word-break:break-word;"
                                                >

                                                    <div
                                                        style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:16px;line-height:24px;text-align:left;color:#000000;"
                                                    >
                                                        <?= Yii::t('app','Sincerely yours,'); ?>

                                                    </div>

                                                </td>
                                            </tr>

                                            <tr>
                                                <td
                                                    align="left" style="font-size:0px;padding:10px 25px;padding-top:0px;padding-bottom:15px;word-break:break-word;"
                                                >

                                                    <div
                                                        style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:16px;line-height:24px;text-align:left;color:#000000;"
                                                    >
                                                        <?= Yii::t('app','StudentHub team'); ?>

                                                    </div>

                                                </td>
                                            </tr>

                                  

                                            <!-- <tr>
                                                <td
                                                    align="left" style="font-size:0px;padding:10px 25px;padding-top:0;padding-bottom:0;word-break:break-word;"
                                                >

                                                    <div
                                                        style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:14px;line-height:24px;text-align:left;color:#666666;"
                                                    >
                                                        <?php
                                                        // Yii::t('app', "<b>Issues with the {numInvoices, plural, =1{invoice} other{invoices}}?</b> Please <a href='https://www.studenthub.co/contact'>contact us</a>, we'll be happy to assist", ['numInvoices' => count($invoices)]);
                                                         ?>

                                                    </div>

                                                </td>
                                            </tr> -->

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
