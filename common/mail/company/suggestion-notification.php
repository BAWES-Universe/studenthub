<?php
/* @var $this yii\web\View */
/* @var $contact company\models\Contact */

?>
Dear <?=$model->company->company_name?>,<br/><br/>

I'd like to suggest the attached profile(s) for your request of <?=$model->request_number_of_employees?> <?=($model->request_position_type == 1) ? 'Full-time': 'Part-time'?> <?=$model->request_position_title?>.<br/><br/>

Best Regards,<br/>
<?=$staff->staff_name?> - StudentHub
