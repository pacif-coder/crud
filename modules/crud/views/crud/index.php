<?php
use Yii;
use yii\bootstrap\Html;
use app\modules\crud\grid\GridView;
use yii\web\View;

use app\modules\crud\behaviors\BackUrlBehavior;

/* @var $this yii\web\View */
/* @var $grid app\modules\crud\grid\GridView */
/* @var $builder app\modules\crud\builder\GridBuilder */
?>
<div>
    <h1 class="main-title clearfix">
        <?= preg_replace('/&quot;(.*?)&quot;/', '<span>$1</span>', Html::encode($this->title)) ?>
        <div class="pull-right">
            <?php
            if ($this->context->addCreateButton) {
                $url = BackUrlBehavior::addBackUrl(['create']);
                echo Html::a(Yii::t($this->context->messageCategory, 'Create item'), $url, ['class' => 'btn btn-success']);
            }
            ?>
        </div>
    </h1>
    <?php
        include 'include/flashes.php';

        $grid = GridView::begin($builder->getOptions());
        GridView::end();
    ?>
</div>