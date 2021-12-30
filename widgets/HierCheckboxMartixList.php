<?php
namespace app\modules\crud\widgets;

use Yii;
use yii\helpers\Html;

use app\modules\crud\widgets\assets\CheckboxMartixList as Asset;

/**
 * fdsfs
 */
class HierCheckboxMartixList extends CheckboxMartixList {
    public function run() {
        Html::addCssClass($this->options, 'hier-checkbox-martix-list');
        $this->options['data-role'] = 'hier-checkbox-martix-list';

        Asset::register($this->getView());

        $content = '';
        $selection = $this->getSelection();
        foreach ($this->items as $headerName => $secondLevel) {
            $isAllChecked = $secondLevel? true : false;
            foreach (array_keys($secondLevel) as $value) {
                if (!$this->isChecked($value, $selection)) {
                    $isAllChecked = false;
                    break;
                }
            }

            $checkboxList = $this->items2checkboxList($secondLevel);
            $matrix = $this->checkboxList2martix($checkboxList);

            $headerName = Html::encode($headerName);
            $checkbox = Html::checkbox(null, $isAllChecked, ['data-role' => 'hier-checkbox-martix-list-header-checkbox']);
            $header = Html::tag('label', $checkbox . $headerName, ['class' => 'header']);

            $content .= Html::tag('div', $header . $matrix, ['data-role' => 'hier-checkbox-martix-list-checkboxs']);
        }

        return $this->getHidden() . Html::tag($this->tag, $content, $this->options);
    }
}