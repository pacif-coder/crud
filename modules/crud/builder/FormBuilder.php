<?php
namespace app\modules\crud\builder;

use Yii;
use yii\db\ActiveRecord;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\ActiveQueryInterface;
use yii\helpers\ArrayHelper;

use app\modules\crud\builder\Base;

use Exception;

/**
 * XXX
 *
 */
class FormBuilder extends Base {
    public $formExtraControls = ['save', 'cancel'];

    public $fieldset2fields;
    public $fieldsetLegends = [];
    public $fieldsetAttrs = [];
    public $fieldsBeforeFieldsetLegend = [];
    public $fieldsAfterFieldsetLegend = [];

    protected $_enumActiveQueries = [];

    public $form = [
        'class' => 'yii\bootstrap\ActiveForm',
        'layout' => 'horizontal',
    ];

    protected $_extraControlVar = 'form';

    public function controller2this($controller) {
        if (isset($controller->modelClass)) {
            $this->static2this($controller->modelClass, 'fb_');
        }

        $this->object2this($controller);
    }

    public function build(ActiveRecord $model) {
        $this->_enumActiveQueries = [];

        $this->_isExtraControlCreated = false;
        foreach (['fieldTypes', 'type2fields', 'fieldOptions', 'enumFields'] as $param) {
            if (null === $this->{$param}) {
                $this->{$param} = [];
            }
        }

        foreach ($this->type2fields as $type => $fields) {
            if (is_string($fields)) {
                $fields = preg_split('/\s+/', preg_replace('/^\s+|\s+$/', '', $fields));
            }

            foreach ($fields as $field) {
                $this->fieldTypes[$field] = $type;
            }
        }

        $this->modelClass = get_class($model);

        $this->beforeBuild();
        $this->initNameAttr();

        $allows = $model->activeAttributes();
        foreach ($this->fieldTypes as $attr => $type) {
            if ('static' == $type || 'staticControl' == $type) {
                $allows[] = $attr;
            }
        }

        if (null === $this->fields) {
            $attrs = array_intersect($model->attributes(), $allows);
            $this->fields = array_diff($attrs, $this->modelClass::primaryKey());
        } else {
            $notRule = array_diff($this->fields, $allows);
            if ($notRule) {
                $notRule = implode("', '", $notRule);
                throw new InvalidConfigException("No exist rules on '{$notRule}' fields");
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

        $this->createExtraControls();

        $this->afterBuild();

        $this->extraControlsToPlace();
    }

    protected function initEnumOptions($model, $attr) {
        if (!in_array($attr, $this->enumFields)) {
            $this->enumFields[] = $attr;
        }

        if (isset($this->enumOptions[$attr])) {
            $options = $this->enumOptions[$attr];
            if (is_string($options)) {
                $options = [$model, $options];
            }

            if (is_callable($options)) {
                $this->enumOptions[$attr] = call_user_func($options, $this);
            } elseif (in_array($attr, $this->translationEnumOptions) ){
                $keys = $this->enumOptions[$attr];

                $this->enumOptions[$attr] = [];
                foreach ($keys as $key) {
                    $this->enumOptions[$attr][$key] = Yii::t($this->messageCategory, "enum option {$key}");
                }
            }
        } elseif (isset($this->_enumActiveQueries[$attr])) {
            /*@var $query ActiveQuery */
            $query = $this->_enumActiveQueries[$attr];
            $class = $query->modelClass;

            $keys = $class::primaryKey();
            if (count($keys) > 1) {
                throw new Exception('Not support');
            }

            $nameAttr = $this->_getNameAttr($class);
            if (!$nameAttr) {
                throw new Exception("Model '{$class}' mast have 'name' attr");
            }

            $this->enumOptions[$attr] = ArrayHelper::map($query->asArray()->all(), current($keys), $nameAttr);
        } else {
            $this->initEnumOptionsByValidator($model, $attr);
        }

        if ($this->isAddEmptyEnumOption($attr) && isset($this->enumOptions[$attr])) {
            $this->enumOptions[$attr] = ArrayHelper::merge(['' => $this->emptyEnumOptionLabel], $this->enumOptions[$attr]);
        }
    }

    public function isAddEmptyEnumOption($attr) {
        if (!$this->addEmptyEnumOption) {
            return false;
        }

        $type = $this->fieldTypes[$attr];
        return in_array($type, ['select', 'dropDownList']);
    }

    protected function getType($attr, $model) {
        if (isset($this->fieldTypes[$attr])) {
            return $this->fieldTypes[$attr];
        }

        /* @var $model ActiveRecord */
        $method = "get{$attr}";
        if ($model->hasMethod($method)) {
            $query = $model->{$method}();
            if ($query instanceof ActiveQueryInterface) {
                $query->via = null;
                $query->primaryModel = null;

                $this->_enumActiveQueries[$attr] = $query;

                return $query->multiple? 'checkboxList' : 'select';
            }
        }

        if (null !== ($type = $this->getControlTypeByDBColumn($attr))) {
            return $type;
        }

        if (null !== ($type = $this->getControlTypeByValidator($model, $attr))) {
            return $type;
        }

        if (in_array($attr, $this->enumFields)) {
            return 'select';
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

    public function getNotFieldsetFields() {
        $notInFieldSets = $this->fields;
        foreach ($this->fieldset2fields as $fieldSet => $fields) {
            $notInFieldSets = array_diff($notInFieldSets, $fields);

            $fields = array_intersect($fields, $this->fields);
            if ($fields) {
                $this->fieldset2fields[$fieldSet] = $fields;
            } else {
                unset($this->fieldset2fields[$fieldSet]);
            }
        }

        return $notInFieldSets;
    }

    public function fieldsBeforeFieldsetLegend2string($fieldset, $form, $model) {
        if (!isset($this->fieldset2fields[$fieldset]) || !isset($this->fieldsBeforeFieldsetLegend[$fieldset])) {
            return '';
        }

        $fields = array_intersect($this->fieldset2fields[$fieldset], $this->fieldsBeforeFieldsetLegend[$fieldset]);
        return $this->_fieldsInput2string($fields, $form, $model);
    }

    public function fieldsAfterFieldsetLegend2string($fieldset, $form, $model) {
        if (!isset($this->fieldset2fields[$fieldset]) || !isset($this->fieldsAfterFieldsetLegend[$fieldset])) {
            return '';
        }

        $fields = array_intersect($this->fieldset2fields[$fieldset], $this->fieldsAfterFieldsetLegend[$fieldset]);
        return $this->_fieldsInput2string($fields, $form, $model);
    }

    protected function _fieldsInput2string($fields, $form, $model) {
        $str = '';
        foreach ($fields as $field) {
            $str .= $this->field2string($field, $form, $model)->parts['{input}'];
        }
        return $str;
    }

    public function skipFieldsetLegendFields($fieldset, $fields) {
        if (isset($this->fieldsBeforeFieldsetLegend[$fieldset])) {
            $fields = array_diff($fields, $this->fieldsBeforeFieldsetLegend[$fieldset]);
        }

        if (isset($this->fieldsAfterFieldsetLegend[$fieldset])) {
            $fields = array_diff($fields, $this->fieldsAfterFieldsetLegend[$fieldset]);
        }

        return $fields;
    }

    public function getFieldsetLegend($fieldset) {
        if (isset($this->fieldsetLegends[$fieldset])) {
            return $this->fieldsetLegends[$fieldset];
        }

        return Yii::t($this->messageCategory, $fieldset);
    }

    public function getFormClass() {
        if (is_string($this->form)) {
            return $this->form;
        }

        return $this->form['class'];
    }

    public function getFormConfig() {
        if (is_string($this->form)) {
            return [];
        }

        $config = $this->form;
        if (isset($config['class'])) {
            unset($config['class']);
        }

        return $config;
    }
}
