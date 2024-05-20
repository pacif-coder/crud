<?php
namespace Crud\grid\column;

use yii\helpers\Html;
use yii\helpers\Json;

use Closure;

/**
 *
 */
class CheckboxColumn extends \yii\grid\CheckboxColumn
{
    public $headerOptions = ['class' => 'checkbox-column'];

    public $checkPermission = null;

    public $checkboxOptions = ['data-role' => self::CHECKBOX_COLUMN_DATA_ROLE];

    const CHECKBOX_COLUMN_DATA_ROLE = 'checkbox-column';

    protected function renderDataCellContent($model, $key, $index)
    {
        if ($this->content !== null) {
            return parent::renderDataCellContent($model, $key, $index);
        }

        if (is_callable($this->checkboxOptions) || $this->checkboxOptions instanceof Closure) {
            $options = call_user_func($this->checkboxOptions, $model, $key, $index, $this);
        } else {
            $options = $this->checkboxOptions;
        }

        if (!isset($options['value'])) {
            $options['value'] = is_array($key) ? Json::encode($key) : $key;
        }

        if ($this->cssClass !== null) {
            Html::addCssClass($options, $this->cssClass);
        }

        return Html::checkbox($this->name, !empty($options['checked']), $options);
    }
}
