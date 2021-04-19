<?php
use Yii;
use yii\bootstrap\Html;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $grid app\modules\crud\grid\GridView */
/* @var $builder app\modules\crud\builder\GridBuilder */
?>
<div>
    <?php
        echo $this->render('include/title', compact(['builder']));

        $grid = $builder->gridClass::begin($builder->getOptions());
        $builder->gridClass::end();
    ?>
</div>