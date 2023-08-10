<?php
namespace Crud\grid\toolbar;

use Crud\helpers\ClassI18N;

/**
 * @XXX
 *
 */
class Button extends \Crud\controls\Base
{
    public $grid;

    public $size = 'small';

    public $columnData2this;

    public function init()
    {
        if (!$this->defMessageCategory) {
            $this->defMessageCategory = ClassI18N::class2messagesPath(self::class);
        }

        parent::init();
    }

    public function getAttrs()
    {
        $attrs = parent::getAttrs();

        if ($this->grid) {
            $attrs['data-target'] = $this->grid->options['id'];
        }

        return $attrs;
    }

    public function columnData2this($model, $key, $index)
    {
        if ($this->columnData2this && is_callable($this->columnData2this)) {
            call_user_func($this->columnData2this, $this, $model, $key, $index);
            return;
        }

        foreach (get_object_vars($this) as $param => $value) {
            if (is_string($value) || !is_callable($value)) {
                continue;
            }

            $this->{$param} = call_user_func($value, $model, $key, $index);
        }
    }
}