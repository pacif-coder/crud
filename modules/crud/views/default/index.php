<?php
use Yii;
use yii\bootstrap\Html;
use app\modules\crud\grid\GridView;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $grid app\modules\crud\grid\GridView */
?>
<div>
    <h1 class="main-title clearfix">
        <?= Html::encode($this->title) ?>
        <div class="pull-right">
            <?php
            if ($this->context->addCreateButton) {
                echo Html::a(Yii::t($this->context->messageCategory, 'Create item'), ['create'], ['class' => 'btn btn-success']);
            }
            ?>
        </div>
    </h1>
    <?php
        $grid = GridView::begin($gridOptions);
        GridView::end();
    ?>
</div>