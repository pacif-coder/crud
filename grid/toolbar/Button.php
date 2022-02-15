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
        foreach (get_object_vars($this) as $param => $value) {
            if (is_string($value) || !is_callable($value)) {
                continue;
            }

            $this->{$param} = call_user_func($value, $model, $key, $index);
        }
    }
}