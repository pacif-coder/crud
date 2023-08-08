<?php
namespace Crud\data;

use Yii;

use Crud\models\tree_node\Node;
use Crud\models\tree_node\Type;
use Crud\helpers\ClassI18N;
use Crud\helpers\Lang;
use Crud\helpers\ModelName;

use Exception;

class TreeNodeDelete extends \yii\base\BaseObject
{
    public $maxDeleteCount = 200;

    public $messages = [];

    public $errors = [];

    public $defMessageCategory;

    protected $count = 0;

    public function init()
    {
        parent::init();

        $this->defMessageCategory = ClassI18N::class2messagesPath(Node::class);
    }

    public function deleteBranchs($selection)
    {
        $this->count = 0;
        $this->messages = $this->errors = [];
        foreach ((array) $selection as $parent) {
            $this->_deleteBranch($parent);

            if ($this->count > $this->maxDeleteCount) {
                break;
            }
        }
    }

    protected function _deleteBranch($topParent)
    {
        if (!$topParent) {
            return;
        }

        $parent = $topParent;
        $class = Node::getClass();

        $isDeleted = true;
        while ($isDeleted && $this->count < $this->maxDeleteCount) {
            $isDeleted = false;

            // dig the node tree to the first folder without internal folders
            $needDig = true;
            $nodesListEmpty = false;
            while ($needDig && !$nodesListEmpty) {
                $this->count++;

                if (YII_DEBUG) {
                    Yii::debug("dig into TreeNode with id '{$parent}'");
                }

                /* @var $node Node */
                $query = $class::find()->where(['parent_id' => $parent])->orderBy('sort');

                $nodes = [];
                $needDig = false;
                foreach ($query->all() as $node) {
                    $nodes[] = $node;

                    // find the folder - dig inside it
                    if (Type::isFolderByType($node->type_id)) {
                        $needDig = true;
                        $parent = $node->id;
                        break;
                    }
                }

                $nodesListEmpty = $nodes? false : true;
            }

            // stop diging - find a folder without internal folders

            // delete nodes inside
            foreach ($nodes as $node) {
                $this->count++;

                if (!$this->_deleteModel($node)) {
                    break 2;
                }

                $isDeleted = true;
            }

            $parentNode = Node::factory($parent);
            if (!$parentNode) {
                continue;
            }

            $isDeleted = true;
            if (!$this->_deleteModel($parentNode)) {
                break;
            }

            if ($parent == $topParent) {
                break;
            }

            $parent = $parentNode->parent_id;
            if (null === $parent) {
                break;
            }

            if (YII_DEBUG) {
                Yii::debug("Up to TreeNode with id '{$parent}'");
            }
        }
    }

    protected function _deleteModel($model)
    {
        try {
            if (false === $model->delete()) {
                $this->errors[] = implode("\n", $model->getErrorSummary(true));
                return false;
            }
        } catch (Exception $exc) {
            $this->errors[] = $exc->getMessage();
            return false;
        }

        $message = "Object '{modelName}' has been successfully deleted";
        $params = ['modelName' => ModelName::getName($model)];
        $this->messages[] = Lang::t($this->defMessageCategory, $message, $params);

        if (YII_DEBUG) {
            Yii::debug("Delete TreeNode with id '{$model->id}'");
        }

        return true;
    }

}