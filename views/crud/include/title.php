<?php
use yii\helpers\Html;
?>
<div class="main-title">
    <?php
        $buttons = $this->render('title/right-buttons', compact(['builder']));
        if ($buttons) {
            echo Html::tag('div', $buttons, ['class' => 'buttons-right']);
        }

        echo $this->render('title/h1', compact(['builder']));
    ?>
</div>

<?php
    echo $this->render('title/flashes', compact(['builder']));