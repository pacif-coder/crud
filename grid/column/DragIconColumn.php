<?php
namespace app\modules\crud\grid\column;

use yii\bootstrap\Html;

/**
 *
 */
class DragIconColumn extends \yii\grid\Column
{
    public $headerOptions = ['class' => 'column-width-30'];

    /**
     * {@inheritdoc}
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        return Html::button(Html::icon('move'), ['class' => 'btn btn-info btn-xs', 'data-role' => 'drag-icon-column']);
    }
}
