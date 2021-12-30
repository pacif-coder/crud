<?php
use yii\bootstrap\Html;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $builder app\modules\crud\builder\FormBuilder */

echo $form->errorSummary($model, ['showAllErrors' => true]);

echo $builder->formBody2string($form, $model);