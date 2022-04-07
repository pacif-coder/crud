<?php
/* @var $this yii\web\View */
/* @var $builder app\modules\crud\builder\GridBuilder */
?>

<div>
    <?php
        echo $this->render('include/title', compact(['builder']));

        $grid = $builder->gridClass::begin($builder->getOptions());
        $builder->gridClass::end();
    ?>
</div>