<?php
/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $builder app\modules\crud\builder\FormBuilder */

use yii\widgets\DetailView;
?>
<div>
    <?php
        echo $this->render('include/title', compact(['builder']));
        echo DetailView::widget([
            'model' => $model,
        ]);

        echo $this->render('include/bottom-buttons', compact(['builder']));
    ?>
</div>