<?php
use yii\bootstrap\Html;
use app\modules\crud\grid\GridView;
use app\modules\admin\models\Competitor;
use app\modules\crud\grid\SearchForm;
use yii\web\View;

/* @var $this yii\web\View */
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
    /* @var $dataProvider yii\data\ActiveDataProvider */
    $grid = GridView::begin([
        'dataProvider' => $provider,
        'filterModel' => $this->context->filterInGrid && $filterModel? $filterModel : null,
        'showHeader' => true,
        'columns' => $inspector->columns,
    ]);
    ?>
    <div class="m-b-5">
        <div class="text-right">
            <?php
            if ($filterModel && $filterModel->isLoaded()) {
                $attrs = ['class' => 'btn btn-xs btn-info', 'data-role' => 'grid-filter-clear'];
                echo Html::a(Html::icon('remove') . ' Сбросить фильтр', $grid->getClearFilterUrl(), $attrs);
            }
            ?>
        </div>
    </div>
    <?php
    GridView::end();
    ?>
</div>