<?php

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
