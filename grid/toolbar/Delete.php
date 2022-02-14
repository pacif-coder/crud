<?php
namespace app\modules\crud\grid\toolbar;

use Yii;

/**
 * Button to delete an object on the grid with confirmation 
 *
 */
class Delete extends SendFormButton
{
    public $action = 'delete';

    public $label = 'Delete';

    public $colorClass = 'btn-danger';

    public $icon = 'remove';

    protected static $defMessageCategory = 'yii';

    protected static $isUseDefMessageCategory = true;

    protected static $message = 'Are you sure you want to delete this item?';

    public function getAttrs()
    {
        $attrs = parent::getAttrs();
        $attrs['data-confirm-message'] = Yii::t(static::$defMessageCategory, self::$message);

        return $attrs;
    }
}