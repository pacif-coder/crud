<?php
namespace app\modules\crud\builder;

use yii\db\ActiveRecord;
use yii\validators\BooleanValidator;
use yii\validators\FileValidator;
use app\modules\crud\builder\Base;

/**
 * XXX
 *
 */
class FormBuilder extends Base {
    public $fieldTypes;
    public $fieldOptions;

    public function build(ActiveRecord $model) {
        foreach (['fieldTypes', 'fieldOptions', 'fields', 'enumFields'] as $param) {
            if (null === $this->{$param}) {
                $this->{$param} = [];
            }
        }

        $class = get_class($model);
        $this->dbColumns = $class::getTableSchema()->columns;
        $keys = $class::primaryKey();

        $attrs = array_intersect($model->attributes(), $model->activeAttributes());
        $attrs = array_diff($attrs, $keys);
        foreach ($attrs as $attr) {
            if (!in_array($attr, $this->fields)) {
                $this->fields[] = $attr;
            }
            $this->uptakeNameAttr($attr);

            $type = $this->getType($attr, $model);
            if (!$type) {
                continue;
            }

            $this->fieldTypes[$attr] = $type;
            if ('radio' == $type || 'select' == $type) {
                $this->initEnumOptionsByValidator($model, $attr);
            }
        }
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