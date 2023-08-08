<?php
namespace Crud\grid\dragable;


/**
 * @XXX
 *
 */
class MatrixGridDragable extends \Crud\grid\Dragable
{
    public function attach(&$grid)
    {
        $grid->options['data-is-grid'] = 1;

        $this->selector = "[data-role = {$grid->bodyAttrs['data-role']}]";

        parent::attach($grid);
    }
}
