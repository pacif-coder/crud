<?php
namespace Crud\migrations;

use Yii;

/**
 *
 */
class RolesCreate extends \yii\db\Migration
{
    protected $role2description = [];

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        foreach ($this->role2description as $role => $description) {
            $roleObject = $auth->createRole($role);
            $roleObject->description = $description;
            $auth->add($roleObject);

            unset($roleObject);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        foreach (array_keys($this->role2description) as $role) {
            $roleObject = $auth->createRole($role);
            $auth->remove($roleObject);

            unset($roleObject);
        }

        return true;
    }
}
