<?php
namespace Crud\models;

use Yii;
use Crud\helpers\ClassI18N;
use Crud\helpers\Lang;

/**
 *
 *
 */
class ActiveRecord extends \yii\db\ActiveRecord
{
    public function attributeLabels()
    {
        $list = [];
        $category = ClassI18N::class2messagesPath(static::class);
        foreach ($this->attributes() as $attribute) {
            $list[$attribute] = Lang::t($category, $attribute);
        }

        return $list;
    }
}