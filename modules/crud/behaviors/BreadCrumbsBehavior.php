<?php
namespace app\modules\crud\behaviors;

use Yii;
use yii\base\Behavior;

/**
 *
 */
class BreadCrumbsBehavior extends Behavior {
    public $breadCrumbs = [
        [
            'url' => '/geo/city/index',
            'label' => 'Города',
        ],
    ];
}