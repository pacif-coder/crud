<?php
namespace Crud\data;

use yii\db\ActiveQuery;

use Crud\models\tree_node\Node;
use Crud\models\tree_node\Type;

class TreeNodeLoader extends \yii\base\BaseObject
{
    public $root;

    public $maxLevel;

    public $scenario;

    public $code2with = [];

    public $class2with;

    protected $nodes = [];

    protected $parent2id = [];

    protected $parents = [];

    public function load()
    {
        foreach ($this->code2with as $code => $with) {
            $type = Type::getTypeByCode($code);
            $class = Type::getClassByType($type);

            $this->class2with[$class] = $with;
        }

        $this->parents[] = $this->root;
        while ($this->parents) {
            $this->_load();
        }
    }

    protected function _load()
    {
        if (!$this->parents) {
            return;
        }

        /* @var $query ActiveQuery */
        $parents = $withLinkedModel = [];
        $query = Node::getClass()::find()->orderBy('sort')->where(['parent_id' => $this->parents]);
        if ($this->maxLevel) {
            $query->andWhere(['<=', 'level', $this->maxLevel]);
        }

        foreach ($query->all() as $node) {
            /* @var $node Node */
            $id = $node->id;
            $type = $node->type_id;
            $parent = null === $node->parent_id? '' : $node->parent_id;

            $this->nodes[$id] = $node->toArray();
            $this->parent2id[$parent][] = $id;

            if (Type::isFolderByType($type)) {
                $parents[] = $id;
            }

            $class = Type::getClassByType($type, false);
            if ($class) {
                $withLinkedModel[$class][] = $node->id;
            } else {
                $this->nodes[$id] = (object) $this->nodes[$id];
            }
        }

        foreach ($withLinkedModel as $class => $ids) {
            $query = $class::find()->where([current($class::primaryKey()) => $ids]);
            if (isset($this->class2with[$class])) {
                $query->with($this->class2with[$class]);
            }

            foreach ($query->all() as $record) {
                $id = $record->id;

                if ($this->scenario) {
                    $record->setScenario($this->scenario);
                }

                $asArray = $record->toArray();
                if (isset($this->class2with[$class])) {
                    $with = $this->class2with[$class];
                    $asArray[$with] = (object) $record->{$with}->toArray();
                }

                $this->nodes[$id] = array_merge($this->nodes[$id], $asArray);
                $this->nodes[$id] = (object) $this->nodes[$id];
            }
        }

        $this->parents = $parents;
    }

    public function getNodesByParent($parent)
    {
        $nodes = [];
        foreach ($this->getIDsByParent($parent) as $id) {
            $nodes[$id] = $this->getNode($id);
        }

        return $nodes;
    }

    public function getIDsByParent($parent)
    {
        $parent = null === $parent? '' : $parent;
        return isset($this->parent2id[$parent])? $this->parent2id[$parent] : [];
    }

    public function getNode($id)
    {
        return $this->nodes[$id];
    }
}