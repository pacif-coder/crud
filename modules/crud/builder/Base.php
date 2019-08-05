<?php
namespace app\modules\crud\builder;

use yii\db\ActiveRecord;
use yii\validators\BooleanValidator;
use yii\validators\FileValidator;
use yii\validators\ExistValidator;
use yii\validators\EmailValidator;

/**
 * XXX
 *
 */
class Base {
    public $fields;

    public $enumFields;
    public $enumOptions;
    public $enumGetOptionsMethod;

    public $uptake = true;
    public $nameAttr = null;

    public $phoneAttrs = ['phone', 'tel'];
    public $emailAttrs = ['email'];
    public $nameAttrs = ['name', 'title', 'fio', 'id'];

    protected $dbColumns = [];
    protected $validatorts;

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

        if ('date' == $column->dbType) {
            return 'date';
        }
    }

    protected function initValidators($model) {
        if (null !== $this->validatorts) {
            return;
        }

        $this->validatorts = [];
        foreach ($model->getActiveValidators() as $validator) {
            /*@var $validator \yii\validators\Validator */
            foreach ($validator->getAttributeNames() as $attr) {
                $this->validatorts[$attr][] = $validator;
            }
        }
    }

    protected function getControlTypeByValidator($model, $attr) {
        $this->initValidators($model);
        if (!isset($this->validatorts[$attr])) {
            return;
        }

        foreach ($this->validatorts[$attr] as $validator) {
            if ($validator instanceof ExistValidator) {
                return 'select';
            }

            if ($validator instanceof BooleanValidator) {
                return 'boolean';
            }

            if ($validator instanceof FileValidator) {
                return 'file';
            }

            if ($validator instanceof EmailValidator) {
                return 'email';
            }
        }
    }

    protected function initEnumOptionsByValidator($model, $attr) {
        $this->initValidators($model);
        if (!isset($this->validatorts[$attr])) {
            return;
        }

        foreach ($this->validatorts[$attr] as $validator) {
            if ($validator instanceof ExistValidator) {
                $this->initEnumOptionsByExistValidator($validator, $attr);
            }
        }
    }

    protected function initEnumOptionsByExistValidator($validator, $attr) {
        if (!in_array($attr, $this->enumFields)) {
            $this->enumFields[] = $attr;
        }

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