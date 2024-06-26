<?php
namespace Crud\behaviors;

use Yii;
use yii\db\ActiveRecord;
use yii\base\ModelEvent;

use Crud\models\ModelWithOrderInterface;
use Crud\models\ModelWithParentInterface;

/**
 * Behavior initializes the value of the sort attribute, putting
 * the object at the end of the list
 */
class SortInit extends \yii\base\Behavior
{
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT  => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_UPDATE  => 'beforeSave',
        ];
    }

    public function beforeSave(ModelEvent $event)
    {
        if (!$event->isValid) {
            return;
        }

        if (!is_a($this->owner, ModelWithOrderInterface::class)) {
            return;
        }

        $class = get_class($this->owner);
        $sortAttr = $class::ORDER_ATTR;
        if (is_array($sortAttr)) {
            $sortAttr = key($sortAttr);
        }

        if (!$sortAttr) {
            return;
        }

        if ($this->owner->{$sortAttr}) {
            return;
        }

        $query = $class::find();
        if (is_a($this->owner, ModelWithParentInterface::class)) {
            $parentAttr = $class::PARENT_MODEL_ATTR;
            $where = [$parentAttr => $this->owner->{$parentAttr}];
            $query->where($where);
        }

        $this->owner->{$sortAttr} = $query->max($sortAttr) + 1;
    }
}