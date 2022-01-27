<?php
namespace app\modules\crud\grid\toolbar;

use Yii;

/**
 * Delete button
 *
 */
class Delete extends SendFormButton
{
    public $action = 'delete';
    public $label = 'Delete';
    public $colorClass = 'btn-danger';
    public $icon = 'remove';
    public $messageCategory = 'yii';

    protected static $message = 'Are you sure you want to delete this item?';

    public function getAttrs()
    {
        $attrs = parent::getAttrs();
        $attrs['data-confirm-message'] = Yii::t($this->messageCategory, self::$message);

        return $attrs;
    }
}