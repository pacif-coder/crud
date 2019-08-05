<?php
namespace app\modules\crud\grid\toolbar;

use Yii;
use yii\helpers\Html;

use app\modules\crud\grid\toolbar\SendFormButton;

/**
 * Description of Delete
 *
 */
class Delete extends SendFormButton {
    public $action = ['mass-delete'];
    public $label = 'Delete';
    public $colorClass = 'btn-danger';
    public $icon = 'remove';

    public function getAttrs() {
        $attrs = parent::getAttrs();
        $attrs['data-confirm-message'] = Yii::t('yii', 'Are you sure you want to delete this item?');
        return $attrs;
    }
}