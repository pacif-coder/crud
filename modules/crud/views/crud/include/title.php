<?php
use yii\helpers\Html;
?>

<h1 class="main-title">
    <?= preg_replace('/&quot;(.*?)&quot;/', '<span>$1</span>', Html::encode($this->title)) ?>
</h1>