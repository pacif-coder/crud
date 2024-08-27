<?php
namespace Crud\models;

use Yii;

use Crud\helpers\Lang;

/**
 *
 *
 */
class ActiveRecord extends \yii\db\ActiveRecord
{
    protected static $class2labels = [];

    protected static $extendAttrsLabel = [];

    /**
     *
     */
    // protected static $skipMessageCategory = true;

    public function attributeLabels()
    {
        if (isset(self::$class2labels[static::class])) {
            return self::$class2labels[static::class];
        }

        $list = [];
        $category = static::getMessageCategory();
        $extendAttrsLabel = Lang::getExtendAttrsLabel(static::class);

        $attrs = array_merge($this->attributes(), $extendAttrsLabel);
        foreach ($attrs as $attr) {
            $list[$attr] = Lang::t($category, $attr);
        }

        return self::$class2labels[static::class] = $list;
    }

    public static function getMessageCategory()
    {
        return Lang::getParentCategorysByRel(static::class, 'models');
    }
}