<?php
namespace app\modules\crud\grid\toolbar;

use yii\helpers\Html;
use app\modules\crud\grid\toolbar\Button;
/**
 * Description of Delete
 *
 */
class Delete extends Button {
    public $colorClass = 'btn-danger';

    public $icon = 'remove';

    public $label = 'Удалить';

    public $options = ['data-role' => 'grid-delete', 'disabled' => 'disabled'];
}
