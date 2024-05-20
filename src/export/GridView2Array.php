<?php
namespace Crud\export;

use Yii;
use yii\grid\DataColumn;

use Crud\grid\column\ActionLinkColumn;

/**
 *
 */
class GridView2Array extends \Crud\grid\BaseGridView
{
    protected $table = [];

    /**
     * Save data to path
     */
    public function asArray()
    {
        $this->renderItems();

        return $this->table;
    }

    public function renderItems()
    {
        $models = array_values($this->dataProvider->getModels());
        $keys = $this->dataProvider->getKeys();

        foreach ($models as $index => $model) {
            $key = $keys[$index];
            $this->renderRow($model, $key, $index);
        }
    }

    public function renderRow($model, $key, $index)
    {
        $row = [];
        $formatter = $this->formatter;
        foreach ($this->columns as $column) {
            /* @var $column Column */
            if (!is_a($column, DataColumn::class)) {
                continue;
            }

            if (is_a($column, ActionLinkColumn::class)) {
                $val = $column->asArray($model, $key, $index);
            } else {
                $val = $column->getDataCellValue($model, $key, $index);
                $val = null !== $val? $formatter->format($val, $column->format) : '';
            }

            $row[$column->attribute] = $val;
        }

        $this->table[] = $row;
    }
}