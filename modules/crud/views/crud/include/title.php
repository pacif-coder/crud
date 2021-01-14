<?php
use yii\bootstrap\Html;
?>
<div class="main-title">
    <?php
    if (($buttons = $builder->extraControlsByPlace('title'))) {
        echo Html::tag('div', $buttons, ['class' => 'pull-right']);
    }
    ?>

    <h1>
        <?php
        if (false !== strpos($this->title, '«') or false !== strpos($this->title, '»')) {
            $template = '<span>$1</span>';
        } else {
            $template = '<span class="with-quota">$1</span>';
        }

        echo preg_replace('/&quot;(.*?)&quot;/', $template, Html::encode($this->title));
        ?>
    </h1>
</div>

<?php
foreach (Yii::$app->session->getAllFlashes() as $key => $message) {
    ?>
        <div class="alert alert-<?=$key?>">
            <?=Html::icon('remove-sign')?>
            <?=nl2br($message)?>
        </div>
    <?php
}
?>