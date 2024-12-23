<?php
namespace Crud\grid\column\tree_node;

use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

use Crud\controls\Base;
use Crud\helpers\ChildeClass;
use Crud\models\ClassType;

/**
 *
 */
class ActionLinkWithTypeColumn extends \Crud\grid\column\ActionLinkColumn
{
    protected $indexActions = [
        'index',
    ];

    protected function getUrlParams($action, $model, $key, $index)
    {
        $params = parent::getUrlParams($action, $model, $key, $index);

        if (!($this->grid->dataProvider instanceof ActiveDataProvider)) {
            return $params;
        }

        if (!($this->grid->dataProvider->query instanceof ActiveQuery)) {
            return $params;
        }

        /* @var $this->grid->dataProvider->query ActiveQuery */
        $class = $this->grid->dataProvider->query->modelClass;

        if (in_array($action, $this->indexActions)) {
            $modelData = $model;
            foreach ($this->grid->renamedLink2ModelAttr as $renameAttr => $attr) {
                $modelData[$renameAttr] = $modelData[$attr];
                unset($modelData[$attr]);
            }

            $class = ChildeClass::getChildeClass($class, $modelData);
        }
        $params[Base::TYPE_PARAM] = ClassType::getTypeByClass($class);

        return $params;
    }
}