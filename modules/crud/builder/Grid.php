<?php
namespace app\modules\crud\builder;

use yii\db\ActiveRecord;
use yii\validators\BooleanValidator;
use yii\validators\FileValidator;

/**
 * XXX
 *
 */
class Grid {
    public $columns;
    public $skipColumnsInGrid;
    public $columnFormats;
    public $gridWithEditLink = true;

    public function build($modelClass) {
        $this->dbColumns = $modelClass::getTableSchema()->columns;
        $model = null;

        if (null === $this->columns) {
            $this->columns = [];
            foreach ($this->getDefaultColumns($modelClass) as $column) {
                $this->columns[$column] = ['attribute' => $column];
            }
        }

        foreach ($this->columns as $column => $desc) {
            $attribute = null;
            if (is_array($desc) && isset($desc['attribute'])) {
                $attribute = $desc['attribute'];
            } elseif (is_string($desc)) {
                $attribute = $desc;
            }

            if (null === $attribute) {
                continue;
            }

            $this->uptakeNameAttr($attribute);

            $format = null;
            if (isset($this->columnFormats[$attribute])) {
                $format = $this->columnFormats[$attribute];
            } else {
                if (!$model) {
                    $model = new $modelClass();
                }

                switch ($this->getControlTypeByValidator($model, $attribute)) {
                    case 'boolean':
                        $format = 'boolean';
                        break;
                }
            }

            if (null === $format) {
                continue;
            }

            if (is_string($desc)) {
                $desc = ['attribute' => $desc];
            }
            $desc['format'] = $format;
            $this->columns[$column] = $desc;
        }

        if ($this->gridWithEditLink && $this->nameAttr) {
            $this->makeGridEditLink();
        }
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

        $desc['class'] = 'app\modules\crud\grid\ActionLinkColumn';
        $this->columns[$targetColumn] = $desc;
    }

    protected function getDefaultColumns($modelClass) {
        if (null === $this->skipAttributesInGrid) {
            $this->skipAttributesInGrid = [];
        }

        if ($this->attributes) {
            return array_diff($this->attributes, $this->skipColumnsInGrid);
        }

        $keys = $modelClass::primaryKey();
        $columns = array_keys($this->dbColumns);
        return array_diff($columns, $keys, $this->skipColumnsInGrid);
    }
}