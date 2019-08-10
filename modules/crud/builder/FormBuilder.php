<?php
namespace app\modules\crud\builder;

use yii\db\ActiveRecord;
use yii\validators\BooleanValidator;
use yii\validators\FileValidator;
use yii\base\InvalidConfigException;

use app\modules\crud\builder\Base;

/**
 * XXX
 *
 */
class FormBuilder extends Base {
    public $fieldTypes;
    public $fieldOptions;

    public function build(ActiveRecord $model) {
        foreach (['fieldTypes', 'fieldOptions', 'enumFields'] as $param) {
            if (null === $this->{$param}) {
                $this->{$param} = [];
            }
        }

        $class = get_class($model);
        $this->dbColumns = $class::getTableSchema()->columns;
        $keys = $class::primaryKey();

        if (null === $this->fields) {
            $attrs = array_intersect($model->attributes(), $model->activeAttributes());
            $this->fields = array_diff($attrs, $keys);
        } else {
            $notExistRules = array_diff($this->fields, $model->activeAttributes());
            if ($notExistRules) {
                throw new InvalidConfigException('No exist rules '
                        . 'on "' . implode('", "', $notExistRules) . '" fields');
            }
        }

        foreach ($this->fields as $field) {
            $type = $this->getType($field, $model);
            if (!$type) {
                continue;
            }

            $this->fieldTypes[$field] = $type;
            if (in_array($type, $this->enumFieldTypes) || in_array($field, $this->enumFields)) {
                $this->initEnumOptions($model, $field);
            }
        }

        if (!$this->uptake || $this->nameAttr || false === $this->nameAttr) {
            return;
        }

        $nameAttrs = array_intersect($this->nameAttrs, $this->fields);
        if ($nameAttrs) {
            $this->nameAttr = reset($nameAttrs);
        }
    }

    protected function initEnumOptions($model, $attr) {
        if (isset($this->enumOptions[$attr])) {
            $options = $this->enumOptions[$attr];
            if (is_string($options)) {
                $options = [$model, $options];
            }

            if (is_callable($options)) {
                return $this->enumOptions[$attr] = call_user_func($options);
            }

            return;
        }

        $this->initEnumOptionsByValidator($model, $attr);
    }

    protected function getType($attr, $model) {
        if (isset($this->fieldTypes[$attr])) {
            return $this->fieldTypes[$attr];
        }

        if (null !== ($type = $this->getControlTypeByDBColumn($attr))) {
            return $type;
        }

        if (null !== ($type = $this->getControlTypeByValidator($model, $attr))) {
            return $type;
        }

        return $this->uptakeType($attr);
    }

    protected function uptakeType($attr) {
        if (!$this->uptake) {
            return;
        }

        if (in_array($attr, $this->phoneAttrs)) {
            return 'phone';
        }

        if (in_array($attr, $this->emailAttrs)) {
            return 'email';
        }
    }
}