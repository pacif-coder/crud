<?php
namespace app\modules\crud\controls;

/**
 * Button to add new object
 *
 */
class Create extends Link
{
    public $label = 'Create item';

    public $place = 'title';

    public $colorClass = 'btn-success';

    public $icon = 'plus';

    public $action = 'create';

    public $backUrl = true;
}