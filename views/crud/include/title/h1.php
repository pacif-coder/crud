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

    $this->title = str_replace("'", '"', $this->title);
    echo preg_replace('/&quot;+(.*?)&quot;/s', $template, Html::encode($this->title));
    ?>
</h1>