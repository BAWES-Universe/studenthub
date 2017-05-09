
<h1>Receipt</h1>

<hr />

For 

<br />

<h3><?= $transfer['company']['company_name'] ?></h3>

<br />

<table class="table table-bordered">
	<thead>
		<tr>
			<th>Candidate</th>
			<th>Hours</th>
			<th>Bonus</th>
			<th>Total</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($transfer['candidates'] as $key => $value) { ?>
		<tr>	
			<td><?= $value['candidate_name'] ?></td>
			<td><?= $value['hours'] ?></td>
			<td><?= $value['bonus'] ?></td>
			<td><?= ($value['hours'] *  $value['company_hourly_rate']) + $value['bonus'] ?></td>
		</tr>
		<?php } ?>
	</tbody>
</table>

<h4>Total Received : <?= $transfer['company_total'] ?></h4>