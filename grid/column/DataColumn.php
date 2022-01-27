<?php
namespace app\modules\crud\grid\column;

/**
 *
 *
 */
class DataColumn extends \yii\grid\DataColumn
{
    use TruncateColumnTrait;

    /**
     * @var string
     */
    public $truncateClass;

    protected function renderDataCellContent($model, $key, $index)
    {
        $str = parent::renderDataCellContent($model, $key, $index);
        return $this->truncateContent($str);
    }
}