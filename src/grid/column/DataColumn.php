<?php
namespace Crud\grid\column;

use Crud\helpers\Html;

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

    public function init()
    {
        parent::init();

        if (!isset($this->filterInputOptions['class'])) {
            return;
        }

        if (5 != Html::getBootstrapVersion()) {
            return;
        }

        if (!is_array($this->filter) && !$this->format === 'boolean') {
            return;
        }

        // change control class name for dropdawn only in Bootstrap5
        if ('form-control' == $this->filterInputOptions['class']) {
            $this->filterInputOptions['class'] = 'form-select';
        }
    }

    protected function renderDataCellContent($model, $key, $index)
    {
        $str = parent::renderDataCellContent($model, $key, $index);

        return $this->truncateContent($str);
    }
}