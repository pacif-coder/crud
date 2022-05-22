<?php
namespace app\modules\crud\controls;

use yii\bootstrap\Html;
use yii\helpers\ArrayHelper;

/**
 * Button to add new object
 *
 */
class CreateAsDropdown extends Create
{
    public $item = [];

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

        $attrs = $this->getAttrs();
        $button = Html::tag('button', $content, $attrs);

        $list = '';
        $backup = $this->params;
        foreach ($this->item as $label => $data) {
            $label = $this->t($label);
            $this->params = ArrayHelper::merge($this->params, $data);
            $url = $this->getUrl();

            $link = Html::a($label, $url);
            $list .= Html::tag('li', $link);
        }

        $attrs = ['class' => 'dropdown-menu dropdown-menu-right'];
        $attrs['aria-labelledby'] = $this->getId();
        $list = Html::tag('ul', $list, $attrs);

        $attrs = ['class' => 'dropdown inline-dropdown'];
        return Html::tag('div', $button . $list, $attrs);
    }
}