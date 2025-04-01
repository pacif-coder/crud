<?php
namespace Crud\rbac;

use yii\db\Query;
use yii\rbac\Item;

/**
 *
 */
class DbManager extends \yii\rbac\DbManager
{
    /**
     * {@inheritdoc}
     * The roles returned by this method include the roles assigned via [[$defaultRoles]].
     */
    public function getRolesByUsers($userIDs)
    {
        if (empty($userIDs)) {
            return [];
        }

        foreach ($userIDs as $i => $userID) {
            $userIDs[$i] = (string) $userID;
        }

        $query = (new Query())->select('{{b}}.*, {{a}}.[[user_id]]')
            ->from(['a' => $this->assignmentTable, 'b' => $this->itemTable])
            ->where('{{a}}.[[item_name]]={{b}}.[[name]]')
            ->andWhere(['a.user_id' => $userIDs])
            ->andWhere(['b.type' => Item::TYPE_ROLE]);

        $roles = [];
        $defRoles = $this->getDefaultRoleInstances();
        foreach ($query->all($this->db) as $userRole) {
            $userID = $userRole['user_id'];
            if (!isset($roles[$userID])) {
                $roles[$userID] = $defRoles;
            }

            $name = $userRole['name'];
            $roles[$userID][$name] = $this->populateItem($userRole);
        }

        return $roles;
    }
}
