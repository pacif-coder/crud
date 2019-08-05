<?php
namespace app\modules\crud\builder;

use yii\db\ActiveRecord;
use yii\validators\BooleanValidator;
use yii\validators\FileValidator;
use app\modules\crud\builder\Base;
use app\modules\crud\grid\column\ActionLinkColumn;

/**
 * XXX
 *
 */
class GridBuilder extends Base {
    public $columns;
    public $columnFormats;
    public $skipColumnsInGrid;

    public $gridWithEditLink = true;

    protected $_model = null;

    public function build($modelClass) {
        $this->dbColumns = $modelClass::getTableSchema()->columns;

        if (null === $this->columns) {
            $this->columns = [];
            foreach ($this->getDefaultColumns($modelClass) as $column) {
                $this->columns[$column] = ['attribute' => $column];
            }
        } else {
            $columns = [];
            foreach ($this->columns as $column => $desc) {
                if (is_string($desc)) {
                    $columns[$desc] = $this->parseColumnDesc($desc);
                } else {
                    $columns[$column] = $desc;
                }
            }

            $this->columns = $columns;
        }

        $model = null;
        foreach ($this->columns as $column => $desc) {
            if (isset($desc['format'])) {
                continue;
            }

            $attr = null;
            if (is_array($desc) && isset($desc['attribute'])) {
                $attr = $desc['attribute'];
            }

            if (null === $attr) {
                continue;
            }

            $this->uptakeNameAttr($attr);

            $format = $this->getColumnFormat($attr, $modelClass);
            if (null === $format) {
                continue;
            }

            $desc['format'] = $format;
            $this->columns[$column] = $desc;
        }

        if ($this->gridWithEditLink && $this->nameAttr) {
            $this->makeGridEditLink();
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

        if (isset($this->dbColumns[$attr])) {
            $column = $this->dbColumns[$attr];

            /**@var $column \yii\db\ColumnSchema  **/
            if ('text' == $column->dbType) {
                return 'ntext';
            }
        }

        return 'text';
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
        if (null === $this->skipColumnsInGrid) {
            $this->skipColumnsInGrid = [];
        }

        if ($this->fields) {
            return array_diff($this->fields, $this->skipColumnsInGrid);
        }

        $keys = $modelClass::primaryKey();
        $columns = array_keys($this->dbColumns);
        return array_diff($columns, $keys, $this->skipColumnsInGrid);
    }
}