<?php
use yii\bootstrap\Html;

$control = $form->field($model, $field);
$fieldType = isset($builder->fieldTypes[$field]) ? $builder->fieldTypes[$field] : null;
$options = isset($builder->fieldOptions[$field]) ? $builder->fieldOptions[$field] : [];

switch ($fieldType) {
    case 'static':
        $fieldType = 'staticControl';
        break;

    case 'select':
        $fieldType = 'dropDownList';
        break;
}

switch ($fieldType) {
    case 'staticControl':
        Html::addCssClass($control->options, 'no-required');
        $control->enableClientValidation = false;
        echo $control->staticControl($options);
        break;

    case 'dropDownList':
    case 'checkboxList':
    case 'radioList':
        echo $control->{$fieldType}($builder->enumOptions[$field], $options);
        break;

    case 'textarea':
        echo $control->textarea($options);
        break;

    case 'email':
        echo $control->input('text', $options);
        break;

    case 'phone':
        echo $control->widget(\yii\widgets\MaskedInput::className(), [
            'mask' => '8 (999) 999 99 99',
        ]);
        break;

    case 'boolean':
        echo $control->checkbox($options);
        break;

    case 'password':
        echo $control->input('password', $options);
        break;

    case 'file':
        echo $control->widget(\app\modules\crud\widgets\FileInput::className(), $options);
        break;

    case 'date':
        echo $control->widget(\yii\jui\DatePicker::classname(), $options);
        break;

    default:
        echo $control->input('text', $options);
        break;
}
