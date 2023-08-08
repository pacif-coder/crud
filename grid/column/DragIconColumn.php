<?php
namespace Crud\grid\column;

use Crud\helpers\Html;

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
        $size = Html::getSmallSize();
        $attrs = [
            'class' => "btn btn-info btn-{$size}",
            'data-role' => 'drag-icon'
        ];

        if ($this->grid->dataProvider->getTotalCount() < 2) {
            $attrs['disabled'] = 'disabled';
        }

        return Html::button(Html::icon('move'), $attrs);
    }
}
