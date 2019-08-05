<?php
/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $builder app\modules\crud\builder\Form */

// use Yii;
use yii\bootstrap\Html;
use yii\bootstrap\ActiveForm;
use yii\web\View;
?>
<div>
    <h1 class="main-title"><?= Html::encode($this->title) ?></h1>

    <?php
    $form = ActiveForm::begin([
        'layout' => 'horizontal',
    ]);
    ?>

    <?= $form->errorSummary($model) ?>

    <?php
    foreach ($builder->fields as $field) {
        $control = $form->field($model, $field);
        $fieldType = isset($builder->fieldTypes[$field])? $builder->fieldTypes[$field] : null;
        $options = isset($builder->fieldOptions[$field])? $builder->fieldOptions[$field] : [];

        switch ($fieldType) {
            case 'select':
                echo $control->dropDownList($builder->enumOptions[$field], $options);
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

            case 'email':
            default:
                echo $control->input('text', $options);
                break;
        }
    }
    ?>
    <hr>
    <div class="form-group">
        <div class="col-xs-12 text-right">
            <?= Html::submitButton(Html::icon('ok') . ' Сохранить', ['class' => 'btn  btn-success']) ?>
        </div>
    </div>
<?php ActiveForm::end(); ?>
</div>