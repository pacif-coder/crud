<?php
/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $builder app\modules\crud\builder\FormBuilder */

// use Yii;
use yii\bootstrap\Html;
use yii\bootstrap\ActiveForm;
use yii\web\View;
?>
<div>
    <?php
    echo $this->render('include/title', compact(['builder']));

    $form = ActiveForm::begin([
        'layout' => 'horizontal',
    ]);

    $form->errorSummary($model);

    if ($builder->fieldSet2fields && $builder->fields) {
        $notInFieldSets = $builder->fields;
        foreach ($builder->fieldSet2fields as $fieldSet => $fields) {
            $notInFieldSets = array_diff($notInFieldSets, $fields);

            $fields = array_intersect($fields, $builder->fields);
            if ($fields) {
                $builder->fieldSet2fields[$fieldSet] = $fields;
            } else {
                unset($builder->fieldSet2fields[$fieldSet]);
            }
        }

        echo $builder->fields2string($notInFieldSets, $form, $model);
        foreach ($builder->fieldSet2fields as $fieldSet => $fields) {
            echo Html::beginTag('fieldset');
            $legend = $builder->getFieldSetLegend($fieldSet);
            echo Html::tag('legend', $legend . $builder->extraControlsByPlace("fieldSet/{$fieldSet}"));
            echo $builder->fields2string($fields, $form, $model);
            echo Html::endTag('fieldset');
        }
    } else {
        echo $builder->fields2string($builder->fields, $form, $model);
    }

    echo $this->render('include/bottom-buttons', compact(['builder']));
    ActiveForm::end();
    ?>
</div>
