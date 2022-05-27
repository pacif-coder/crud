<?php
namespace app\modules\crud\builder;

use Yii;
use yii\base\Event;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\validators\ExistValidator;

use app\modules\crud\builder\Base;
use app\modules\crud\grid\column\ActionLinkColumn;
use app\modules\crud\grid\column\DragIconColumn;
use app\modules\crud\grid\GridView;
use app\modules\crud\grid\MatrixGridView;
use app\modules\crud\grid\FilterModel;
use app\modules\crud\helpers\ModelName;
use app\modules\crud\helpers\ParentModel;
use app\modules\crud\models\ModelWithParentInterface;
use app\modules\crud\models\ModelWithOrderInterface;

/**
 * XXX
 *
 */
class GridBuilder extends Base
{
    public $gridType;
    public $gridClass;

    public $columns;
    public $removeColumns = [];
    public $addColumnAfter = [];
    public $addColumnsAfter = [];
    public $addColumnBefore = [];
    public $addColumnsBefore = [];
    public $columnFormats = [];
    public $columnLabels = [];
    public $columnOptions = [];

    public $editColumnClass = ActionLinkColumn::class;

    public $defaultOrder;
    public $pageSize;
    public $gridWithEditLink = true;
    public $gridOptions = [];
    public $dragable;
    public $addDragIconColumn = true;
    public $removeSortColumn = true;

    public $addToolbarButtons = [];
    public $removeToolbarButtons = [];
    public $toolbarButtonOptions = [];

    public $isInsideForm;
    public $surroundForm;
    public $surroundFormAction = '';
    public $surroundFormMethod = 'post';
    public $surroundFormOptions = [];

    public $gridExtraControls = ['create'];

    public $parentModelID;

    // Filter part
    public $withFilter = false;
    public $filterInGrid = true;

    public $filterAttrs;
    public $addFilterAttrs = [];
    public $removeFilterAttrs = [];
    public $filterAttrOperator = [];
    public $filterOnlyIndexed = true;
    public $noApplyFilterAttrs = [];
    public $transformAttrMap = [];

    public $autoJoin = true;

    public $joinWith = [];

    /**
     * @event Grid types
     */

    const TYPE_DEFAULT = 'default';

    const TYPE_MATRIX = 'matrix';

    public $gridType2Class = [
        self::TYPE_DEFAULT => GridView::class,
        self::TYPE_MATRIX => MatrixGridView::class,
    ];

    /**
     * @event Event
     */
    const EVENT_BEFORE_FILTER_APPLY = 'beforeFilterApply';

    /**
     *
     * @var \yii\db\ActiveQuery
     */
    protected $query;
    protected $provider;
    protected $filterModel;

    protected $_model;
    protected $_transformSortAttrMap = [];
    protected $_transformFilterAttrMap = [];
    protected $_isChangeGridOption = false;

    protected $_joinWithModels = [];

    protected $_extraControlVar = 'grid';
    protected $_extraControlDefPlace = 'title';

    protected static $_autoJoinI = 1;

    public function controller2this($controller, $prefix = 'fb_')
    {
        if (isset($controller->parentModelID)) {
            $this->parentModelID = $controller->parentModelID;
        }

        parent::controller2this($controller, $prefix);
    }

    public function build($modelClass = null)
    {
        $this->_checkBuilded();

        $this->_isExtraControlCreated = false;
        $this->_transformSortAttrMap = $this->_transformFilterAttrMap = [];

        if ($modelClass && $modelClass != $this->modelClass) {
            $this->setModelClass($modelClass);
        }

        if (null === $this->dragable && is_a($this->modelClass, ModelWithOrderInterface::class, true)) {
            $this->dragable = true;
        }

        $this->beforeBuild();
        $this->initNameAttr();

        if (!$this->gridType) {
            $this->gridType = self::TYPE_DEFAULT;
        }
        $this->gridClass = $this->gridType2Class[$this->gridType];

        if (null === $this->columns) {
            $this->columns = [];
            foreach ($this->getDefaultColumns($this->modelClass) as $column) {
                $this->columns[$column] = ['attribute' => $column];
            }
        } else {
            $this->columns = $this->parseColumns($this->columns);
        }

        $this->insertColumns();
        $this->joinWith();

        foreach ($this->columns as $column => $desc) {
            // other options
            if (isset($this->columnOptions[$column])) {
                $desc = ArrayHelper::merge($desc, $this->columnOptions[$column]);
            }

            // set label
            if (isset($this->columnLabels[$column])) {
                $desc['label'] = $this->columnLabels[$column];
            }

            $attr = isset($desc['attribute'])? $desc['attribute'] : null;

            // define column format
            if (null !== $attr && !isset($desc['format'])) {
                $format = $this->getColumnFormat($attr, $this->modelClass);
                if (null !== $format) {
                    $desc['format'] = $format;
                }
            }

            $this->columns[$column] = $desc;
        }

        if ($this->dragable) {
            // off sort in table column
            foreach ($this->columns as $column => $desc) {
                if (!isset($desc['attribute'])) {
                    continue;
                }

                $desc['enableSorting'] = false;
                $this->columns[$column] = $desc;
            }

            // remove sort attr column
            if ($this->removeSortColumn && is_a($this->modelClass, ModelWithOrderInterface::class, true)) {
                $sortAttr = $this->modelClass::ORDER_ATTR;
                if ($sortAttr && isset($this->columns[$sortAttr])) {
                    unset($this->columns[$sortAttr]);
                }
            }

            if ($this->addDragIconColumn) {
                $this->columns[] = ['class' => DragIconColumn::class];
            }
        }

        $this->autoJoin();
        $this->selectInFilter();

        // define filter operator
        $dbColumns = $this->getDBColumns($this->modelClass);
        foreach ($this->columns as $desc) {
            $attr = isset($desc['attribute'])? $desc['attribute'] : null;
            if (null === $attr) {
                continue;
            }

            if (isset($this->filterAttrOperator[$attr])) {
                continue;
            }

            if (!isset($dbColumns[$attr])) {
                continue;
            }

            /* @var $column \yii\db\ColumnSchema */
            $column = $dbColumns[$attr];
            switch ($column->type) {
                case 'char':
                case 'string':
                case 'text':
                    $this->filterAttrOperator[$attr] = 'like';
                    break;
            }
        }

        if ($this->gridWithEditLink) {
            $this->makeGridEditLink();
        }

        $this->fixSort();

        $this->createFilter();
        $this->filterApply();

        $this->createExtraControls();

        $this->afterBuild();
    }

    protected function insertColumns()
    {
        $afterColumns = array_keys($this->addColumnAfter);
        $afterColumns = array_merge($afterColumns, array_keys($this->addColumnsAfter));
        $afterColumns = array_unique($afterColumns);
        foreach ($afterColumns as $afterColumn) {
            $attrInColumns = array_keys($this->columns);
            if (!in_array($afterColumn, $attrInColumns)) {
                continue;
            }

            $index = array_search($afterColumn, $attrInColumns, true);
            if (false === $index) {
                continue;
            }

            if (isset($this->addColumnAfter[$afterColumn])) {
                $insert = [$this->addColumnAfter[$afterColumn]];
            } else {
                $insert = $this->addColumnsAfter[$afterColumn];
            }

            array_splice($this->columns, $index + 1, 0, $insert);
        }

        $beforeColumns = array_keys($this->addColumnBefore);
        $beforeColumns = array_merge($beforeColumns, array_keys($this->addColumnsBefore));
        $beforeColumns = array_unique($beforeColumns);
        foreach ($beforeColumns as $beforeColumn) {
            $attrInColumns = array_keys($this->columns);
            if (!in_array($beforeColumn, $attrInColumns)) {
                continue;
            }

            $index = array_search($beforeColumn, $attrInColumns, true);
            if (false === $index) {
                continue;
            }

            if (isset($this->addColumnBefore[$beforeColumn])) {
                $insert = [$this->addColumnBefore[$beforeColumn]];
            } else {
                $insert = $this->addColumnsBefore[$beforeColumn];
            }

            array_splice($this->columns, $index, 0, $insert);
        }

        $this->columns = $this->parseColumns($this->columns);
    }

    protected function filterApply()
    {
        if (!$this->filterModel) {
            return;
        }

        $this->filterModel->transformAttrMap = $this->_transformFilterAttrMap;
        $this->beforeFilterApply();

        $query = $this->getQuery();
        $this->filterModel->filter($query);
    }

    public function getFilter()
    {
        return $this->filterModel;
    }

    protected function createFilter()
    {
        if (!$this->withFilter) {
            return;
        }

        $this->filterModel = new FilterModel();
        $this->filterModel->builder2this($this);
        $this->filterModel->setModel(Yii::createObject($this->modelClass));
        $this->filterModel->load(Yii::$app->request->get());
    }

    /**
     * @return ActiveDataProvider
     */
    public function getProvider()
    {
        if (null !== $this->provider) {
            return $this->provider;
        }

        $options = [
            'query' => $this->getQuery(),
        ];

        if ($this->dragable) {
            $options['sort'] = false;
        } else {
            $defaultOrder = null;
            if (null !== $this->defaultOrder) {
                $defaultOrder = $this->defaultOrder;
            } elseif ($this->nameAttr) {
                $defaultOrder = [$this->nameAttr => SORT_ASC];
            }

            if (null !== $defaultOrder) {
                $options['sort']['defaultOrder'] = $defaultOrder;
            }
        }

        if (null !== $this->pageSize) {
            $options['pagination']['pageSize'] = $this->pageSize;
        }

        $this->provider = new ActiveDataProvider($options);
        $sort = $this->provider->getSort();
        if (!$sort) {
            return $this->provider;
        }

        foreach ($this->joinWith as $name => $columns) {
            foreach ((array) $columns as $column) {
                $attr = "{$name}.{$column}";
                if (isset($sort->attributes[$attr])) {
                    continue;
                }

                $sort->attributes[$attr] = ['label' => $attr];
            }
        }

        return $this->provider;
    }

    protected function fixSort()
    {
        if (!$this->_transformSortAttrMap) {
            return;
        }

        $provider = $this->getProvider();
        $sort = $provider->getSort();

        foreach ($this->_transformSortAttrMap as $attr => $fix) {
            if (!isset($sort->attributes[$attr])) {
                continue;
            }

            $sort->attributes[$attr]['asc'] = [$fix => SORT_ASC];
            $sort->attributes[$attr]['desc'] = [$fix => SORT_DESC];
        }
    }

    public function &getOptions()
    {
        if ($this->_isChangeGridOption) {
            return $this->gridOptions;
        }

        $this->_isChangeGridOption = true;

        if (self::TYPE_MATRIX != $this->gridType) {
            $this->gridOptions['columns'] = $this->columns;
        }

        $this->gridOptions['addToolbarButtons'] = $this->addToolbarButtons;
        $this->gridOptions['removeToolbarButtons'] = $this->removeToolbarButtons;
        $this->gridOptions['toolbarButtonOptions'] = $this->toolbarButtonOptions;
        $this->gridOptions['dataProvider'] = $this->getProvider();

        $this->gridOptions['isInsideForm'] = $this->isInsideForm;
        $this->gridOptions['surroundForm'] = $this->surroundForm;
        $this->gridOptions['surroundFormAction'] = $this->surroundFormAction;
        $this->gridOptions['surroundFormMethod'] = $this->surroundFormMethod;
        $this->gridOptions['surroundFormOptions'] = $this->surroundFormOptions;

        if ($this->messageCategory) {
            $this->gridOptions['messageCategory'] = $this->messageCategory;
        }

        $this->gridOptions['dragable'] = $this->dragable;

        if ($this->filterModel && $this->filterInGrid) {
            $this->gridOptions['filterModel'] = $this->filterModel;
        }

        return $this->gridOptions;
    }

    /**
     *
     * @return \yii\db\ActiveQuery
     */
    public function getQuery()
    {
        if ($this->query) {
            return $this->query;
        }

        $modelClass = $this->modelClass;
        $table = $modelClass::tableName();
        $this->query = $modelClass::find();

        $parentModelAttr = ParentModel::getParentModelAttr($modelClass);
        if ($parentModelAttr) {
            $column = "[[{$table}]].[[{$parentModelAttr}]]";
            $this->query->andWhere([$column => $this->parentModelID]);
        }

        if ($this->dragable) {
            $this->query->orderBy([$this->modelClass::ORDER_ATTR => SORT_ASC]);
        }

        if (!$this->query->select) {
            $this->query->addSelect("[[{$table}]].*");
        }

        return $this->query;
    }

    public function setQuery($query)
    {
        return $this->query = $query;
    }

    protected function selectInFilter()
    {
        foreach ($this->columns as $column => $desc) {
            $attr = null;
            if (is_array($desc) && isset($desc['attribute'])) {
                $attr = $desc['attribute'];
            }

            if (null === $attr) {
                continue;
            }

            if (is_array($desc) && isset($desc['filter']) && !$desc['filter']) {
                continue;
            }

            $model = $this->_createTmpModel();
            $this->initValidators($model);

            if (!isset($this->validatorts[$attr])) {
                continue;
            }

            foreach ($this->validatorts[$attr] as $validator) {
                if ($validator instanceof ExistValidator) {
                    $desc['filter'] = [];
                    $this->addEnumOptionsByExistValidator($desc['filter'], $validator, $attr);
                    $this->columns[$column] = $desc;
                }
            }
        }
    }

    protected function autoJoin()
    {
        if (!$this->autoJoin) {
            return;
        }

        foreach ($this->columns as $desc) {
            $attr = null;
            if (is_array($desc) && isset($desc['attribute'])) {
                $attr = $desc['attribute'];
            }

            if (null === $attr) {
                continue;
            }

            $model = $this->_createTmpModel();
            $this->initValidators($model);

            if (!isset($this->validatorts[$attr])) {
                continue;
            }

            foreach ($this->validatorts[$attr] as $validator) {
                if ($validator instanceof ExistValidator) {
                    $this->linkQueryByExistValidator($validator, $attr);
                }
            }
        }
    }

    protected function linkQueryByExistValidator($validator, $attr)
    {
        /* @var $validator ExistValidator */
        $targetModelClass = $validator->targetClass;
        $nameAttr = ModelName::getNameAttr($targetModelClass);
        if (!$nameAttr) {
            return;
        }

        /* @var $query \yii\db\ActiveQuery */
        $query = $this->getQuery();

        $originJoinToTable = $targetModelClass::tableName();
        $joinToTable = '_gb_' . self::$_autoJoinI++;

        $table = $query->modelClass::tableName();
        $joinAttr = $validator->targetAttribute[$attr];
        $on = "[[{$joinToTable}]].[[{$joinAttr}]] = [[{$table}]].[[{$attr}]]";

        $query->join('left join', [$joinToTable => $originJoinToTable], $on);
        if (!$query->select) {
            $query->addSelect("[[{$table}]].*");
        }

        $query->addSelect("[[{$joinToTable}]].[[{$nameAttr}]] as [[{$attr}]]");
        $this->_transformSortAttrMap[$attr] = "[[{$joinToTable}]].[[{$nameAttr}]]";

        $joinTableAttrs = array_keys($this->getDBColumns($targetModelClass));
        $tableAttrs = array_keys($this->getDBColumns($this->modelClass));
        $intersect = array_intersect($joinTableAttrs, $tableAttrs);
        if (!$intersect) {
            return;
        }

        foreach ($intersect as $attr) {
            $this->_transformFilterAttrMap[$attr] = "[[{$table}]].[[{$attr}]]";
        }
    }

    protected function joinWith()
    {
        if (!$this->joinWith) {
            return;
        }

        /* @var $query \yii\db\ActiveQuery */
        $query = $this->getQuery();

        $schema = Yii::$app->db->schema;

        $model = $this->_createTmpModel();
        foreach ($this->joinWith as $name => $columns) {
            $query->joinWith($name, false, 'left join');

            $method = "get{$name}";
            $object = $model->{$method}();
            $table = $object->modelClass::tableName();

            $model = $object->modelClass::instance();
            $this->_joinWithModels[$name] = $model;

            foreach ((array) $columns as $column) {
                $attr = "{$name}.{$column}";
                $this->columns[$attr] = [
                    'attribute' => $attr,
                    'label' => $model->getAttributeLabel($column),
                ];

                $as = $schema->quoteSimpleColumnName($attr);
                $query->addSelect("[[{$table}]].[[{$column}]] as {$as}");

                $this->_transformSortAttrMap[$attr] = "[[{$table}]].[[{$column}]]";
            }

            $allOptions = $this->_getJoinedOptions($object->modelClass, $columns);

            foreach ($allOptions as $param => $map) {
                foreach ($map as $column => $value) {
                    $attr = "{$name}.{$column}";
                    $this->{$param}[$attr] = $value;
                }
            }
        }
    }

    protected function _getJoinedOptions($class, $columns)
    {
        $all = $this->_filterStatic($class);

        $result = [];
        foreach (['columnFormats', 'columnLabels', 'columnOptions'] as $param) {
            if (!isset($all[$param])) {
                continue;
            }

            $value = $all[$param];
            foreach ((array) $columns as $column) {
                if (!isset($value[$column])) {
                    continue;
                }

                $result[$param][$column] = $value[$column];
            }
        }

        return $result;
    }

    protected function getColumnFormat($attr)
    {
        if (isset($this->columnFormats[$attr])) {
            return $this->columnFormats[$attr];
        }

        $model = $this->_createTmpModel();

        $format = $this->getControlTypeByValidator($model, $attr);
        switch ($format) {
            case 'email':
            case 'boolean':
                return $format;
                break;
        }

        $dbColumns = $this->getDBColumns($this->modelClass);
        if (isset($dbColumns[$attr])) {
            $column = $dbColumns[$attr];

            /**@var $column \yii\db\ColumnSchema  **/
            switch ($column->type) {
                case 'datetime':
                case 'date':
                case 'time':
                case 'timestamp':
                case 'decimal':
                case 'integer':
                    return $column->type;
                    break;

                case 'text':
                    return 'ntext';
                    break;
            }
        }

        // enum value in column
        if (isset($this->enumOptions[$attr])) {
            $this->initEnumOptionsByDesc($model, $attr);
            $options = $this->enumOptions[$attr];
            $emptyLabel = $this->emptyEnumOptionLabel;

            return function ($value, $formatter) use ($options, $emptyLabel) {
                $value = isset($options[$value])? $options[$value] : $emptyLabel;
                return $formatter->asText($value);
            };
        }

        return 'text';
    }

    protected function makeGridEditLink()
    {
        $targetColumn = null;
        $nameAttr = $this->nameAttr;
        if (null === $this->nameAttr) {
            $keys = $this->modelClass::primaryKey();
            if (1 == count($keys)) {
                $nameAttr = current($keys);
            }
        }

        if (null === $nameAttr) {
            return;
        }

        foreach ($this->columns as $column => $desc) {
            $attr = isset($desc['attribute'])? $desc['attribute'] : null;
            if (null === $attr) {
                continue;
            }

            if ($nameAttr == $attr) {
                $targetColumn = $column;
                break;
            }
        }

        if (null === $targetColumn) {
            return;
        }

        $desc = $this->columns[$targetColumn];
        if (isset($desc['class'])) {
            return;
        }

        $desc['class'] = $this->editColumnClass;
        $this->columns[$targetColumn] = $desc;
    }

    protected function _createTmpModel()
    {
        if ($this->_model) {
            return $this->_model;
        }

        $this->_model = $this->modelClass::instantiate(null);

        $parentModelAttr = ParentModel::getParentModelAttr($this->modelClass);
        if ($parentModelAttr) {
            $this->_model->{$parentModelAttr} = $this->parentModelID;
        }

        return $this->_model;
    }

    protected function getDefaultColumns($modelClass)
    {
        if ($this->fields) {
            return array_diff($this->fields, $this->removeColumns);
        }

        $keys = $modelClass::primaryKey();
        $columns = array_keys($this->getDBColumns($modelClass));
        return array_diff($columns, $keys, $this->removeColumns);
    }

    protected function parseColumns($columns)
    {
        $result = [];
        foreach ($columns as $column => $desc) {
            if (is_string($desc)) {
                $desc = $this->parseColumnDesc($desc);
                $result[$desc['attribute']] = $desc;
            } elseif (!isset($desc['attribute']) && !is_int($column)) {
                $desc['attribute'] = $column;
                $result[$column] = $desc;
            } else {
                $result[$column] = $desc;
            }
        }

        return $result;
    }

    protected function parseColumnDesc($text)
    {
        if (!preg_match('/^([^:]+)(:(\w*))?(:(.*))?$/', $text, $matches)) {
            throw new InvalidConfigException('The column must be specified in the format of "attribute", "attribute:format" or "attribute:format:label"');
        }

        return [
            'attribute' => $matches[1],
            'format' => isset($matches[3]) ? $matches[3] : null,
            'label' => isset($matches[5]) ? $matches[5] : null,
        ];
    }

    protected function beforeFilterApply()
    {
        $event = new Event();
        $this->trigger(self::EVENT_BEFORE_FILTER_APPLY, $event);
    }
}