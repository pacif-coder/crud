<?php
namespace Crud\grid\data;

use Yii;

use Crud\rbac\DbManager;

use Exception;

/**
 *
 *
 */
class UserWithRolesProvider extends \yii\data\ActiveDataProvider
{
    public $rolesAttr = 'roles';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        $auth = Yii::$app->authManager;
        if (!is_a($auth, DbManager::class)) {
            throw new Exception('"authManager" mast be a "' . DbManager::class . '" class');
        }
    }

    protected function prepareModels()
    {
        $models = parent::prepareModels();

        $userID2index = [];
        foreach ($models as $index => $model) {
            $id = $model['id'];
            $userID2index[$id] = $index;

            $models[$index] = $model;
        }

        $auth = Yii::$app->authManager;
        $userID2roles = $auth->getRolesByUsers(array_keys($userID2index));
        foreach ($userID2index as $userID => $index) {
            $model = $models[$index];

            if (isset($userID2roles[$userID])) {
                $model[$this->rolesAttr] = array_keys($userID2roles[$userID]);
            } else {
                $model[$this->rolesAttr] = [];
            }

            $models[$index] = $model;
        }

        return $models;
    }
}