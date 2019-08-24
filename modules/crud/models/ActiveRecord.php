<?php
namespace app\modules\crud\models;

use Yii;
use app\modules\crud\helpers\ClassI18N;

/**
 * Description of ActiveRecord
 *
 */
class ActiveRecord extends \yii\db\ActiveRecord {
    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        $file = ClassI18N::class2messagesPath(static::class);

        $list = [];
        foreach ($this->attributes() as $attribute) {
            $list[$attribute] = Yii::t($file, $attribute);
        }

        return $list;
    }
}