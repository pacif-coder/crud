<?php
namespace app\modules\crud\widgets;

use Yii;
use yii\widgets\InputWidget;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;

/**
 * fdsfs
 *
 */
class CheckboxMartixList extends InputWidget {
    public $items;

    public $tag = 'div';

    public $unselect = '';

    public $encode = true;

    public $itemOptions = [];

    public $model;

    public $column = 3;

    public $disabled;

    public function init() {
        parent::init();

        if (!array_key_exists('unselect', $this->options)) {
            $this->options['unselect'] = '';
        }
    }

    public function run() {
        Html::addCssClass($this->options, 'checkbox-martix-list');
        Html::removeCssClass($this->options, 'form-control');

        $checkboxList = $this->items2checkboxList($this->items);
        $content = $this->checkboxList2martix($checkboxList);

        return $this->getHidden() . Html::tag($this->tag, $content, $this->options);
    }

    protected function checkboxList2martix($checkboxs) {
        $content = '';
        $oldRow = -1;
        $index = 0;
        $colClassI = (int) 12 / $this->column;
        foreach ($checkboxs as $checkbox) {
            $row = floor($index / $this->column);
            if ($oldRow != $row) {
                if ($index) {
                    $content .= Html::endTag('div');
                }

                $content .= Html::beginTag('div', ['class' => 'row']);
            }

            $content .= Html::tag('div', $checkbox, ['class' => "col-xs-{$colClassI}"]);
            $oldRow = $row;
            $index++;
        }

        if ($content) {
            $content .= Html::endTag('div');
        }

        return $content;
    }

    protected function getHidden() {
        if (!isset($this->options['unselect'])) {
            return '';
        }

        // add a hidden field so that if the list box has no option being selected, it still submits a value
        $name = $this->getName();
        $name2 = substr($name, -2) === '[]' ? substr($name, 0, -2) : $name;
        $hidden = Html::hiddenInput($name2, $this->options['unselect']);
        unset($this->options['unselect']);

        return $hidden;
    }

    protected function items2checkboxList($items) {
        if (!$this->items) {
            return [];
        }

        $selection = isset($this->options['value']) ? $this->options['value'] : Html::getAttributeValue($this->model, $this->attribute);
        if (ArrayHelper::isTraversable($selection)) {
            $selection = array_map('strval', (array)$selection);
        }

        $formatter = ArrayHelper::remove($this->options, 'item');
        $itemOptions = ArrayHelper::remove($this->options, 'itemOptions', []);

        if ($this->disabled) {
            $itemOptions['disabled'] = 'disabled';
        }

        $elements = [];
        $index = 0;
        $name = $this->getName();
        foreach ($items as $value => $label) {
            $checked = $selection !== null &&
                (!ArrayHelper::isTraversable($selection) && !strcmp($value, $selection)
                    || ArrayHelper::isTraversable($selection) && ArrayHelper::isIn((string)$value, $selection));
            if ($formatter !== null) {
                $elements[] = call_user_func($formatter, $index, $label, $name, $checked, $value);
            } else {
                $elements[] = Html::checkbox($name, $checked, array_merge($itemOptions, [
                    'value' => $value,
                    'label' => $this->encode ? Html::encode($label) : $label,
                ]));
            }
            $index++;
        }

        return $elements;
    }

    protected function getName() {
        $name = isset($this->options['name']) ? $this->options['name'] : Html::getInputName($this->model, $this->attribute);
        if (substr($name, -2) !== '[]') {
            $name .= '[]';
        }

        return $name;
    }
}