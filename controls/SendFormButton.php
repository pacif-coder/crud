<?php
namespace app\modules\crud\controls;

/**

 *
 */
class SendFormButton extends Base
{
    public $options = ['data-role' => 'submit-form-with-url'];

    protected static $isAddAction = true;
}