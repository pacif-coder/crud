<?php
namespace Crud\grid;

use yii\bootstrap\ActiveForm;

class SearchForm extends ActiveForm
{
    public $action = ['index'];
    public $method = 'get';
    public $layout = 'inline';
    public $fieldConfig = [
        'labelOptions' => ['class' => 'control-label'],
    ];
}
