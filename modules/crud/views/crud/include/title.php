<?php
use yii\bootstrap\Html;
?>

<h1 class="main-title">
    <?= preg_replace('/&quot;(.*?)&quot;/', '<span>$1</span>', Html::encode($this->title)) ?>

    <?php
    if (($buttons = $builder->extraControlsByPlace('title'))) {
        ?>
        <div class="pull-right"><?= $buttons ?></div>
        <?php
    }
    ?>
</h1>

<?php
    foreach (Yii::$app->session->getAllFlashes() as $key => $message) {
        ?>
            <div class="alert alert-<?= $key ?>">
                <?= Html::icon('remove-sign') ?>
                <?= nl2br($message) ?>
            </div>
        <?php
    }
?>