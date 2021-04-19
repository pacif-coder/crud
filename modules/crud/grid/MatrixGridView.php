<?php
namespace app\modules\crud\grid;

use app\modules\crud\grid\cell\Cell;

/**
 *
 */
class MatrixGridView extends \ThumbOnDemand\widgets\MatrixGridView
{
    use GridViewTrait;

    public $layout = "{toolbar}\r\n{items}\r\n{summary}\r\n{pager}";

    public $cell = [
        'class' => Cell::class,
        'tools' => '{checkbox}',
    ];

    /**
     * Runs the widget.
     */
    public function init()
    {
        parent::init();

        $this->registerAsset();
    }

    protected function registerJs()
    {
        parent::registerJs();
    }
}