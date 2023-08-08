<?php
namespace Crud\grid\toolbar;

use yii\grid\CheckboxColumn;
use yii\grid\GridView;

use Crud\grid\MatrixGridView;
use Crud\grid\toolbar\Button;
use Crud\grid\toolbar\NeedCheckboxColumnInterface;

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

        if (is_a($this->grid, GridView::class)) {
            foreach ($this->grid->columns as $column) {
                if (!($column instanceof CheckboxColumn)) {
                    continue;
                }

                $attrs['data-checkbox-name'] = $column->name;
                $attrs['data-checkbox-role'] = $column->checkboxOptions['data-role'];
                break;
            }
        } elseif (is_a($this->grid, MatrixGridView::class)) {
            $attrs['data-checkbox-name'] = $this->grid->cell->name;
            $attrs['data-checkbox-role'] = $this->grid->cell->checkboxOptions['data-role'];
        }

        return $attrs;
    }

    public function isShow()
    {
        return parent::isShow() && $this->grid->dataProvider->getTotalCount() > 0;
    }
}