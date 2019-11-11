<?php
namespace app\modules\crud\builder;

use Yii;
use yii\validators\BooleanValidator;
use yii\validators\FileValidator;
use yii\validators\ExistValidator;
use yii\validators\EmailValidator;
use yii\base\Event;

use yii\bootstrap\Html;

use app\modules\crud\controls\CopyMessageCategoryInterface;

use ReflectionClass;

/**
 * XXX
 *
 */
class Base extends \yii\base\Component {
    public $modelClass;

    public $fields;
    public $fieldOptions;
    public $fieldTypes;
    public $type2fields;
    public $fieldType2fieldMethod = [
        'textarea' => 'textarea',
        'email' => 'textInput',
        'boolean' => 'checkbox',
        'password' => 'passwordInput',
        'dropDownList' => 'dropDownList',
        'checkboxList' => 'checkboxList',
        'radioList' => 'radioList',
        'hidden' => 'hiddenInput',
    ];

    public $fieldType2widgetOptions = [
        'phone' => [
            'mask' => '8 (999) 999 99 99',
        ],
    ];

    public $fieldType2widget = [
        'date'  => 'yii\jui\DatePicker',
        'phone' => 'yii\widgets\MaskedInput',
        'file'  => 'app\modules\crud\widgets\FileInput',
    ];

    public $formExtraControls = [];
    public $gridExtraControls = [];
    public $addExtraControls = [];
    public $removeExtraControls = [];
    public $extraControlOptions = [];

    public $skipColumnsInGrid = [];

    public $enumFields;
    public $enumOptions;
    public $addEmptyEnumOption = true;
    public $emptyEnumOptionLabel = '---';

    public $messageCategory;

    public $uptake = true;
    public $nameAttr = null;

    public $phoneAttrs = ['phone', 'tel'];
    public $emailAttrs = ['email'];
    public $nameAttrs = ['name', 'title', 'fio', 'id'];

    public $dbType2fieldType = [
        'text' => 'textarea',
        'boolean' => 'boolean',
        'datetime' => 'datetime',
        'date' => 'date',
        'time' => 'time',
    ];

    /**
     * @event Event an event raised right before build
     */
    const EVENT_BEFORE_BUILD = 'beforeBuild';

    /**
     * @event Event an event raised right after build
     */
    const EVENT_AFTER_BUILD = 'afterBuild';

    protected $validatorts;
    protected $enumFieldTypes = ['dropDownList', 'radioList', 'checkboxList'];
    protected $mergeAsArray = [
        'dbType2fieldType',
        'extraControlOptions',
    ];

    protected $_isExtraControlCreated = false;
    protected $_extraControlVar;
    protected $_extraControlsByPlace;

    protected static $class2nameAttr = [];
    protected static $class2dbColumns = [];

    public function static2this($class, $prefix = 'fb_') {
        $ref = new ReflectionClass($class);
        $this->array2this($ref->getStaticProperties(), $prefix);
    }

    public function object2this($object, $prefix = null) {
        $this->array2this(get_object_vars($object), $prefix);
    }

    protected function array2this($array, $prefix = null) {
        if (null !== $prefix) {
            $source = [];
            $len = strlen($prefix);
            foreach ($array as $param => $value) {
                if (strlen($param) > $len && $prefix == substr($param, 0, $len)) {
                    $source[substr($param, $len)] = $value;
                }
            }
        } else {
            $source = $array;
        }

        if (!$source) {
            return;
        }

        $thisVars = array_keys(get_object_vars($this));
        foreach (array_intersect(array_keys($source), $thisVars) as $param) {
            if (in_array($param, $this->mergeAsArray)) {
                $this->{$param} = array_merge($this->{$param}, $source[$param]);
            } else {
                $this->{$param} = $source[$param];
            }
        }
    }

    protected function initNameAttr() {
        if (null !== $this->nameAttr) {
            return;
        }

        if (($nameAttr = $this->getNameAttr($this->modelClass))) {
            $this->nameAttr = $nameAttr;
        }
    }

    protected function getNameAttr($modelClass) {
        if (isset(self::$class2nameAttr[$modelClass])) {
            return self::$class2nameAttr[$modelClass];
        }

        self::$class2nameAttr[$modelClass] = $this->_getNameAttr($modelClass);
        return self::$class2nameAttr[$modelClass];
    }

    protected function _getNameAttr($modelClass) {
        $ref = new ReflectionClass($modelClass);
        $staticAttrs = $ref->getStaticProperties();
        if (isset($staticAttrs['fb_nameAttr'])) {
            return $staticAttrs['fb_nameAttr'];
        }

        if (is_a($modelClass, 'app\modules\crud\models\WithNameAttr', true)) {
            return self::$class2nameAttr[$modelClass] = $modelClass::NAME_ATTR;
        }

        $uptake = isset($staticAttrs['fb_uptake'])? $staticAttrs['fb_uptake'] : true;
        if (!$uptake) {
            return;
        }

        if (isset($staticAttrs['fb_columns'])) {
            $columns = array_keys($this->parseColumns($staticAttrs['fb_columns']));
        } else {
            $fields = isset($staticAttrs['fb_fields'])? $staticAttrs['fb_fields'] : null;
            $skipColumnsInGrid = isset($staticAttrs['fb_skipColumnsInGrid'])? $staticAttrs['fb_skipColumnsInGrid'] : [];

            $columns = $this->_getDefaultColumns($modelClass, $fields, $skipColumnsInGrid);
        }

        $names = array_intersect($this->nameAttrs, $columns);
        if ($names) {
            return reset($names);
        }
    }

    protected function getControlTypeByDBColumn($attr) {
        $dbColumns = $this->getDBColumns($this->modelClass);
        if (!isset($dbColumns[$attr])) {
            return;
        }

        /* @var $column \yii\db\ColumnSchema */
        $column = $dbColumns[$attr];
        if (isset($this->dbType2fieldType[$column->type])) {
            return $this->dbType2fieldType[$column->type];
        }
    }

    protected function getDBColumns($class) {
        if (isset(self::$class2dbColumns[$class])) {
            return self::$class2dbColumns[$class];
        }

        self::$class2dbColumns[$class] = $class::getTableSchema()->columns;
        return self::$class2dbColumns[$class];
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
                return 'dropDownList';
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
        $this->enumOptions[$attr] = [];
        if ($this->addEmptyEnumOption) {
            $this->enumOptions[$attr][''] = $this->emptyEnumOptionLabel;
        }

        $this->addEnumOptionsByExistValidator($this->enumOptions[$attr], $validator, $attr);
    }

    protected function addEnumOptionsByExistValidator(&$options, $validator, $attr) {
        /* @var $validator ExistValidator */
        $targetModelClass = $validator->targetClass;
        $targetModelAttr = $validator->targetAttribute[$attr];

        $nameAttr = $this->getNameAttr($targetModelClass);
        foreach ($targetModelClass::find()->orderBy($nameAttr)->all() as $targetModel) {
            $options[$targetModel->{$targetModelAttr}] = $targetModel->{$nameAttr};
        }
    }

    protected function parseColumns($columns) {
        $result = [];
        foreach ($columns as $column => $desc) {
            if (is_string($desc)) {
                $result[$desc] = $this->parseColumnDesc($desc);
            } elseif (!isset($desc['attribute']) && !is_int($column)) {
                $desc['attribute'] = $column;
                $result[$column] = $desc;
            } else {
                $result[$column] = $desc;
            }
        }

        return $result;
    }

    protected function _getDefaultColumns($modelClass, $fields, $skipColumnsInGrid) {
        if ($fields) {
            return array_diff($fields, $skipColumnsInGrid);
        }

        $keys = $modelClass::primaryKey();
        $columns = array_keys($this->getDBColumns($modelClass));
        return array_diff($columns, $keys, $skipColumnsInGrid);
    }

    protected function parseColumnDesc($text) {
        if (!preg_match('/^([^:]+)(:(\w*))?(:(.*))?$/', $text, $matches)) {
            throw new InvalidConfigException('The column must be specified in the format of "attribute", "attribute:format" or "attribute:format:label"');
        }

        return [
            'attribute' => $matches[1],
            'format' => isset($matches[3]) ? $matches[3] : null,
            'label' => isset($matches[5]) ? $matches[5] : null,
        ];
    }

    public function dropExtraControls() {
        $this->_isExtraControlCreated = false;
        $this->_extraControlsByPlace = null;
    }

    /**
     * Create buttons in tollbar
     */
    public function createExtraControls() {
        if ($this->_isExtraControlCreated) {
            return;
        }

        $this->_isExtraControlCreated = true;
        $this->_extraControlsByPlace = null;

        $extraControlVar = "{$this->_extraControlVar}ExtraControls";
        $extraControls = array_merge($this->{$extraControlVar}, $this->addExtraControls);
        foreach ($extraControls as $i => $control) {
            if (in_array($control, $this->removeExtraControls)) {
                unset($extraControls[$i]);
            }
        }

        $this->{$extraControlVar} = [];
        foreach ($extraControls as $place => $control) {
            $options = null;

            if (is_string($control)) {
                $name = $control;
                if (isset($this->extraControlOptions[$control])) {
                    $options = $this->extraControlOptions[$control];
                }

                // if the control name does not contain slashes - this is the internal button
                if (false === strpos($control, '\\')) {
                    $control = ['class' => 'app\modules\crud\controls\\' . ucfirst($control)];
                } else {
                    $control = ['class' => $control];
                }

                $control['name'] = $name;
            }

            if (null !== $options) {
                $control = array_merge($control, $options);
            }

            if (is_subclass_of($control['class'], CopyMessageCategoryInterface::class)) {
                $control['messageCategory'] = $this->messageCategory;
            }

            if (!is_int($place) && (!isset($control['place']) || !$control['place'])) {
                $control['place'] = $place;
            }

            if (isset($control['name']) && $control['name']) {
                $this->{$extraControlVar}[$control['name']] = $control;
            } else {
                $this->{$extraControlVar}[] = $control;
            }
        }
    }

    protected function extraControlsToPlace() {
        $this->createExtraControls();
        if (null !== $this->_extraControlsByPlace) {
            return;
        }

        $this->_extraControlsByPlace = [];
        $extraControlVar = "{$this->_extraControlVar}ExtraControls";
        foreach ($this->{$extraControlVar} as $control) {
            $control = Yii::createObject($control);
            if (!$control->place) {
                continue;
            }

            $tmp = explode('/', $this->normalizePlace($control->place));
            if (count($tmp) > 1) {
                $this->_extraControlsByPlace[$tmp[0]][$tmp[1]][] = $control;
            } else {
                $this->_extraControlsByPlace[$tmp[0]][] = $control;
            }
        }
    }

    public function normalizePlace($place) {
        $place = strtolower($place);
        $place = str_replace('\\', '/', $place);
        $place = preg_replace('/\/+/', '/', $place);
        return preg_replace('/^\/+|\/+$/', '', $place);
    }

    public function isExtraControlExist($place) {
        $this->extraControlsToPlace();

        $tmp = explode('/', $this->normalizePlace($place));
        if (!isset($this->_extraControlsByPlace[$tmp[0]])) {
            return '';
        }

        if (1 == count($tmp)) {
            return isset($this->_extraControlsByPlace[$tmp[0]]);
        }

        if (2 == count($tmp)) {
            return isset($this->_extraControlsByPlace[$tmp[0]][$tmp[1]]);
        }
    }

    public function extraControlsByPlace($place) {
        $this->extraControlsToPlace();

        $tmp = explode('/', $this->normalizePlace($place));
        if (!isset($this->_extraControlsByPlace[$tmp[0]])) {
            return '';
        }

        if (1 == count($tmp)) {
            return implode('', $this->_extraControlsByPlace[$tmp[0]]);
        }

        if (2 == count($tmp) && !isset($this->_extraControlsByPlace[$tmp[0]][$tmp[1]])) {
            return '';
        }

        return implode('', $this->_extraControlsByPlace[$tmp[0]][$tmp[1]]);
    }

    protected function _hidden2string($control) {
        return (string) $control->hiddenInput()->parts['{input}'];
    }

    protected function _staticControl2string($control) {
        Html::addCssClass($control->options, 'no-required');
        $control->enableClientValidation = false;

        return (string) $control->staticControl();
    }

    public function field2string($field, $form, $model) {
        $type = isset($this->fieldTypes[$field]) ? $this->fieldTypes[$field] : null;
        switch ($type) {
            case 'static':
                $type = 'staticControl';
                break;

            case 'select':
                $type = 'dropDownList';
                break;
        }

        $typeOptions = isset($this->fieldType2widgetOptions[$type]) ? $this->fieldType2widgetOptions[$type] : [];
        $fieldOptions = isset($this->fieldOptions[$field]) ? $this->fieldOptions[$field] : [];
        $options = array_merge($typeOptions, $fieldOptions);

        $widget = isset($this->fieldType2widget[$type])? $this->fieldType2widget[$type] : null;
        if ($widget) {
            return (string) $form->field($model, $field)->widget($widget, $options? $options : null);
        }

        /* @var $control \yii\bootstrap\ActiveField */
        $control = $form->field($model, $field, $options);

        $innerMethod = "_{$type}2string";
        if (method_exists($this, $innerMethod)) {
            return $this->{$innerMethod}($control, $options);
        }

        $method = isset($this->fieldType2fieldMethod[$type])? $this->fieldType2fieldMethod[$type] : null;

        if (in_array($field, $this->enumFields) || isset($this->enumOptions[$field])) {
            $items = isset($this->enumOptions[$field])? $this->enumOptions[$field] : [];
            if ($method) {
                return (string) $control->{$method}($items);
            }
        }

        if ($method) {
            return (string) $control->{$method}();
        }

        return (string) $control->textInput();
    }

    public function fields2string($fields, $form, $model) {
        $str = '';
        foreach ($fields as $field) {
            $str .= $this->field2string($field, $form, $model);
        }
        return $str;
    }

    public function bindEventsHandler($handler, $event2method) {
        foreach ($event2method as $event => $method) {
            if ($handler->hasMethod($method)) {
                $this->on($event, [$handler, $method]);
            }
        }
    }

    protected function beforeBuild() {
        $event = new Event();
        $this->trigger(self::EVENT_BEFORE_BUILD, $event);
    }

    protected function afterBuild() {
        $event = new Event();
        $this->trigger(self::EVENT_AFTER_BUILD, $event);
    }
}