<?php
namespace app\modules\crud\builder;

use yii\db\ActiveRecord;
use yii\validators\BooleanValidator;
use yii\validators\FileValidator;
use yii\validators\ExistValidator;

/**
 * XXX
 *
 */
class Base {
    public $enumFields;
    public $enumOptions;
    public $enumGetOptionsMethod;

    public $uptake = true;
    public $nameAttr = null;

    public $phoneAttrs = ['phone', 'tel'];
    public $emailAttrs = ['email'];
    public $nameAttrs = ['name', 'title', 'fio', 'id'];

    protected $dbColumns = [];

    public function controller2this($controller) {
        $params1 = array_keys(get_object_vars($controller));
        $params2 = array_keys(get_object_vars($this));
        foreach (array_intersect($params1, $params2) as $param) {
            $this->{$param} = $controller->{$param};
        }
    }

    protected function uptakeNameAttr($attr) {
        if (!$this->uptake || $this->nameAttr || false === $this->nameAttr) {
            return;
        }

        if (in_array($attr, $this->nameAttrs)) {
            $this->nameAttr = $attr;
        }
    }

    protected function getControlTypeByDBColumn($attr) {
        if (!isset($this->dbColumns[$attr])) {
            return;
        }

        $column = $this->dbColumns[$attr];

        /**@var $column \yii\db\ColumnSchema  **/
        if ('text' == $column->dbType) {
            return 'textarea';
        }

        if ('datetime' == $column->dbType) {
            return 'datetime';
        }
    }
/*
  +targetClass: "app\modules\admin\models\City"
  +targetAttribute: array:1 [▶]
 */
    protected function getControlTypeByValidator($model, $attr) {
        foreach ($model->getActiveValidators($attr) as $validator) {
            if ($validator instanceof ExistValidator) {

                $this->initEnumControlByExistValidator($validator, $attr);

                return 'select';
            }

            if ($validator instanceof BooleanValidator) {
                return 'boolean';
            }

            if ($validator instanceof FileValidator) {
                return 'file';
            }
        }
    }

    protected function initEnumControlByExistValidator($validator, $attr) {
        if (in_array($attr, $this->enumFields)) {
            return;
        }

        $this->enumFields[] = $attr;
        if (isset($this->enumOptions[$attr])) {
            return;
        }

        /* @var $validator ExistValidator */
        $options = [];
        $targetModelClass = $validator->targetClass;
        $targetModelAttr = $validator->targetAttribute[$attr];

        foreach ($targetModelClass::find()->all() as $targetModel) {
            $options[$targetModel->{$targetModelAttr}] = $targetModel->name;
        }

        $this->enumOptions[$attr] = $options;
    }
}