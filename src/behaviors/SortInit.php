<?php
namespace Crud\behaviors;

use Yii;
use yii\db\ActiveRecord;
use yii\base\ModelEvent;

use Crud\helpers\ParentModel;
use Crud\models\ModelWithOrderInterface;

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

    /**
     * If the object does not have the 'sort' attribute value defined or if we
     * have moved the object to another parent - put the object at the end
     * of the list
     */
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

        $parentAttr = ParentModel::getParentModelAttr($this->owner);
        if ($this->owner->{$sortAttr} && !$this->isParentChange($parentAttr)) {
            return;
        }

        $query = $class::find();
        if ($parentAttr) {
            $where = [$parentAttr => $this->owner->{$parentAttr}];
            $query->where($where);
        }

        $this->owner->{$sortAttr} = $query->max($sortAttr) + 1;
    }

    /**
     * Is the object changed parent object?
     *
     * @param type $parentAttr
     * @return bool
     */
    protected function isParentChange($parentAttr)
    {
        if (!$parentAttr) {
            return false;
        }

        if (!is_a($this->owner, ActiveRecord::class)) {
            return false;
        }

        return $this->owner->getOldAttribute($parentAttr) != $this->owner->getAttribute($parentAttr);
    }
}