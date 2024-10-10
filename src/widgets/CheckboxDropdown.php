<?php
namespace Crud\widgets;

use yii\helpers\ArrayHelper;

use Crud\helpers\Html;
use Crud\helpers\Lang;
use Crud\widgets\assets\CheckboxDropdownAsset;

/**
 *
 */
class CheckboxDropdown extends \yii\widgets\InputWidget
{
    public $items;

    public $withEmptyLabel = true;

    public $emptyLabel;

    public $addCssClass;

    public $withHidden = true;

    public $asset = CheckboxDropdownAsset::class;

    protected static $defAttrs = [
        'class' => 'checkbox-dropdown',
        'data-role' => 'checkbox-dropdown',
    ];

    protected static $innerDivAttrs = [
        'class' => 'form-control',
    ];

    protected static $radiosAttrs = [
        'class' => 'radios',
        'data-role' => 'radios',
        'unselect' => '',
    ];

    protected static $toggleAttrs = [
        'class' => 'toggle text-truncate',
        'data-role' => 'toggle',
    ];

    protected static $emptyLabelAttrs = [
        'class' => 'text-secondary',
        'data-role' => 'emptyLabel',
    ];

    public function init()
    {
        parent::init();

        $view = $this->getView();
        foreach ((array) $this->asset as $asset) {
            $view->registerAssetBundle($asset);
        }
    }

    public function run()
    {
        return $this->html();
    }

    public function html()
    {
        $emptyLabel = $this->getEmptyLabelAsHtml();
        $str = Html::tag('div', $emptyLabel, static::$toggleAttrs);

        $htmlClass = Html::getBootstrapClass('Html');

        $radiosAttrs = static::$radiosAttrs;
        $str .= $htmlClass::activeCheckboxList($this->model, $this->attribute, $this->items, $radiosAttrs);

        $attrs = $this->options;
        Html::removeCssClass($attrs, ['form-control', 'form-select']);

        $innerDiv = Html::tag('div', $str, static::$innerDivAttrs);

        $class = ArrayHelper::remove(static::$defAttrs, 'class', '');
        Html::addCssClass($attrs, $class);
        $attrs = array_merge(static::$defAttrs, $attrs);
        Html::addCssClass($attrs, $this->addCssClass);

        return Html::tag('div', $innerDiv, $attrs);
    }

    protected function getEmptyLabelAsHtml()
    {
        if (!$this->withEmptyLabel) {
            return '';
        }

        if (!$this->emptyLabel) {
            $category = static::getMessageCategory();
            $this->emptyLabel = Lang::t($category, 'emptyLabel');
        }

        $emptyLabelAttrs = static::$emptyLabelAttrs;
        $value = (bool) Html::getAttributeValue($this->model, $this->attribute);
        Html::toggleCssClass($emptyLabelAttrs, 'd-none', !$value);

        return Html::tag('span', $this->emptyLabel, $emptyLabelAttrs);
    }

    public static function getMessageCategory()
    {
        return Lang::getParentCategorysByRel(static::class, 'widgets');
    }
}