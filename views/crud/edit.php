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

    $formClass = $builder->getFormClass();
    $form = $formClass::begin($builder->getFormConfig());

    echo $this->render('include/fields', compact(['form', 'builder', 'model']));

    echo $this->render('include/bottom-buttons', compact(['builder']));
    ActiveForm::end();
    ?>
</div>