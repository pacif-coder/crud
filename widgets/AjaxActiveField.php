<?php
namespace app\modules\crud\widgets;

use ReflectionClass, ReflectionProperty;

/**
 *
 *
 */
class AjaxActiveField extends \yii\bootstrap\ActiveField
{
    public function init()
    {
        parent::init();

        $class = new ReflectionClass($this);
        $names = [];
        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $name = $property->getName();
            if (!preg_match('/Template$/', $name)) {
                continue;
            }

            $template = $this->{$name};

            $matches = [];
            preg_match_all('/\{\w+\}/', $template, $matches);
            if (!$matches) {
                continue;
            }

            foreach ($matches[0] as $match) {
                if ('{input}' != $match) {
                    $this->parts[$match] = '';
                }
            }
        }

        return $names;
    }

    public function begin()
    {
        return '';
    }

    public function end()
    {
        return '';
    }
}
