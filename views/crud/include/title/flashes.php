<?php
use yii\bootstrap\Html;

foreach (Yii::$app->session->getAllFlashes() as $type => $message) {
    switch ($type) {
        case 'success':
            $icon = 'ok';
            break;

        default:
            $icon = 'remove-sign';
    }
    ?>
        <div class="alert alert-<?=$type?>">
            <?=Html::icon($icon)?>
            <?=nl2br(is_array($message)? implode("\n", $message) : $message)?>
        </div>
    <?php
}