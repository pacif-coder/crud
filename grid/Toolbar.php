<?php
namespace Crud\grid;

use Yii;
use yii\helpers\Html;
use Crud\grid\toolbar\NeedCheckboxColumnInterface;

use Crud\controls\Base;

/**
 * Create and show grid buttons
 *
 */
class Toolbar
{
    public $grid;

    public $buttons;

    /**
     * Return html representation of tollbar
     */
    public function render()
    {
        $this->createButtons();

        // get html representation of button, with it order
        $left = $right = [];
        foreach ($this->buttons as $button) {
            /*@var $button Base */
            if (!$button->isShow()) {
                continue;
            }

            $html = $button->html();
            if ('right' == $button->align) {
                $right[] = ['html' => $html, 'order' => $button->order];
            } else {
                $left[] = ['html' => $html, 'order' => $button->order];
            }
        }

        // reorder buttons in left part of tollbar
        $leftAsStr = '';
        usort($left, [static::class, 'cmpButtonOrder']);
        foreach ($left as $button) {
            $leftAsStr .= $button['html'];
        }

        // reorder buttons in right part of tollbar, in reverse order (that is, the
        // buttons with the lower order are to the right)
        $rightAsStr = '';
        usort($right, [static::class, 'cmpButtonOrder']);
        $right = array_reverse($right);
        foreach ($right as $button) {
            $rightAsStr .= $button['html'];
        }

        // put it all together
        $widthClass = $leftAsStr && $rightAsStr ? ' half-width' : '';
        if ($leftAsStr) {
            $leftAsStr = Html::tag('div', $leftAsStr, ['class' => 'pull-left' . $widthClass]);
        }

        if ($rightAsStr) {
            $rightAsStr = Html::tag('div', $rightAsStr, ['class' => 'pull-right text-right' . $widthClass]);
        }

        return Html::tag('div', $leftAsStr . $rightAsStr, ['class' => 'clearfix toolbar']);
    }

    /**
     * Create buttons in tollbar
     */
    protected function createButtons()
    {
        if (null !== $this->buttons) {
            return;
        }

        $this->buttons = [];
        $buttons = array_merge($this->grid->baseToolbarButtons, $this->grid->addToolbarButtons);
        foreach ($buttons as $i => $button) {
            if (in_array($button, $this->grid->removeToolbarButtons)) {
                unset($buttons[$i]);
            }
        }

        foreach ($buttons as $button) {
            $options = null;
            if (is_string($button)) {

                if (is_array($this->grid->toolbarButtonOptions) && isset($this->grid->toolbarButtonOptions[$button])) {
                    $options = $this->grid->toolbarButtonOptions[$button];
                }

                // if the button name does not contain slashes - this is the internal button
                if (false === strpos($button, '\\')) {
                    $button = ['class' => __NAMESPACE__ . '\toolbar\\' . ucfirst($button)];
                } else {
                    $button = ['class' => $button];
                }
            }

            if (null !== $options) {
                $button = array_merge($button, $options);
            }

            $button['grid'] = $this->grid;

            $button['messageCategory'] = $this->grid->messageCategory;

            $this->buttons[] = Yii::createObject($button);
        }
    }

    /**
     * Some button needs a checkbox column?
     */
    public function isNeedCheckboxColumn()
    {
        $this->createButtons();
        foreach ($this->buttons as $button) {
            if ($button instanceof NeedCheckboxColumnInterface) {
                return true;
            }
        }
    }

    public function collectButtonActions()
    {
        $actions = [];
        $this->createButtons();
        foreach ($this->buttons as $button) {
            if ($button instanceof NeedCheckboxColumnInterface && $button->action) {
                $actions[] = $button->action;
            }
        }

        return $actions;
    }

    /**
     * Inner compare button order function
     */
    public static function cmpButtonOrder($button1, $button2)
    {
        if (null === $button1['order']) {
            $button1['order'] = 0;
        }

        if (null === $button2['order']) {
            $button2['order'] = 0;
        }

        if ($button1['order'] == $button2['order']) {
            return 0;
        }

        return ($button1['order'] > $button2['order']) ? +1 : -1;
    }
}