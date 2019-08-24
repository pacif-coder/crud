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
    <h1 class="main-title"><?= Html::encode($this->title) ?></h1>

    <?php
    $form = ActiveForm::begin([
        'layout' => 'horizontal',
    ]);
    ?>

    <?= $form->errorSummary($model) ?>

    <?php
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

        foreach ($notInFieldSets as $field) {
            include 'include/controls.php';
        }

        foreach ($builder->fieldSet2fields as $fieldSet => $fields) {
            echo Html::beginTag('fieldset');
            echo Html::tag('legend', $fieldSet);
            foreach ($fields as $field) {
                include 'include/controls.php';
            }
            echo Html::endTag('fieldset');
        }
    } else {
        foreach ($builder->fields as $field) {
            include 'include/controls.php';
        }
    }
    ?>
    <hr>
    <div class="form-group">
        <div class="col-xs-12 bottom-buttons">
            <?php
                echo Html::submitButton(Html::icon('ok') . ' Сохранить', ['class' => 'btn  btn-success']);
                echo Html::a('Отменить', $this->context->getBackUrl(), ['class' => 'btn  btn-info']);
            ?>
        </div>
    </div>
<?php ActiveForm::end(); ?>
</div>