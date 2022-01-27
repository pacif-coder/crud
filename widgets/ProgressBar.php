<?php
namespace app\modules\crud\widgets;

use yii\helpers\Html;

use app\modules\crud\widgets\assets\ProgressBarAsset;
use app\modules\crud\widgets\assets\RefreshAjaxForm;

/**
 * ProgressBar
 *
 */
class ProgressBar extends \yii\widgets\InputWidget
{
    public $withText = true;

    public $colorClass;

    protected static $attrs = [
        'class' => 'progress-bar',
        'role' => 'progressbar',
        'aria-valuemin' => '0',
        'aria-valuemax' => '100',
    ];

    public function run()
    {
        ProgressBarAsset::register($this->getView());
        RefreshAjaxForm::register($this->getView());

        $value = (float) Html::getAttributeValue($this->model, $this->attribute);

        $attrs = static::$attrs;
        $attrs['aria-valuenow'] = $value;
        Html::addCssStyle($attrs, "width: {$value}%");

        if ($this->colorClass) {
            Html::addCssClass($attrs, "progress-bar-{$this->colorClass}");
        }

        $text = $this->withText? "{$value}%" : '';
        $content = Html::tag('div', $text, $attrs);

        $attrs = ['class' => 'progress crud-widget-progress-bar'];
        $attrs['id']  = Html::getInputId($this->model, $this->attribute);

        return Html::tag('div', $content, $attrs);
    }
}