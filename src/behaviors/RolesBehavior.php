<?php
namespace Crud\behaviors;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

// use Exception;

/**
 *
 */
class RolesBehavior extends \yii\base\Behavior
{
    public $attr;

    protected $_roles;

    protected $_oldRoles;

    protected static $allRoles;

    protected static $role2user;

    protected static $user2role;

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
        ];
    }

    public function afterSave()
    {
        $this->_saveRoles();
    }

    public function __set($name, $new)
    {
        if ($name != $this->attr) {
            return parent::__set($name, $new);
        }

        $this->_rolesLoad();
        $this->_roles = '' === $new? [] : $new;
    }

    public function __get($name)
    {
        return $name == $this->attr? $this->_rolesLoad() : parent::__get($name);
    }

    protected function _rolesLoad()
    {
        if (null !== $this->_roles) {
            return $this->_roles;
        }

        foreach (array_keys($this->_getAllRoles()) as $role) {
            self::fillRole($role);
        }

        $roles = self::$user2role[$this->getOwnerId()]?? [];
        return $this->_oldRoles = $this->_roles = $roles;
    }

    protected function _saveRoles()
    {
        if (!$this->_oldRoles && !$this->_roles) {
            return;
        }

        if ($this->_oldRoles == $this->_roles) {
            return;
        }

        $auth = Yii::$app->authManager;

        // remove user role
        $del = array_diff($this->_oldRoles, $this->_roles);
        foreach ($del as $role) {
            $userRole = $auth->getRole($role);
            $auth->revoke($userRole, $this->getOwnerId());
        }

        // add user role
        $add = array_diff($this->_roles, $this->_oldRoles);
        foreach ($add as $role) {
            $userRole = $auth->getRole($role);
            $auth->assign($userRole, $this->getOwnerId());
        }

        $this->_oldRoles = $this->_roles;

        //  drop cash
        self::$role2user = self::$user2role = null;
    }

    protected function _getAllRoles()
    {
        if (null !== self::$allRoles) {
            return self::$allRoles;
        }

        $roles = Yii::$app->authManager->getRoles();
        self::$allRoles = ArrayHelper::map($roles, 'name', 'description');
        return self::$allRoles;
    }

    protected function _check($attr, $params = null)
    {
        $notExist = array_diff($this->owner->{$attr}, array_keys($this->_getAllRoles()));
        if (!$notExist) {
            return;
        }

        $notExist = implode("', '", $notExist);
        $this->owner->addError($attr, "No exist roles with code '{$notExist}'");
    }

    public function canSetProperty($name, $checkVars = true): bool
    {
        return $name == $this->attr || parent::canSetProperty($name, $checkVars);
    }

    public function canGetProperty($name, $checkVars = true): bool
    {
        return $name == $this->attr || parent::canGetProperty($name, $checkVars);
    }

    public function hasMethod($name)
    {
        $name = strtolower($name);
        $lattr = strtolower($this->attr);
        return "getall{$lattr}" == $name || "check{$lattr}" == $name || parent::hasMethod($name);
    }

    public function __call($name, $params)
    {
        $name = strtolower($name);
        $lattr = strtolower($this->attr);
        if ("getall{$lattr}" == $name) {
            return $this->_getAllRoles();
        } elseif ("check{$lattr}" == $name) {
            return call_user_func_array([$this, '_check'], $params);
        }

        return parent::__call($name, $params);
    }

    protected function getOwnerId()
    {
        return $this->owner->id;
    }

    public static function getUserIDsByRole($role)
    {
        if (null === self::$role2user || !isset(self::$role2user[$role])) {
            self::fillRole($role);
        }

        return self::$role2user[$role];
    }

    protected static function fillRole($role)
    {
        if (null !== self::$role2user && isset(self::$role2user[$role])) {
            return;
        }

        if (null === self::$role2user) {
            self::$role2user = self::$user2role = [];
        }

        $auth = Yii::$app->authManager;

        self::$role2user[$role] = [];
        foreach ($auth->getUserIdsByRole($role) as $user) {
            self::$role2user[$role][] = $user;
            self::$user2role[$user][] = $role;
        }
    }
}