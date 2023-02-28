<?php
/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $builder app\modules\crud\builder\FormBuilder */
?>
<div>
    <?php
        echo $this->render('include/loader');

        $formClass = $builder->getFormClass();
        $form = $formClass::begin($builder->getFormConfig());

        echo $this->render('include/title', compact(['builder']));

        echo $this->render('include/fields', compact(['form', 'builder', 'model']));

        echo $this->render('include/bottom-buttons', compact(['builder']));
        $formClass::end();
    ?>
</div>