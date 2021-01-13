<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user common\models\User */

?>
<div class="password-reset">
    
    <p>Hello Admin,</p>

    <p>Please find list of Companies which didn't created any transfer from last 35 days..</p>

    <table class="table table-bordered" style="width: 100%;  max-width: 500px;">
    	<thead>
    		<tr>
    			<td align="left" style="border-left: 1px solid #ddd; border-top: 1px solid #ddd; border-right: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD;"><b>ID</b></td>
    			<td align="left" style="border-top: 1px solid #ddd; border-right: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD;"><b>Name</b></td>
    			<td align="left" style="border-top: 1px solid #ddd; border-right: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD;"><b>Email</b></td>
    		</tr>
    	</thead>
    	<tbody>
    		<?php foreach ($companies as $key => $company) { ?>
    		<tr>
    			<td align="left" style="border-left: 1px solid #ddd; border-right: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD;"><?= $company->company_id ?></td>
    			<td align="left" style="border-right: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD;"><?= $company->company_name ?></td>
    			<td align="left" style="border-right: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD;"><?= $company->company_email ?></td>
    		</tr>
    		<?php } ?>
    	</tbody>
    </table>

</div>
