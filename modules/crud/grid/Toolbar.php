<?php
namespace app\modules\crud\grid;

use Yii;
use yii\helpers\Html;
/**
 * Description of Toobar
 *
 */
class Toolbar {
    public $grid;

    public function render() {
        $left = $right = '';

        foreach ($this->grid->toolbarButtons as $button) {
            if (is_string($button)) {
                if (false === strpos($button, '\\')) {
                    $button = ['class' => __NAMESPACE__ . '\toolbar\\' . ucfirst($button)];
                } else {
                    $button = ['class' => $button];
                }
            }
            $button['grid'] = $this->grid;

            $button = Yii::createObject($button);

            if ('right' == $button->align) {
                $right .= $button->html();
            } else {
                $left .= $button->html();
            }
        }

        $widthClass = $right && $left? ' half-width' : '';
        if ($left) {
            $left = Html::tag('div', $left, ['class' => 'pull-left' . $widthClass]);
        }

        if ($right) {
            $right = Html::tag('div', $right, ['class' => 'pull-right text-right' . $widthClass]);
        }

        return Html::tag('div', $left . $right, ['class' => 'clearfix toolbar']);
    }
}
