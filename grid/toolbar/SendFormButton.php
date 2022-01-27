<?php
namespace app\modules\crud\grid\toolbar;

use yii\grid\CheckboxColumn;
use yii\grid\GridView;

use app\modules\crud\grid\toolbar\Button;
use app\modules\crud\grid\toolbar\NeedCheckboxColumnInterface;

/**
 * Description of Delete
 *
 */
class SendFormButton extends Button implements NeedCheckboxColumnInterface
{
    public $options = ['data-role' => 'grid-button-send'];

    protected static $isAddAction = true;

    public function getAttrs()
    {
        $attrs = parent::getAttrs();

        $attrs['data-is-inside-form'] = $this->grid->surroundForm || $this->grid->isInsideForm;

        if (!is_a($this->grid, GridView::class)) {
            return $attrs;
        }

        foreach ($this->grid->columns as $column) {
            if (!($column instanceof CheckboxColumn)) {
                continue;
            }

            $attrs['data-checkbox-name'] = $column->name;
            $attrs['data-checkbox-role'] = $column->checkboxOptions['data-role'];
            break;
        }

        return $attrs;
    }

    public function isShow()
    {
        return $this->grid->dataProvider->getTotalCount() > 0;
    }
}