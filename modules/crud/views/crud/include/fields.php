<?php
use yii\bootstrap\Html;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $builder app\modules\crud\builder\FormBuilder */

echo $form->errorSummary($model, ['showAllErrors' => true]);

if ($builder->fieldset2fields && $builder->fields) {
    $notInFieldSets = $builder->getNotFieldsetFields();
    if ($notInFieldSets) {
        echo "<!-- not in fieldset fields -->";
        echo $builder->fields2string($notInFieldSets, $form, $model);
    }

    foreach ($builder->fieldset2fields as $fieldset => $fields) {
        echo "<!-- fieldset '{$fieldset}' -->";
        echo Html::beginTag('fieldset', isset($builder->fieldsetAttrs[$fieldset])? $builder->fieldsetAttrs[$fieldset] : []);

        $str = $builder->fieldsBeforeFieldsetLegend2string($fieldset, $form, $model);
        $str .= $builder->getFieldSetLegend($fieldset);
        $str .= $builder->fieldsAfterFieldsetLegend2string($fieldset, $form, $model);
        echo Html::tag('legend', $str);

        $fields = $builder->skipFieldsetLegendFields($fieldset, $fields);
        echo $builder->fields2string($fields, $form, $model);

        echo Html::endTag('fieldset');
    }
} else {
    echo $builder->fields2string($builder->fields, $form, $model);
}