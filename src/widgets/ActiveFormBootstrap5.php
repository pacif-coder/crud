<?php
namespace Crud\widgets;

use Crud\helpers\Html;

class ActiveFormBootstrap5 extends \yii\bootstrap5\ActiveForm
{
    public function errorSummary1($models, $options = [])
    {
        Html::addCssClass($options, $this->errorSummaryCssClass);
        $options['encode'] = $this->encodeErrorSummary;
        return Html::errorSummary($models, $options);
    }
}