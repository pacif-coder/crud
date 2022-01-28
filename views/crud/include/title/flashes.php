<?php
use yii\bootstrap\Html;

foreach (Yii::$app->session->getAllFlashes() as $type => $message) {
    $icon = '';
    switch ($type) {
        case 'success':
            $icon = 'ok';
            break;

        case 'warning':
            $icon = 'question-sign';
            break;

        case 'info':
            $icon = 'info-sign';
            break;

        case 'danger':
            $icon = 'remove-sign';
            break;
    }

    if ($icon) {
        $icon = Html::icon($icon);
    }
    ?>
        <div class="alert alert-<?=$type?>">
            <?=$icon?>
            <?=nl2br(is_array($message)? implode("\n", $message) : $message)?>
        </div>
    <?php
}