<?php
namespace app\modules\crud\grid\toolbar;

use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\CheckboxColumn;

use app\modules\crud\grid\toolbar\Button;

/**
 * Description of Delete
 *
 */
class SendFormButton extends Button {
    public $action = [];

    public $options = ['data-role' => 'grid-button-send'];

    public function getAttrs() {
        $attrs = parent::getAttrs();
        if ($this->action) {
            $attrs['data-url'] = Url::to($this->action);
        }

        $this->addCsrf($attrs);
        foreach ($this->grid->columns as $column) {
            if ($column instanceof CheckboxColumn) {
                $attrs['data-checkbox-name'] = $column->name;
            }
        }

        return $attrs;
    }
}