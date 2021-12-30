<?php
namespace app\modules\crud\grid\column;

use yii\helpers\Html;
use yii\helpers\Json;

/**
 *
 */
class CheckboxColumn extends \yii\grid\CheckboxColumn {
    public $headerOptions = ['class' => 'checkbox-column'];

    public $checkedActions = [];

    public $checkPermission = null;

    protected function renderDataCellContent($model, $key, $index) {
        if ($this->checkboxOptions instanceof Closure) {
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
