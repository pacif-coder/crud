<?php
use yii\bootstrap\Html;
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