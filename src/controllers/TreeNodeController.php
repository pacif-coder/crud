<?php
namespace Crud\controllers;

use Yii;
use yii\web\BadRequestHttpException;

use Crud\data\TreeNodeDelete;
use Crud\helpers\ClassI18N;
use Crud\models\tree_node\Node;
use Crud\models\tree_node\Type;
use Crud\models\tree_node\WithPathInterface;
use Crud\widgets\TreeMenu;

/**
 *
 */
class TreeNodeController extends CrudController
{
    public $hasTreeMenu = true;

    protected $createTypeCode;

    protected $treeMenuAttrs = [];

    public function init()
    {
        $this->modelClass = Node::getClass();

        parent::init();
    }

    protected function _actionEdit($isCreate)
    {
        $modelClass = null;

        if ($isCreate) {
            $typeID = $this->request->get('type');
            if (!$typeID) {
                throw new BadRequestHttpException(
                    Yii::t('yii', 'Missing required parameters: {params}', ['params' => 'type'])
                );
            }

            $modelClass = Type::getClassByType($typeID);
        } else {
            $node = $this->_findModel(Node::getClass(), $this->getModelID());
            $modelClass = Type::getClassByType($node->type_id);
        }

        if ($modelClass) {
            $this->modelClass = $modelClass;
            $this->messageCategory = ClassI18N::class2messagesPath($modelClass);
        }

        return parent::_actionEdit($isCreate);
    }

    public function actionDelete()
    {
        if (!is_a($this->modelClass, Node::getClass(), true)) {
            return parent::actionDelete();
        }

        $selection = $this->request->post('selection', []);
        $delete = new TreeNodeDelete();
        $delete->deleteBranchs($selection);

        if ($delete->errors) {
            $this->addFlashMessage('danger', implode("\n", $delete->errors));
        } else {
            $this->addFlashMessage('info', implode("\n", $delete->messages));
        }

        return $this->goBack();
    }

    protected function createEditBreadcrumbs($isCreate, $model)
    {
        $node = Node::factory();
        $node->id = $model->id;
        $node->parent_id = $model->parent_id;

        return parent::createEditBreadcrumbs($isCreate, $node);
    }

    protected function createModel()
    {
        $model = parent::createModel();
        if (is_a($model, Node::class)) {
            $model->type_id = $this->request->get('type');
        }

        return $model;
    }

    protected function beforeGridBuild(\yii\base\Event $event)
    {
        /* @var $gridBuilder \Crud\builder\GridBuilder */
        $gridBuilder = $event->sender;

        $gridBuilder->columnOptions['name']['action'] = [self::class, 'getNodeAction'];

        $typeCode = $this->getCreateTypeCode();
        if ($typeCode) {
            $gridBuilder->extraControlOptions['create']['params'] = [
                'type' => Type::getTypeByCode($typeCode),
            ];
        } else {
            $gridBuilder->gridExtraControls = [];
        }
    }

    protected function afterGridBuild(\yii\base\Event $event)
    {
        /* @var $gridBuilder \Crud\builder\GridBuilder */
        $gridBuilder = $event->sender;

        if (null === $gridBuilder->parentModelID) {
            return;
        }

        $node = $this->findModel();
        $class = Type::getClassByType($node->type_id);
        if ($class && !is_a($class, WithPathInterface::class, true)) {
            unset($gridBuilder->columns['path']);
        }
    }

    protected function getCreateTypeCode()
    {
    }

    public function getTreeMenu()
    {
        $treeMenuAttrs = $this->treeMenuAttrs;
        $treeMenuAttrs['currentId'] = $this->getModelID();

        $menu = new TreeMenu($treeMenuAttrs);
        return $menu->html();
    }

    public static function getNodeAction($node, $key, $index, $colunn)
    {
        if (!isset($colunn->grid->renamedLink2ModelAttr['type_id'])) {
            return 'update';
        }

        $typeRenameAttr = $colunn->grid->renamedLink2ModelAttr['type_id'];
        if (is_object($node)) {
            $type = $node->{$typeRenameAttr};
        } else {
            $type = $node[$typeRenameAttr];
        }

        return Type::isFolderByType($type)? 'index' : 'update';
    }
}