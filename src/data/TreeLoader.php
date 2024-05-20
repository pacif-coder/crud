<?php
namespace Crud\data;

use Yii;
use yii\db\ActiveQuery;

use Crud\helpers\ChildeClass;
use Crud\helpers\ParentModel;
use Crud\models\tree_node\Folder;
use Crud\models\tree_node\Type;
use Crud\models\ModelWithOrderInterface;
use Crud\models\ModelWithParentInterface;
use Crud\models\tree_node\ActiveRecord;

class TreeLoader extends \yii\base\BaseObject
{
    public $rootClass = Folder::class;

    public $loadingClass2parentID = [];

    public $maxLevel;

    public $scenario;

    public $code2with = [];

    public $class2with = [];

    public $class2limit = [];

    /* @var ActiveQuery */
    protected $query;

    protected $nodes = [];

    protected $parent2id = [];

    protected $noParentId = [];

    protected $nextID2realID = [];

    protected $nextID2class = [];

    protected $classParentID2nextID = [];

    protected $childeClass2parentIDMap = [];

    protected static $nextID = 1;

    protected $level = 1;

    public function loadChilde(ActiveRecord $model)
    {
        $class = get_class($model);
        $idAttr = $this->getIDAttr($class);

        $nextID = self::$nextID++;
        $realID = $model->{$idAttr};
        $this->nextID2realID[$nextID] = $realID;

        $childeClass = ChildeClass::getChildeClass($model);
        $this->childeClass2parentIDMap[$childeClass][$realID] = $nextID;

        $this->loadingClass2parentID = [
            $childeClass => $realID,
        ];
        $this->load();

        return $nextID;
    }

    public function load()
    {
        $this->nextID2realID = $this->nextID2class = [];
        $this->parent2id = $this->noParentId = $this->classParentID2nextID = [];

        if (!$this->loadingClass2parentID && $this->rootClass) {
            $this->loadingClass2parentID[$this->rootClass] = null;
        }

        while ($this->_load()) {}
    }

    protected function _load()
    {
        if (!$this->loadingClass2parentID) {
            return;
        }

        if (null !== $this->maxLevel && $this->level > $this->maxLevel) {
            return;
        }

        $loadingClass2parentID = $this->loadingClass2parentID;
        $this->loadingClass2parentID = [];
        foreach ($loadingClass2parentID as $class => $parentID) {
            /* @var $query ActiveQuery */
            $this->query = $class::find();

            $this->this2query($class, $parentID);

            $idAttr = $this->getIDAttr($class);
            foreach ($this->query->all() as $model) {
                /* @var $model ActiveRecord */
                $nextID = self::$nextID++;
                $realID = $model->{$idAttr};
                $this->nextID2realID[$nextID] = $realID;
                $this->nextID2class[$nextID] = $class;

                $parentAttr = ParentModel::getParentModelAttr($class, false);
                if ($parentAttr) {
                    $parentID = $model->{$parentAttr};
                    $this->classParentID2nextID[$class][$parentID][$realID] = $nextID;
                }

                $obj = $this->model2array($model, $class);

                $this->addChilde($model, $class, $nextID);

                $obj->id = $nextID;
                $this->convertParent($obj, $class, $nextID);
                $this->nodes[$nextID] = $obj;
            }
        }
        $this->level++;

        return true;
    }

    protected function this2query($class, $parentID)
    {
        if (is_a($class, ModelWithOrderInterface::class, true)) {
            $orderAttrs = $class::ORDER_ATTR;
            if (!is_array($orderAttrs)) {
                $orderAttrs = [$orderAttrs => SORT_ASC];
            }

            $this->query->orderBy($orderAttrs);
        }

        $parentAttr = ParentModel::getParentModelAttr($class, false);
        if ($parentID && $parentAttr) {
            $this->query->andWhere([$parentAttr => $parentID]);
        }

        if (isset($this->class2with[$class])) {
            $this->query->with($this->class2with[$class]);
        }

        if (!isset($this->class2limit[$class])) {
            return;
        }

        if (null === $parentID || !is_array($parentID)) {
            return;
        }

        if (count($parentID) > 1) {
            throw new Exception("Multiple parent limits for a class '{$class}' are not supported");
        }

        $this->query->limit($this->class2limit[$class]);
    }

    protected function addChilde($model, $class, $nextID)
    {
        $type = Type::getTypeByClass($class);
        if (!Type::isFolderByType($type)) {
            return;
        }

        $childeClass = ChildeClass::getChildeClass($model);
        if (!$childeClass) {
            return;
        }

        $realID = $this->nextID2realID[$nextID];
        $this->loadingClass2parentID[$childeClass][] = $realID;
        $this->childeClass2parentIDMap[$childeClass][$realID] = $nextID;
    }

    protected function model2array($model, $class)
    {
        if ($this->scenario) {
            $model->setScenario($this->scenario);
        }

        $obj = (object) $model->toArray();
        if (!isset($this->class2with[$class])) {
            return $obj;
        }

        $with = $this->class2with[$class];
        if (isset($model->{$with}) && $model->{$with}) {
            $obj->{$with} = (object) $model->{$with}->toArray();
        } else {
            $obj->{$with} = null;
        }

        return $obj;
    }

    protected function convertParent(&$obj, $class, $nextID)
    {
        if (!is_a($class, ModelWithParentInterface::class, true)) {
            $this->noParentId[] = $nextID;
            return;
        }

        $parentAttr = $class::PARENT_MODEL_ATTR;
        $parentID = $obj->{$parentAttr};
        if (!isset($this->childeClass2parentIDMap[$class][$parentID])) {
            return;
        }

        $reformatParentID = $this->childeClass2parentIDMap[$class][$parentID];
        $this->parent2id[$reformatParentID][] = $nextID;
        $obj->{$parentAttr} = $reformatParentID;
    }

    public function getNodesByParent($parent = null)
    {
        $nodes = [];
        foreach ($this->getIDsByParent($parent) as $id) {
            $nodes[$id] = $this->getNode($id);
        }

        return $nodes;
    }

    public function getIDsByModel($model)
    {
        if (null === $parent) {
            return $this->noParentId;
        }

        return $this->parent2id[$parent]?? [];
    }

    public function getIDsByParent($parent)
    {
        if (null === $parent) {
            return $this->noParentId;
        }

        return $this->parent2id[$parent]?? [];
    }

    public function getNode($id)
    {
        return $this->nodes[$id];
    }

    public function getClassByID($id)
    {
        return $this->nextID2class[$id]?? null;
    }

    public function getNodes()
    {
        return $this->nodes;
    }

    public function getRealID($id)
    {
        return $this->nextID2realID[$id]?? null;
    }

    protected function getIDAttr($class)
    {
        $keys = $class::primaryKey();
        if (count($keys) > 1) {
            throw new Exception('Not support several keys');
        }
        return current($keys);
    }
}