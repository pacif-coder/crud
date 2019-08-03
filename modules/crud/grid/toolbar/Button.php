<?php
namespace app\modules\crud\grid\toolbar;

use yii\bootstrap\Html;

/**
 * @XXX
 *
 */
class Button extends \yii\base\BaseObject {
    public $gridID;

    public $label = '';

    public $icon = '';

    public $baseClass = 'btn';

    public $sizeClass = 'btn-xs';

    public $colorClass = 'btn-danger';

    public $options = ['data-role' => 'grid-delete', 'disabled' => 'disabled'];

    public function html() {
        $content = '';
        if ($this->icon) {
            $content .= Html::icon($this->icon) . ' ';
        }

        $content .= $this->label;
        return Html::button($content, $this->getAttrs());
    }

    public function getAttrs() {
        $attrs = $this->options;
        Html::addCssClass($attrs, $this->baseClass);
        Html::addCssClass($attrs, $this->colorClass);
        Html::addCssClass($attrs, $this->sizeClass);

        if ($this->gridID) {
            $attrs['data-target-grid'] = $this->gridID;
        }

        return $attrs;
    }
}