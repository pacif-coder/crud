<?php
use yii\bootstrap\Html;

foreach (Yii::$app->session->getAllFlashes() as $key => $message) {
    ?>
        <div class="alert alert-<?=$key?>">
            <?=Html::icon('remove-sign')?>
            <?=nl2br($message)?>
        </div>
    <?php
}