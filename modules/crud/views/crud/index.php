<?php
use Yii;
use yii\bootstrap\Html;
use app\modules\crud\grid\GridView;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $grid app\modules\crud\grid\GridView */
/* @var $builder app\modules\crud\builder\GridBuilder */
?>
<div>
    <?php
        echo $this->render('include/title', compact(['builder']));

        $grid = GridView::begin($builder->getOptions());
        GridView::end();
    ?>
</div>