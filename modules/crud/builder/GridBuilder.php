<?php
namespace app\modules\crud\builder;

use Yii;
use yii\data\ActiveDataProvider;
use yii\base\Event;

use app\modules\crud\builder\Base;
use app\modules\crud\grid\column\ActionLinkColumn;
use app\modules\crud\grid\FilterModel;
use yii\validators\ExistValidator;

/**
 * XXX
 *
 */
class GridBuilder extends Base {
    public $columns;
    public $columnFormats;

    public $gridDefaultOrder;
    public $gridWithEditLink = true;

    public $addToolbarButtons = [];
    public $removeToolbarButtons = [];
    public $toolbarButtonOptions = [];

    public $withFilter = false;
    public $filterInGrid = true;
    public $filterAttrs;
    public $addFilterAttrs = [];
    public $removeFilterAttrs = [];
    public $filterAttrOperator = [];
    public $filterOnlyIndexed = true;
    public $noApplyAttrs = [];

    public $autoJoin = true;

    /**
     * @event Event
     */
    const EVENT_BEFORE_FILTER_APPLY = 'beforeFilterApply';

    protected $query;
    protected $provider;
    protected $gridOptions;
    protected $filterModel;

    protected $_model;
    protected $_transformSortAttrMap = [];
    protected $_transformFilterAttrMap = [];

    public function controller2this($controller) {
        if (isset($controller->modelClass)) {
            $this->setModelClass($controller->modelClass);
        }

        $this->object2this($controller);
    }

    public function setModelClass($modelClass) {
        $this->modelClass = $modelClass;

        $this->static2this($modelClass, 'fb_');
    }

    public function build($modelClass = null) {
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

        $this->autoJoin();
        $this->selectInFilter();

        $dbColumns = $this->getDBColumns($this->modelClass);
        foreach ($this->columns as $desc) {
            $attr = isset($desc['attribute'])? $desc['attribute'] : null;
            if (null === $attr) {
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

        $this->createFilter();
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

        $this->filterApply();

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

    protected function getProvider() {
        if (null !== $this->provider) {
            return $this->provider;
        }

        $options = [
            'query' => $this->getQuery(),
        ];

        if (null !== $this->gridDefaultOrder) {
            $options['sort']['defaultOrder'] = $this->gridDefaultOrder;
        } elseif ($this->nameAttr) {
            $options['sort']['defaultOrder'] = [$this->nameAttr => SORT_ASC];
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
        if (null !== $this->gridOptions) {
            return $this->gridOptions;
        }

        $this->gridOptions = [
            'showHeader' => true,
            'columns' => $this->columns,
            'addToolbarButtons' => $this->addToolbarButtons,
            'removeToolbarButtons' => $this->removeToolbarButtons,
            'toolbarButtonOptions' => $this->toolbarButtonOptions,
            'dataProvider' => $this->getProvider(),
        ];

        if ($this->filterModel && $this->filterInGrid) {
            $this->gridOptions['filterModel'] = $this->filterModel;
        }

        return $this->gridOptions;
    }

    public function getQuery() {
        if ($this->query) {
            return $this->query;
        }

        $modelClass = $this->modelClass;
        return $this->query = $modelClass::find();
    }

    protected function selectInFilter() {
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

            if (!$this->_model) {
                $this->_model = new $this->modelClass();
                $this->initValidators($this->_model);
            }

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

    protected function autoJoin() {
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

            if (!$this->_model) {
                $this->_model = new $this->modelClass();
                $this->initValidators($this->_model);
            }

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

    protected function linkQueryByExistValidator($validator, $attr) {
        /* @var $validator ExistValidator */
        $targetModelClass = $validator->targetClass;
        $nameAttr = $this->getNameAttr($targetModelClass);

        if (!$nameAttr) {
            return;
        }

        /* @var $query \yii\db\ActiveQuery */
        $query = $this->getQuery();

        $joinToTable = $targetModelClass::tableName();
        $table = $query->modelClass::tableName();
        $joinAttr = $validator->targetAttribute[$attr];
        $on = "{$joinToTable}.[[{$joinAttr}]] = {$table}.[[{$attr}]]";

        $query->join('left join', $joinToTable, $on);
        if (!$query->select) {
            $query->addSelect("{$table}.*");
        }

        $query->addSelect("{$joinToTable}.[[{$nameAttr}]] as [[{$attr}]]");
        $this->_transformSortAttrMap[$attr] = "{$joinToTable}.[[{$nameAttr}]]";

        $joinTableAttrs = array_keys($this->getDBColumns($targetModelClass));
        $tableAttrs = array_keys($this->getDBColumns($this->modelClass));
        $intersect = array_intersect($joinTableAttrs, $tableAttrs);
        if (!$intersect) {
            return;
        }

        foreach ($intersect as $attr) {
            $this->_transformFilterAttrMap[$attr] = "{$table}.[[{$attr}]]";
        }
    }

    protected function getColumnFormat($attr, $modelClass) {
        if (isset($this->columnFormats[$attr])) {
            return $this->columnFormats[$attr];
        }

        if (!$this->_model) {
            $this->_model = new $modelClass();
        }

        $format = $this->getControlTypeByValidator($this->_model, $attr);
        switch ($format) {
            case 'email':
            case 'boolean':
                return $format;
                break;
        }

        $dbColumns = $this->getDBColumns($modelClass);
        if (isset($dbColumns[$attr])) {
            $column = $dbColumns[$attr];

            /**@var $column \yii\db\ColumnSchema  **/
            if ('text' == $column->dbType) {
                return 'ntext';
            }
        }

        return 'text';
    }

    protected function makeGridEditLink() {
        $targetColumn = null;
        foreach ($this->columns as $column => $desc) {
            $columnName = null;
            if (is_array($desc) && isset($desc['attribute'])) {
                $columnName = $desc['attribute'];
            } elseif (is_string($desc)) {
                $columnName = $desc;
            }

            if (!$columnName) {
                continue;
            }

            if ($this->nameAttr == $columnName) {
                $targetColumn = $column;
                break;
            }
        }

        if (null === $targetColumn) {
            return;
        }

        $desc = $this->columns[$targetColumn];
        if (is_array($desc) && isset($desc['class'])) {
            return;
        }

        if (is_string($desc)) {
            $desc = ['attribute' => $desc];
        }

        $desc['class'] = ActionLinkColumn::className();
        $this->columns[$targetColumn] = $desc;
    }

    protected function getDefaultColumns($modelClass) {
        return $this->_getDefaultColumns($modelClass, $this->fields, $this->skipColumnsInGrid);
    }

    protected function beforeFilterApply() {
        $event = new Event();
        $this->trigger(self::EVENT_BEFORE_FILTER_APPLY, $event);
    }
}