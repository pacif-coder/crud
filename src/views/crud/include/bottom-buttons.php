<?php
/* @var $this yii\web\View */
/* @var $builder app\modules\crud\builder\FormBuilder */

$isSingleCell = !($builder->isExtraControlExist('bottom/right') && $builder->isExtraControlExist('bottom/left'));
$class = $isSingleCell? 'col-xs-12' : 'col-xs-6';
?>
<hr>
<div class="form-group">
    <?php if ($builder->isExtraControlExist('bottom/left')): ?>
        <div class="<?=$class?> bottom-buttons text-left">
            <?= $builder->extraControlsByPlace('bottom/left') ?>
        </div>
    <?php endif; ?>

    <?php if ($builder->isExtraControlExist('bottom/right')): ?>
        <div class="<?=$class?> bottom-buttons text-right">
            <?= $builder->extraControlsByPlace('bottom/right') ?>
        </div>
    <?php endif; ?>
</div>