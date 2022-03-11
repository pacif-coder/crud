<?php
namespace app\modules\crud\widgets;

use Yii;
use yii\widgets\InputWidget;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;

use app\modules\crud\widgets\assets\CheckboxMartixAsset as Asset;

/**
 * fdsfs
 *
 */
class CheckboxMartixList extends InputWidget
{
    public $items;

    public $tag = 'div';

    public $unselect = '';

    public $encode = true;

    public $itemOptions = [];

    public $model;

    public $column = 3;

    public $disabled;

    public $forceChecked;

    public $isTruncate = true;

    public $noBorder = true;

    public function init()
    {
        parent::init();

        if (!array_key_exists('unselect', $this->options)) {
            $this->options['unselect'] = '';
        }
    }

    public function run()
    {
        Html::addCssClass($this->options, 'checkbox-martix-list');
        $this->options['data-role'] = 'checkbox-martix-list';

        if ($this->noBorder) {
            Html::addCssClass($this->options, 'no-border');
        }

        Asset::register($this->getView());

        $checkboxList = $this->items2checkboxList($this->items);
        $content = $this->checkboxList2martix($checkboxList);

        return $this->getHidden() . Html::tag($this->tag, $content, $this->options);
    }

    protected function checkboxList2martix($checkboxs)
    {
        $colClassI = (int) 12 / $this->column;
        $content = '';
        foreach ($checkboxs as $checkbox) {
            $content .= Html::tag('div', $checkbox, ['class' => "col-xs-{$colClassI}"]);
        }

        return Html::tag('div', $content, ['class' => 'row']);
    }

    protected function getHidden()
    {
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

    protected function items2checkboxList($items)
    {
        if (!$this->items) {
            return [];
        }

        $itemOptions = ArrayHelper::remove($this->options, 'itemOptions', []);
        if ($this->disabled) {
            $itemOptions['disabled'] = 'disabled';
        }

        if ($this->isTruncate) {
            $itemOptions['labelOptions']['class'] = 'truncate';
        }

        $elements = [];
        $index = 0;
        $name = $this->getName();
        $selection = $this->getSelection();
        $formatter = ArrayHelper::remove($this->options, 'item');
        foreach ($items as $value => $label) {
            $checked = $this->isChecked($value, $selection);

            if ($formatter !== null) {
                $elements[] = call_user_func($formatter, $index, $label, $name, $checked, $value);
            } else {
                $options = array_merge($itemOptions, [
                    'value' => $value,
                    'label' => $this->encode ? Html::encode($label) : $label,
                ]);

                if ($this->isTruncate) {
                    $options['labelOptions']['title'] = $label;
                }

                $elements[] = Html::checkbox($name, $checked || $this->forceChecked, $options);
            }
            $index++;
        }

        return $elements;
    }

    protected function getSelection()
    {
        if (isset($this->options['value'])) {
            $selection = $this->options['value'];
        } else {
            $selection = Html::getAttributeValue($this->model, $this->attribute);
        }

        if (ArrayHelper::isTraversable($selection)) {
            $selection = array_map('strval', (array) $selection);
        }

        return $selection;
    }

    protected function isChecked($value, $selection)
    {
        if (null === $selection) {
            return false;
        }

        if (!ArrayHelper::isTraversable($selection) && !strcmp($value, $selection)) {
            return true;
        }

        if (ArrayHelper::isTraversable($selection) && ArrayHelper::isIn((string) $value, $selection)) {
            return true;
        }

        return false;
    }

    protected function getName()
    {
        $name = isset($this->options['name']) ? $this->options['name'] : Html::getInputName($this->model, $this->attribute);
        if (substr($name, -2) !== '[]') {
            $name .= '[]';
        }

        return $name;
    }
}