<?php
namespace app\modules\crud\grid\toolbar;

use app\modules\crud\helpers\ClassI18N;

/**
 * @XXX
 *
 */
class Button extends \app\modules\crud\controls\Base
{
    public $grid;

    public $sizeClass = 'btn-xs';

    public function init() {
        if (!$this->messageCategory) {
            $this->messageCategory = ClassI18N::class2messagesPath('app\modules\crud\grid\toolbar\Button');
        }

        parent::init();
    }

    public function getAttrs() {
        $attrs = parent::getAttrs();

        if ($this->grid) {
            $attrs['data-target'] = $this->grid->options['id'];
        }

        return $attrs;
    }
}