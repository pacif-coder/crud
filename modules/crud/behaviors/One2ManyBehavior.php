<?php
namespace app\modules\crud\behaviors;

use yii\db\ActiveRecord;

use app\modules\crud\models\One2Many;

/**
 *
 */
class One2ManyBehavior extends \yii\base\Behavior
{
    public $attr;

    protected $values = [];

    public function afterSave()
    {
        foreach ((array) $this->attr as $attr) {
            $method = "get{$attr}";
            One2Many::changeLink($this->owner->{$method}(), $this->values[$attr]);
        }
    }

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
        ];
    }

    public function __set($name, $value)
    {
        if (in_array($name, (array) $this->attr)) {
            $this->values[$name] = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    public function canSetProperty($name, $checkVars = true): bool
    {
        return in_array($name, (array) $this->attr) || parent::canSetProperty($name, $checkVars);
    }
}