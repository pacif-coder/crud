<?php
namespace Crud\grid\toolbar;

use Yii;
use Crud\helpers\Lang;

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

    protected $defMessageCategory = 'yii';

    protected static $isUseDefMessageCategory = true;

    protected static $defConfirmMessage = 'Are you sure you want to delete this item?';

    public function getAttrs()
    {
        $attrs = parent::getAttrs();

        if (null !== $this->confirmMessage) {
            $message = Lang::t($this->messageCategory, $this->confirmMessage);
        } else {
            $message = Lang::t($this->defMessageCategory, static::$defConfirmMessage);
        }

        $attrs['data-confirm-message'] = $message;

        return $attrs;
    }
}