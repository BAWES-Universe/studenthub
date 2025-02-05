
<?php
/* @var $this yii\web\View */
/* @var $candidate common\models\Candidate */

$email = ($candidate->candidate_new_email) ? $candidate->candidate_new_email : $candidate->candidate_email;

$webUrl = Yii::$app->params['candidateAppUrl'] . 'verify-email/' . urlencode($email) . '/' . $candidate->candidate_auth_key;

$mobileUrl = 'studenthub-student://verify-email/' . urlencode($email) . '/' . $candidate->candidate_auth_key;

$logo = yii\helpers\Url::to('@web/images/logo.png', 'https');
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
    <head>
        <title>
            Please confirm your email address
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
                                                                                        Thanks for signing up on StudentHub. Please take a sec to confirm your email.<br /><br /> 

                                                                                        Your verification code : <?= $candidate->candidate_auth_key ?>
                                                                                    </div>

                                                                                </td>
                                                                            </tr>

                                                                            <tr>
                                                                                <td
                                                                                    align="left" vertical-align="middle" style="font-size:0px;padding:10px 25px;padding-top:8px;padding-right:30px;padding-bottom:30px;padding-left:30px;word-break:break-word;"
                                                                                    >

                                                                                    <table
                                                                                        border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:separate;line-height:100%;"
                                                                                        >
                                                                                        <tr>
                                                                                            <td
                                                                                                align="center" bgcolor="#4354A4" role="presentation" style="border:none;border-radius:5px;cursor:auto;padding:10px 25px;background:#4354A4;" valign="middle"
                                                                                                >
                                                                                                <a
                                                                                                    href="<?= $webUrl ?>" style="background:#4354A4;color:white;font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:16px;font-weight:bold;line-height:120%;Margin:0;text-decoration:none;text-transform:none;" target="_blank"
                                                                                                    >
                                                                                                    Confirm my account
                                                                                                </a>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </table>

                                                                                </td>
                                                                            </tr>

                                                                            <tr>
                                                                                <td
                                                                                    style="font-size:0px;padding:10px 25px;word-break:break-word;"
                                                                                    >

                                                                                    <p
                                                                                        style="border-top:solid 1px #d8e2e7;font-size:1;margin:0px auto;width:100%;"
                                                                                        >
                                                                                    </p>

                                                                                    <!--[if mso | IE]>
                                                                                      <table
                                                                                         align="center" border="0" cellpadding="0" cellspacing="0" style="border-top:solid 1px #d8e2e7;font-size:1;margin:0px auto;width:618px;" role="presentation" width="618px"
                                                                                      >
                                                                                        <tr>
                                                                                          <td style="height:0;line-height:0;">
                                                                                            &nbsp;
                                                                                          </td>
                                                                                        </tr>
                                                                                      </table>
                                                                                    <![endif]-->


                                                                                </td>
                                                                            </tr>

                                                                            <tr>
                                                                                <td
                                                                                    align="left" style="font-size:0px;padding:10px 25px;padding-top:0;padding-bottom:0;word-break:break-word;"
                                                                                    >

                                                                                    <div
                                                                                        style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:14px;line-height:24px;text-align:left;color:#666666;"
                                                                                        >
                                                                                        <b>Button not working?</b> Copy and paste this link into your browser:
                                                                                    </div>

                                                                                </td>
                                                                            </tr>

                                                                            <tr>
                                                                                <td
                                                                                    align="left" style="font-size:0px;padding:10px 25px;padding-top:0;padding-bottom:20px;word-break:break-word;"
                                                                                    >

                                                                                    <div
                                                                                        style="font-family:Proxima Nova, Arial, Arial, Helvetica, sans-serif;font-size:14px;line-height:24px;text-align:left;color:#666666;"
                                                                                        >
                                                                                            <?= $webUrl ?>
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
