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

    public $confirmMessage;

    // disabled by default
    public $disabled = true;

    protected static $defMessageCategory = 'yii';

    protected static $isUseDefMessageCategory = true;

    protected static $defConfirmMessage = 'Are you sure you want to delete this item?';

    public function getAttrs()
    {
        $attrs = parent::getAttrs();

        if (null !== $this->confirmMessage) {
            $message = Yii::t($this->messageCategory, $this->confirmMessage);
        } else {
            $message = Yii::t(static::$defMessageCategory, static::$defConfirmMessage);
        }

        $attrs['data-confirm-message'] = $message;

        return $attrs;
    }
}