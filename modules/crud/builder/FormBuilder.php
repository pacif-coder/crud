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
class Form extends Base {
    public $fields;
    public $fieldTypes;
    public $fieldOptions;

    // Enum
    public $enumFields;
    public $enumOptions;
    public $enumGetOptionsMethod;

    public function build(ActiveRecord $model) {
        $class = get_class($model);

        $this->dbColumns = $class::getTableSchema()->columns;
        $keys = $class::primaryKey();
        $activeAttributes = $model->activeAttributes();

        foreach (['fieldTypes', 'fieldOptions', 'fields', 'enumFields'] as $param) {
            if (null === $this->{$param}) {
                $this->{$param} = [];
            }
        }

        $attrs = $model->attributes();
        $attrs = array_diff($attrs, $keys);
        $attrs = array_diff($attrs, array_keys($this->fieldTypes));
        $attrs = array_intersect($attrs, $activeAttributes);

        foreach ($attrs as $attr) {
            $type = null;
            if (!in_array($attr, $this->fields)) {
                $this->fields[] = $attr;
            }
            $this->uptakeNameAttr($attr);

            if (null !== ($type = $this->getControlTypeByDBColumn($attr))) {
                $this->fieldTypes[$attr] = $type;
                continue;
            }

            if (null !== ($type = $this->getControlTypeByValidator($model, $attr))) {
                $this->fieldTypes[$attr] = $type;
                continue;
            }

            if (!$this->uptake) {
                continue;
            }

            if (in_array($attr, $this->phoneAttrs)) {
                $type = 'phone';
            }

            if (in_array($attr, $this->emailAttrs)) {
                $type = 'email';
            }

            if ($type) {
                $this->fieldTypes[$attr] = $type;
            }
        }
    }
}