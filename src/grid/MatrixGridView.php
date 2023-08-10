<?php
namespace Crud\grid;

use Crud\grid\cell\Cell;

use Crud\grid\dragable\MatrixGridDragable;

/**
 *
 */
class MatrixGridView extends \ThumbOnDemand\widgets\MatrixGridView
{
    use GridViewTrait;

    public $layout = "{toolbar}\r\n{items}\r\n{summary}\r\n{pager}";

    public $cell = [
        'class' => Cell::class,
        'tools' => ['checkbox'],
    ];

    public $dragable;
    public $dragableСlass = MatrixGridDragable::class;
    public $dragableOptions = [];

    public $renamedLink2ModelAttr = [];

    public function run()
    {
        $this->registerDragable();

        parent::run();
    }

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