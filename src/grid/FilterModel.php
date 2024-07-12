<?php
namespace Crud\grid;

use yii\base\DynamicModel;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\db\QueryInterface;

use Crud\builder\GridBuilder;

class FilterModel extends DynamicModel
{
    /**
     * @var null|array
     *
     * Also, if [[label]] is not specified, the label associated with the attribute will be displayed.
     */
    public $filterAttrs;
    public $addFilterAttrs = [];
    public $removeFilterAttrs = [];
    public $filterAttrOperator = [];
    public $filterOnlyIndexed = true;
    public $noApplyFilterAttrs = [];
    public $transformAttrMap = [];
    public $forceFilterFormName;

    protected $_formName;
    protected $_modelTable;
    protected $_isLoaded;

    public function builder2this(GridBuilder $builder)
    {
        $builderVars = array_keys(get_object_vars($builder));
        $thisVars = array_keys(get_object_vars($this));
        foreach (array_intersect($builderVars, $thisVars) as $param) {
            $this->{$param} = $builder->{$param};
        }

        if (null !== $this->filterAttrs || !$builder->filterInGrid) {
            return;
        }

        $this->filterAttrs = [];
        foreach ($builder->columns as $desc) {
            if (!isset($desc['attribute'])) {
                continue;
            }

            if (isset($desc['filter']) && !$desc['filter']) {
                continue;
            }

            $this->filterAttrs[] = $desc['attribute'];
        }
    }

    public function setModel(Model $model)
    {
        $this->_formName = $model->formName();
        $this->_isLoaded = null;

        if (null === $this->filterAttrs) {
            $filterAttrs = $model->attributes();
        } else {
            $filterAttrs = $this->filterAttrs;
        }

        $isActiveRecord = $model instanceof ActiveRecord;
        if ($isActiveRecord) {
            $modelClass = get_class($model);
            $this->_modelTable = $modelClass::tableName();
        }

        if ($this->filterOnlyIndexed && $isActiveRecord) {
            $indexed = [];

            $schema = $modelClass::getDb()->getSchema();
            foreach($schema->getTableIndexes($this->_modelTable) as $indexes) {
                $indexed = array_merge($indexed, $indexes->columnNames);
            }

            foreach($schema->getTableForeignKeys($this->_modelTable) as $indexes) {
                $indexed = array_merge($indexed, $indexes->columnNames);
            }

            $filterAttrs = array_intersect($filterAttrs, $indexed);
        }

        $filterAttrs = array_merge($filterAttrs, $this->addFilterAttrs);
        $this->filterAttrs = array_diff($filterAttrs, $this->removeFilterAttrs);

        foreach ($this->filterAttrs as $attr) {
            $this->defineAttribute($attr);
            $this->addRule($attr, 'safe');
        }
    }

    public function load($data, $formName = null)
    {
        return $this->_isLoaded = parent::load($data, $formName);
    }

    public function isLoaded()
    {
        return $this->_isLoaded;
    }

    public function filter(QueryInterface $query)
    {
        foreach ($this->filterAttrs as $attr) {
            if (in_array($attr, $this->noApplyFilterAttrs)) {
                continue;
            }

            $operator = isset($this->filterAttrOperator[$attr])? $this->filterAttrOperator[$attr] : '=';
            $condAttr = $this->transformAttrMap[$attr]?? $attr;
            $val = $this->{$attr};


            if ('=' == $operator && is_array($val)) {
                $operator = 'IN';
            }

            $query->andFilterWhere([$operator, $condAttr, $val]);
        }
    }

    public function formName()
    {
        if ($this->forceFilterFormName) {
            return $this->forceFilterFormName;
        }

        return $this->_formName;
    }

    public function activeAttributes()
    {
        return $this->attributes();
    }
}