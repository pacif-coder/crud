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

        if (is_string($this->cell)) {
            $this->cell = ['class' => $this->cell];
        } elseif (is_array($this->cell) && !isset($this->cell['class'])) {
            $this->cell['class'] = Cell::class;
        }

        $this->registerAsset();
    }

    /**
     * Needed - call not trait registerJs
     */
    protected function registerJs()
    {
        parent::registerJs();
    }
}