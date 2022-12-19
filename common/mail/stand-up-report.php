<?php

/* @var $absents common\models\StaffLeave[] */
/* @var $attended common\models\StaffWorkSession[] */
/* @var $didnt_attended common\models\Staff[] */

?>

<h3>Absents (<?= sizeof($absents) ?>)</h3>

<?php foreach($absents as $absent) { ?>
    <li><?php echo $absent->staff->staff_name ?></li>
<?php } ?>

<br />

<h3>Didn't Attended (<?= sizeof($didnt_attended) ?>)</h3>

<?php foreach($didnt_attended as $didnt_attende) { ?>
    <li><?php echo $didnt_attende->staff_name ?></li>
<?php } ?>

<br />

<h3>Attended (<?= sizeof($attended) ?>)</h3>

<?php foreach($attended as $attende) { ?>
    <h4><?php echo $attende->staff->staff_name ?></h4>

    <?php

    $answers = $attende->staff->getDailyStandupAnswers()->filterToday()->all();

    foreach ($answers as $answer) { ?>

        <h5><?= $answer->question ?></h5>
        <p><?= $answer->answer ?></p>

    <?php } ?>

    <hr />

<?php } ?>
