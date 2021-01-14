<?php
namespace app\modules\crud\builder;

use Yii;
use yii\base\Event;
use yii\data\ActiveDataProvider;
use yii\validators\ExistValidator;

use app\modules\crud\builder\Base;
use app\modules\crud\models\ModelWithParentInterface;
use app\modules\crud\grid\column\ActionLinkColumn;
use app\modules\crud\grid\FilterModel;
use app\modules\crud\helpers\ModelName;
use app\modules\crud\helpers\ParentModel;

/**
 * XXX
 *
 */
class GridBuilder extends Base {
    public $columns;
    public $columnFormats;

    public $addColumnsAfter = [];

    public $defaultOrder;
    public $pageSize;
    public $gridWithEditLink = true;
    public $gridOptions = [];

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

    protected $_extraControlVar = 'grid';

    protected static $_autoJoinI = 1;

    public function build($modelClass = null) {
        $this->_isExtraControlCreated = false;
        $this->_transformSortAttrMap = $this->_transformFilterAttrMap = [];

        if ($modelClass && $modelClass != $this->modelClass) {
            $this->setModelClass($modelClass);
        }

        $this->beforeBuild();
        $this->initNameAttr();

        if (null === $this->columns) {
            $this->columns = [];
            foreach ($this->getDefaultColumns($this->modelClass) as $column) {
                $this->columns[$column] = ['attribute' => $column];
            }
        } else {
            $this->columns = $this->parseColumns($this->columns);
        }

        $attrInColumns = array_keys($this->columns);
        foreach ($this->addColumnsAfter as $afterAttr => $columns) {
            if (!in_array($afterAttr, $attrInColumns)) {
                continue;
            }

            $index = array_search($afterAttr, $attrInColumns);
            if (false === $index) {
                continue;
            }

            array_splice($this->columns, $index + 1, 0, [$columns]);
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

        // define column format
        foreach ($this->columns as $column => $desc) {
            if (isset($desc['format'])) {
                continue;
            }

            $attr = isset($desc['attribute'])? $desc['attribute'] : null;
            if (null === $attr) {
                continue;
            }

            $format = $this->getColumnFormat($attr, $this->modelClass);
            if (null !== $format) {
                $this->columns[$column]['format'] = $format;
            }
        }

        if ($this->gridWithEditLink && $this->nameAttr) {
            $this->makeGridEditLink();
        }

        $this->fixSort();

        $this->createFilter();
        $this->filterApply();

        $this->createExtraControls();

        $this->afterBuild();
    }

    protected function filterApply() {
        if (!$this->filterModel) {
            return;
        }

        $this->filterModel->transformAttrMap = $this->_transformFilterAttrMap;
        $this->beforeFilterApply();

        $query = $this->getQuery();
        $this->filterModel->filter($query);
    }

    public function getFilter() {
        return $this->filterModel;
    }

    protected function createFilter() {
        if (!$this->withFilter) {
            return;
        }

        $this->filterModel = new FilterModel();
        $this->filterModel->builder2this($this);
        $this->filterModel->setModel(Yii::createObject($this->modelClass));
        $this->filterModel->load(Yii::$app->request->get());
    }

    public function getProvider() {
        if (null !== $this->provider) {
            return $this->provider;
        }

        $options = [
            'query' => $this->getQuery(),
        ];

        if (null !== $this->defaultOrder) {
            $options['sort']['defaultOrder'] = $this->defaultOrder;
        } elseif ($this->nameAttr) {
            $options['sort']['defaultOrder'] = [$this->nameAttr => SORT_ASC];
        }

        if (null !== $this->pageSize) {
            $options['pagination']['pageSize'] = $this->pageSize;
        }

        return $this->provider = new ActiveDataProvider($options);
    }

    protected function fixSort() {
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

    public function &getOptions() {
        if ($this->_isChangeGridOption) {
            return $this->gridOptions;
        }

        $this->_isChangeGridOption = true;
        $this->gridOptions['columns'] = $this->columns;
        $this->gridOptions['addToolbarButtons'] = $this->addToolbarButtons;
        $this->gridOptions['removeToolbarButtons'] = $this->removeToolbarButtons;
        $this->gridOptions['toolbarButtonOptions'] = $this->toolbarButtonOptions;
        $this->gridOptions['dataProvider'] = $this->getProvider();

        $this->gridOptions['isInsideForm'] = $this->isInsideForm;
        $this->gridOptions['surroundForm'] = $this->surroundForm;
        $this->gridOptions['surroundFormAction'] = $this->surroundFormAction;
        $this->gridOptions['surroundFormMethod'] = $this->surroundFormMethod;
        $this->gridOptions['surroundFormOptions'] = $this->surroundFormOptions;

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
        $this->query = $modelClass::find()->asArray(true);

        $parentModelAttr = ParentModel::getParentModelAttr($modelClass);
        if ($parentModelAttr) {
            $table = $modelClass::tableName();
            $column = "[[{$table}]].[[{$parentModelAttr}]]";
            $this->query->andWhere([$column => $this->parentModelID]);
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

            $this->_createTmpModel();
            $this->initValidators($this->_model);

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

            $this->_createTmpModel();
            $this->initValidators($this->_model);

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

    protected function getColumnFormat($attr)
    {
        if (isset($this->columnFormats[$attr])) {
            return $this->columnFormats[$attr];
        }

        $this->_createTmpModel();

        $format = $this->getControlTypeByValidator($this->_model, $attr);
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
            if ('text' == $column->dbType) {
                return 'ntext';
            }
        }

        // enum value in column
        if (isset($this->enumOptions[$attr])) {
            $this->initEnumOptionsByDesc($this->_model, $attr);
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
        foreach ($this->columns as $column => $desc) {
            $attr = isset($desc['attribute'])? $desc['attribute'] : null;
            if (null === $attr) {
                continue;
            }

            if ($this->nameAttr == $attr) {
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

        $desc['class'] = ActionLinkColumn::class;
        $this->columns[$targetColumn] = $desc;
    }

    protected function _createTmpModel()
    {
        if ($this->_model) {
            return;
        }

        $this->_model = $this->modelClass::instantiate(null);
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