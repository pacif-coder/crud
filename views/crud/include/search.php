<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\MaskedInput;

/* @var $this yii\web\View */
/* @var $model app\models\subscribe\Search */
/* @var $form yii\widgets\ActiveForm */
?>
<div class="subscribe-search m-b-20 m-t-20">
    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'class' => 'form-inline',
        ],
    ]);?>

    <?= $form->field($model, 'account') ?>

    <?= $form->field($model, 'phone')->widget(MaskedInput::className(), ['mask' => '7 (999) 999 99 99']) ?>

    <?= $form->field($model, 'email') ?>

    <?= Html::submitButton(Yii::t('app/subscribe', 'Search'), ['class' => 'btn btn-info']) ?>

    <?php ActiveForm::end(); ?>
</div>