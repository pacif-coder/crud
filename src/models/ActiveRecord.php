<?php
namespace Crud\models;

use Crud\helpers\Lang;

/**
 *
 *
 */
class ActiveRecord extends \yii\db\ActiveRecord
{
    protected static $extendAttrsLabel = [];

    public function attributeLabels()
    {
        $list = [];
        $category = static::getMessageCategory();
        $attrs = array_merge($this->attributes(), static::$extendAttrsLabel);
        foreach ($attrs as $attr) {
            $list[$attr] = Lang::t($category, $attr);
        }

        return $list;
    }

    public static function getMessageCategory()
    {
        $classes = Lang::regClassPref(static::class, 'models');
        return Lang::classes2messagesCategory($classes);
    }
}