<?php
namespace app\modules\crud\grid\toolbar;

use Yii;
use yii\helpers\Html;

use app\modules\crud\grid\toolbar\SendFormButton;
use yii\web\Request;

/**
 * Description of Delete
 *
 */
class Delete extends SendFormButton {
    public $action = 'delete';
    public $label = 'Delete';
    public $colorClass = 'btn-danger';
    public $icon = 'remove';
    public $order = -1;
    public $messageCategory = 'yii';

    public function getAttrs() {
        $attrs = parent::getAttrs();
        $attrs['data-confirm-message'] = Yii::t($this->messageCategory, 'Are you sure you want to delete this item?');

        return $attrs;
    }
}