<?php
namespace Crud\controls;

use Crud\helpers\Html;

use yii\helpers\ArrayHelper;

/**
 * Button to add new object
 *
 */
class CreateAsDropdown extends Create
{
    public $items = [];

    public $options = [
        'class' => 'btn btn-success dropdown-toggle',
        'type' => 'button',
        'data-toggle' => 'dropdown',
        'aria-haspopup' => 'true',
        'aria-expanded' => 'true'
    ];

    protected static $isUseDefMessageCategory = false;

    public function html()
    {
        $content = '';
        $icon = $this->getIcon();
        if ($icon) {
            $content .= $icon . ' ';
        }

        $content .= $this->getTransLabel();
        $content .= ' ';
        $content .= Html::tag('span', '', ['class' => 'caret']);

        $isBs5 = Html::getBootstrapVersion();

        $attrs = $this->getAttrs();
        if ($isBs5) {
            $attrs['data-bs-toggle'] = $attrs['data-toggle'];
            unset($attrs['data-toggle']);
        }

        $button = Html::tag('button', $content, $attrs);

        $list = '';
        $backup = $this->params;
        $attrs = [];
        if ($isBs5) {
            Html::addCssClass($attrs, 'dropdown-item');
        }

        foreach ($this->items as $label => $data) {
            $label = $this->t($label);
            $this->params = ArrayHelper::merge($this->params, $data);
            $url = $this->getUrl();

            $link = Html::a($label, $url);
            $list .= Html::tag('li', $link, $attrs);
        }

        $attrs = ['class' => 'dropdown-menu'];
        $attrs['aria-labelledby'] = $this->getId();
        $list = Html::tag('ul', $list, $attrs);

        $attrs = ['class' => 'dropdown inline-dropdown'];
        if ($isBs5) {
            Html::removeCssClass($attrs, 'inline-dropdown');
        }

        return Html::tag('div', $button . $list, $attrs);
    }
}