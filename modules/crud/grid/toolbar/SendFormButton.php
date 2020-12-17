<?php
namespace app\modules\crud\grid\toolbar;

use Yii;
use yii\helpers\Url;
use yii\grid\CheckboxColumn;

use app\modules\crud\grid\toolbar\Button;
use app\modules\crud\grid\toolbar\NeedCheckboxColumnInterface;

/**
 * Description of Delete
 *
 */
class SendFormButton extends Button implements NeedCheckboxColumnInterface
{
    public $action;
    public $options = ['data-role' => 'grid-button-send'];

    public function getAttrs()
    {
        $attrs = parent::getAttrs();
        if ($this->action) {
            $params = Yii::$app->request->get();
            $params[0] = $this->action;

            $attrs['data-url'] = Url::toRoute($params);
        }

        $attrs['data-is-inside-form'] = $this->grid->surroundForm || $this->grid->isInsideForm;

        foreach ($this->grid->columns as $column) {
            if ($column instanceof CheckboxColumn) {
                $attrs['data-checkbox-name'] = $column->name;
                break;
            }
        }

        return $attrs;
    }

    public function html()
    {
        if ($this->grid->dataProvider->getTotalCount()) {
            return parent::html();
        }
    }
}