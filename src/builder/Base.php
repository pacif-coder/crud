<?php
namespace Crud\builder;

use Yii;
use yii\base\Event;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\validators\BooleanValidator;
use yii\validators\FileValidator;
use yii\validators\ExistValidator;
use yii\validators\EmailValidator;

use Crud\controls\CopyMessageCategoryInterface;
use Crud\controls\Base as BaseControl;
use Crud\controls\Link;
use Crud\helpers\Enum;
use Crud\helpers\ClassI18N;
use Crud\helpers\ModelName;
use Crud\widgets\MaskedInput;

use Exception;
use ReflectionClass;
use ReflectionProperty;

/**
 * XXX
 *
 */
class Base extends \yii\base\Component
{
    public $modelClass;

    public $fields;
    public $fieldTypes;
    public $type2fields;
    public $fieldOptions;
    public $fieldMethodOptions = [];
    public $fieldWidgetOptions = [];
    public $fieldLabels;
    public $fieldAddClass = [];

    public $readyOnlyFields = [];

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

    public $fieldType2widget = [
        'date'  => 'yii\jui\DatePicker',
        'phone' => MaskedInput::class,
    ];

    public $fieldType2widgetOptions = [
        'phone' => [
            'mask' => '7 999 999 99 99',
        ],
    ];

    public $formExtraControls = [];
    public $gridExtraControls = [];
    public $addExtraControls = [];
    public $removeExtraControls = [];
    public $extraControlOptions = [];

    public $enumFields;
    public $enumOptions;
    public $translationEnumOptions = [];
    public $addEmptyEnumOption = true;
    public $emptyEnumOptionLabel = '---';

    public $messageCategory;

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

    protected $innerType = [];

    protected $enumFieldTypes = ['dropDownList', 'radioList', 'checkboxList', 'select'];

    protected $mergeAsArray = [
        'dbType2fieldType',
        'extraControlOptions',
        'fieldType2widget',
        'fieldType2widgetOptions',
        'fieldType2fieldMethod',
        'form',
    ];

    protected $subFormBuilders = [];

    protected static $class2dbColumns = [];
    protected static $class2publicProperties = [];

    // Static variable to cache driver names for different model classes
    protected static $class2driverName = [];

    protected $_isExtraControlCreated = false;
    protected $_extraControlVar;
    protected $_extraControlDefPlace;
    protected $_extraControlsByPlace;

    protected $_builded;

    public function controller2this($controller, $prefix = 'fb_')
    {
        if (isset($controller->messageCategory)) {
            $this->messageCategory = $controller->messageCategory;
        }

        if (isset($controller->modelClass)) {
            $this->setModelClass($controller->modelClass, $prefix);
        }

        $this->static2this($controller, $prefix);
    }

    public function setModelClass($modelClass, $prefix = 'fb_')
    {
        $this->modelClass = $modelClass;
        $this->static2this($modelClass, $prefix);

        if (!$this->messageCategory) {
            $this->messageCategory = ClassI18N::class2messagesPath($modelClass);
        }
    }

    public function static2this($class, $prefix = 'fb_')
    {
        $array = $this->_filterStatic($class);
        $this->array2this($array);
    }

    public function object2this($object, $prefix = null)
    {
        $array = $this->_filterByPrefix(get_object_vars($object), $prefix);
        $this->array2this($array);
    }

    protected function array2this($array)
    {
        if (!$array) {
            return;
        }

        $ref = new ReflectionClass($this);
        $props = $ref->getProperties(ReflectionProperty::IS_PUBLIC);
        $thisVars = ArrayHelper::getColumn($props, 'name');

        foreach (array_intersect(array_keys($array), $thisVars) as $param) {
            if (is_array($this->{$param}) && null === $array[$param]) {
                $array[$param] = [];
            }

            if (in_array($param, $this->mergeAsArray)) {
                $this->{$param} = array_merge($this->{$param}, $array[$param]);
            } else {
                $this->{$param} = $array[$param];
            }
        }
    }

    protected function _filterStatic($class, $prefix = 'fb_')
    {
        $ref = new ReflectionClass($class);
        return $this->_filterByPrefix($ref->getStaticProperties(), $prefix);
    }

    protected function _filterByPrefix($array, $prefix = null)
    {
        if (null === $prefix) {
            return $array;
        }

        $source = [];
        $len = strlen($prefix);
        foreach ($array as $param => $value) {
            if (strlen($param) > $len && $prefix == substr($param, 0, $len)) {
                $source[substr($param, $len)] = $value;
            }
        }

        return $source;
    }

    protected function getControlTypeByDBColumn($attr)
    {
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

    protected function getDBColumns($class)
    {
        if (isset(self::$class2dbColumns[$class])) {
            return self::$class2dbColumns[$class];
        }

        self::$class2dbColumns[$class] = $class::getTableSchema()->columns;
        return self::$class2dbColumns[$class];
    }

    /**
     * Method to get the driver name for a given ActiveRecord class
     * and cache it in a static variable.
     *
     * @param string $modelClass The name of the ActiveRecord class.
     * @return string The driver name.
     */
    public function getDriverName($modelClass)
    {
        if (!isset(self::$class2driverName[$modelClass])) {
            // get the driver name from the database connection
            self::$class2driverName[$modelClass] = $modelClass::getDb()->driverName;
        }

        return self::$class2driverName[$modelClass];
    }

    protected function initValidators($model)
    {
        if (null !== $this->validatorts) {
            return;
        }

        $this->validatorts = [];
        foreach ($model->getValidators() as $validator) {
            /*@var $validator \yii\validators\Validator */
            foreach ($validator->getAttributeNames() as $attr) {
                $this->validatorts[$attr][] = $validator;
            }
        }
    }

    protected function inspectAttr($attr, $model)
    {
        if (isset($this->innerType[$attr])) {
            return;
        }

        /* @var $model ActiveRecord */
        if (Enum::isEnum($model, $attr)) {
            if (!in_array($attr, $this->enumFields)) {
                $this->enumFields[] = $attr;
            }

            $isMultiple = Enum::isMultiple($model, $attr);
            $this->innerType[$attr] = $isMultiple? 'oneToMany' : 'oneToOne';
        }

        if (!isset($this->innerType[$attr])) {
            $this->innerType[$attr] = false;
        }
    }

    protected function getControlTypeByValidator($model, $attr)
    {
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

    protected function initEnumOptionsByDesc($model, $attr)
    {
        $options = $this->enumOptions[$attr];
        if (is_string($options)) {
            $options = [$model, $options];
        }

        if (is_callable($options)) {
            $this->enumOptions[$attr] = call_user_func($options, $attr, $this);
        } elseif (in_array($attr, $this->translationEnumOptions)) {
            $keys = $this->enumOptions[$attr];

            $this->enumOptions[$attr] = [];
            foreach ($keys as $key) {
                $this->enumOptions[$attr][$key] = Yii::t($this->messageCategory, "enum option {$key}");
            }
        }
    }

    protected function addEnumOptionsByExistValidator(&$options, $validator, $attr)
    {
        /* @var $validator ExistValidator */
        $targetModelClass = $validator->targetClass;
        $targetModelAttr = $validator->targetAttribute[$attr];

        $nameAttr = ModelName::getNameAttr($targetModelClass);
        foreach ($targetModelClass::find()->orderBy($nameAttr)->all() as $targetModel) {
            $options[$targetModel->{$targetModelAttr}] = $targetModel->{$nameAttr};
        }
    }

    protected function _checkBuilded()
    {
        if ($this->_builded) {
            throw new Exception('Allready builded');
        }

        $this->_builded = true;
    }

    public function dropExtraControls()
    {
        $this->_isExtraControlCreated = false;
        $this->_extraControlsByPlace = null;
    }

    /**
     * Create buttons in tollbar
     */
    public function createExtraControls()
    {
        if ($this->_isExtraControlCreated) {
            return;
        }

        $controlBase = new ReflectionClass(BaseControl::class);
        $controlNamespace = $controlBase->getNamespaceName();

        $this->_isExtraControlCreated = true;
        $this->_extraControlsByPlace = null;

        $extraControlVar = "{$this->_extraControlVar}ExtraControls";
        $extraControls = array_merge($this->{$extraControlVar}, $this->addExtraControls);

        $this->{$extraControlVar} = [];
        foreach ($extraControls as $control) {
            $options = null;

            if (is_string($control)) {
                $name = $control;
                if (isset($this->extraControlOptions[$control])) {
                    $options = $this->extraControlOptions[$control];
                }

                // if the control name does not contain slashes - this is the internal button
                if (false === strpos($control, '\\')) {
                    $control = ['class' => $controlNamespace . '\\' . ucfirst($control)];
                } else {
                    $control = ['class' => $control];
                }

                $control['name'] = $name;
            }

            if (null !== $options) {
                $control = array_merge($control, $options);
            }

            if (!isset($control['class']) || !$control['class']) {
                $control['class'] = Link::class;
            }

            if (is_subclass_of($control['class'], CopyMessageCategoryInterface::class)) {
                $control['messageCategory'] = $this->messageCategory;
            }

            if (!isset($control['place']) || !$control['place']) {
                $control['place'] = $this->_extraControlDefPlace;
            }

            if (!isset($control['name']) && isset($control['action'])) {
                $control['name'] = $control['action'];
            }

            if (isset($control['name']) && in_array($control['name'], $this->removeExtraControls)) {
                continue;
            }

            if (isset($control['name']) && $control['name']) {
                $this->{$extraControlVar}[$control['name']] = $control;
            } else {
                $this->{$extraControlVar}[] = $control;
            }
        }
    }

    protected function extraControlsToPlace()
    {
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

    public function normalizePlace($place)
    {
        $place = strtolower($place);
        $place = str_replace('\\', '/', $place);
        $place = preg_replace('/\/+/', '/', $place);
        return preg_replace('/^\/+|\/+$/', '', $place);
    }

    public function isExtraControlExist($place)
    {
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

    public function extraControlsByPlace($place)
    {
        $this->extraControlsToPlace();

        $tmp = explode('/', $this->normalizePlace($place));
        if (!isset($this->_extraControlsByPlace[$tmp[0]])) {
            return '';
        }

        $str = '';
        if (1 == count($tmp)) {
            foreach ($this->_extraControlsByPlace[$tmp[0]] as $control) {
                if ($control->isShow()) {
                    $str .= (string) $control;
                }
            }

            return $str;
        }

        if (2 == count($tmp) && !isset($this->_extraControlsByPlace[$tmp[0]][$tmp[1]])) {
            return '';
        }

        foreach ($this->_extraControlsByPlace[$tmp[0]][$tmp[1]] as $control) {
            if ($control->isShow()) {
                $str .= (string) $control;
            }
        }

        return $str;
    }

    /**
     * Create string represention of form fields
     * @return string
     */
    public function fields2string($fields, $form, $model)
    {
        $str = '';
        foreach ($fields as $field) {
            $str .= $this->field2string($field, $form, $model);
        }
        return $str;
    }

    /**
     * Create string represention of form field
     * @return string
     */
    public function field2string($field, \yii\widgets\ActiveForm $form, $model)
    {
        $type = $this->fieldTypes[$field]?? null;
        switch ($type) {
            case 'static':
                $type = 'staticControl';
                break;

            case 'select':
                $type = 'dropDownList';
                break;
        }

        $fieldOptions = $this->fieldOptions[$field]?? [];
        $methodOptions = $this->fieldMethodOptions[$field]?? [];

        $isEnum = false;
        $items = null;
        if (in_array($field, $this->enumFields) || isset($this->enumOptions[$field])) {
            $isEnum = true;
            $items = isset($this->enumOptions[$field]) ? $this->enumOptions[$field] : [];
        }

        // read-only
        if (in_array($field, $this->readyOnlyFields)) {
            // special case - boolean data is output only for reads
            // use booleanFormat in formatter
            if ('boolean' == $type && !array_key_exists('value', $fieldOptions)) {
                $value = empty($model->{$field})? 0 : $model->{$field};
                $methodOptions['value'] = Yii::$app->formatter->booleanFormat[$value];
                $type = 'staticControl';
            }

            // special case - enum data is output only for reads
            if ($isEnum && !array_key_exists('value', $fieldOptions)) {
                $value = $items[$model->{$field}]?? '';
                $methodOptions['value'] = $value;
                $type = 'staticControl';
            }
        }

        /*@var $control \yii\widgets\ActiveField */
        $control = $form->field($model, $field, $fieldOptions);
        if (isset($this->fieldAddClass[$field])) {
            Html::addCssClass($control->options, $this->fieldAddClass[$field]);
        }

        if (isset($this->fieldHint[$field])) {
            $control->hint($this->fieldHint[$field]);
        }

        if (isset($this->fieldLabels[$field])) {
            $control->label($this->fieldLabels[$field]);
        }

        $widget = $this->fieldType2widget[$type]?? null;
        if ($widget) {
            $widgetTypeOptions = $this->fieldType2widgetOptions[$type]?? [];
            $widgetOptions = $this->fieldWidgetOptions[$field]?? [];
            $options = ArrayHelper::merge($widgetTypeOptions, $widgetOptions);

            if ($isEnum) {
                $options['items'] = $items;
            }

            return (string) $control->widget($widget, $options);
        }

        $innerMethod = "_{$type}2string";
        if (method_exists($this, $innerMethod)) {
            return $this->{$innerMethod}($control, $methodOptions);
        }

        $method = $this->fieldType2fieldMethod[$type]?? null;
        if ($isEnum && $method) {
            return $control->{$method}($items, $methodOptions);
        }

        if ($method) {
            if ('password' == $type) {
                $methodOptions['autocomplete'] = 'off';
            }

            return $control->{$method}($methodOptions);
        }

        return $control->textInput($methodOptions);
    }

    protected function _hidden2string($control, $options)
    {
        return $control->hiddenInput($options)->parts['{input}'];
    }

    protected function _staticControl2string($control, $options)
    {
        Html::addCssClass($control->options, 'no-required');
        $control->enableClientValidation = false;

        $options['id'] = Html::getInputId($control->model, $control->attribute);

        return $control->staticControl($options);
    }

    public function bindEventsHandler($handler, $event2method)
    {
        if (is_object($handler)) {
            foreach ($event2method as $event => $method) {
                if ($handler->hasMethod($method)) {
                    $this->on($event, [$handler, $method]);
                }
            }
        } elseif (is_string($handler)) {
            foreach ($event2method as $event => $method) {
                if (method_exists($handler, $method)) {
                    $this->on($event, [$handler, $method]);
                }
            }
        }
    }

    protected function beforeBuild()
    {
        $event = new Event();
        $this->trigger(self::EVENT_BEFORE_BUILD, $event);
    }

    protected function afterBuild()
    {
        $event = new Event();
        $this->trigger(self::EVENT_AFTER_BUILD, $event);
    }
}