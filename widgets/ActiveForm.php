<?php
namespace app\modules\crud\widgets;

use app\modules\crud\helpers\Html;

class ActiveForm extends \yii\bootstrap\ActiveForm
{
    public function errorSummary($models, $options = [])
    {
        Html::addCssClass($options, $this->errorSummaryCssClass);
        $options['encode'] = $this->encodeErrorSummary;
        return Html::errorSummary($models, $options);
    }
}