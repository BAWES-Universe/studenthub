
<h1>Invoice</h1>

<hr />

For 

<br />

<h3><?= Yii::$app->user->identity->company_name ?></h3>

<br />

<table class="table table-bordered">
	<thead>
		<tr>
			<th>Candidate</th>
			<th>Hours</th>
			<th>Bonus</th>
			<th>Transfer Cost</th>
			<th>Total</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($invoice['candidates'] as $key => $value) { ?>
		<tr>	
			<td><?= $value['candidate_name'] ?></td>
			<td><?= $value['hours'] ?></td>
			<td><?= $value['bonus'] ?></td>
			<td><?= $value['transfer_cost'] ?></td>
			<td><?= ($value['hours'] *  Yii::$app->params['candidate_max_hourly_rate']) + $value['bonus'] + $value['transfer_cost'] ?></td>
		</tr>
		<?php } ?>
	</tbody>
</table>

<h4>Invoice Total : <?= $invoice['company_total'] ?></h4>