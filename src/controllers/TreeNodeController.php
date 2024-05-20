<?php
namespace Crud\controllers;

use Yii;

use Crud\grid\column\tree_node\ActionLinkWithTypeColumn;
use Crud\helpers\ModelName;
use Crud\models\tree_node\ActiveRecord;
use Crud\models\tree_node\Folder;
use Crud\models\tree_node\Type;
use Crud\widgets\TreeMenu;

use Exception;

/**
 *
 */
class TreeNodeController extends CrudController
{
    protected $rootClass = Folder::class;

    public function init()
    {
        $this->globalUseClass[] = TreeMenu::class;

        parent::init();
    }

    public function fillModelClass()
    {
        $type = Yii::$app->request->get('type');
        $id = Yii::$app->request->get('id');

        if (!$id && !$type) {
            $this->modelClass = $this->rootClass;

            return;
        }

        if ($id && !$type) {
            throw new Exception('Type mast be definded');
        }

        $this->modelClass = Type::getClassByType($type);
    }

    protected function beforeGridBuild(\yii\base\Event $event)
    {
        /* @var $gridBuilder \Crud\builder\GridBuilder */
        $gridBuilder = $event->sender;

        $gridBuilder->editColumnClass = ActionLinkWithTypeColumn::class;

        $nameAttr = ModelName::getNameAttr($this->modelClass);
        $type = Type::getTypeByClass($this->modelClass);
        $action = Type::isFolderByType($type)? 'index' : 'update';

        if (!isset($gridBuilder->columnOptions[$nameAttr]['action'])) {
            $gridBuilder->columnOptions[$nameAttr]['action'] = $action;
        }

        if ('index' == $action) {
            $gridBuilder->columnOptions[$nameAttr]['secondAction'] = 'update';

            $gridBuilder->columnOptions[$nameAttr]['backUrl'] = false;

            $gridBuilder->columnOptions[$nameAttr]['secondActionBackUrl'] = true;
        }
    }

    protected function createBreadcrumbs()
    {
        $this->breadcrumbs += ['withType' => true];

        parent::createBreadcrumbs();
    }
}