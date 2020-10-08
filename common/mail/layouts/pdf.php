<?php

//use admin\assets\AppAsset;
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use yii\bootstrap\Alert;
use yii\helpers\Url;

//AppAsset::register($this);

$this->beginPage();
?>
<!DOCTYPE html>
<html>
<head>
    <?php $this->head(); ?>
</head>
<body style="background: #fff;">
<?php $this->beginBody() ?>

<?= $content ?>
</body>
</html>
<?php $this->endPage() ?>
