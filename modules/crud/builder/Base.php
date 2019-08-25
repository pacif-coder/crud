<?php
namespace app\modules\crud\builder;

use yii\validators\BooleanValidator;
use yii\validators\FileValidator;
use yii\validators\ExistValidator;
use yii\validators\EmailValidator;
use yii\base\Event;

use ReflectionClass;

/**
 * XXX
 *
 */
class Base extends \yii\base\Component {
    public $modelClass;

    public $fields;
    public $skipColumnsInGrid = [];

    public $enumFields;
    public $enumOptions;
    public $addEmptyEnumOption = true;
    public $emptyEnumOptionLabel = '---';

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
        'dbType2fieldType'
    ];

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