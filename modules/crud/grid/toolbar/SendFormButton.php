<?php
namespace app\modules\crud\grid\toolbar;

use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\CheckboxColumn;

use app\modules\crud\grid\toolbar\Button;
use app\modules\crud\grid\toolbar\NeedCheckboxColumnInterface;

/**
 * Description of Delete
 *
 */
class SendFormButton extends Button implements NeedCheckboxColumnInterface {
    public $action;

    public $options = ['data-role' => 'grid-button-send'];

    public function getAttrs() {
        $attrs = parent::getAttrs();
        if ($this->action) {
            $action = is_string($this->action)? [$this->action] : $this->action;
            $attrs['data-url'] = Url::to($action);
        }

        foreach ($this->grid->columns as $column) {
            if ($column instanceof CheckboxColumn) {
                $attrs['data-checkbox-name'] = $column->name;
                break;
            }
        }

        return $attrs;
    }

    public function html() {
        if ($this->grid->dataProvider->getTotalCount()) {
            return parent::html();
        }
    }
}