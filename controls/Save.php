<?php
namespace app\modules\crud\controls;

/**
 * @XXX
 *
 */
class Save extends Base
{
    public $label = 'save';

    public $icon = 'ok';

    public $colorClass = 'btn-success';

    public $place = 'bottom/right';

    public $options = [
        'type' => 'submit',
    ];

    protected static $isUseDefMessageCategory = true;
}