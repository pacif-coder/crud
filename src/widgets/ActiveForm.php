<?php
namespace Crud\widgets;

use Crud\helpers\Html;

class ActiveForm extends \yii\bootstrap\ActiveForm
{
    public function errorSummary($models, $options = [])
    {
        Html::addCssClass($options, $this->errorSummaryCssClass);
        $options['encode'] = $this->encodeErrorSummary;
        return Html::errorSummary($models, $options);
    }
}