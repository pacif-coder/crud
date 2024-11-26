<?php
namespace Crud\action;

use Yii;
use yii\web\Response;

use Crud\builder\GridBuilder;
use Crud\export\GridView2Array;
use Crud\helpers\ModelName;
use Crud\models\ClassType;

use ReflectionClass;
use ReflectionProperty;

/**
 *
 *
 */
class TreeMenuBranchLoadAction extends \yii\base\Action
{
    /**
     * @return string
     */
    public function run()
    {
        $builder = $this->controller->getGridBuilder();
        $builder->addDragIconColumn = false;
        $builder->gridClass = GridView2Array::class;

        $nameAttr = ModelName::getNameAttr($this->controller->modelClass);
        $builder->columns = [$nameAttr];
        $builder->gridType = GridBuilder::TYPE_DEFAULT;
        $builder->pageSize = false;
        $this->controller->buildGrid();

        $options = $builder->getOptions();

        $clearOptions = [];
        $ref = new ReflectionClass($builder->gridClass);
        foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            if (!array_key_exists($prop->name, $options)) {
                continue;
            }

            $clearOptions[$prop->name] = $options[$prop->name];
        }
        $clearOptions['class'] = $builder->gridClass;

        $grid = Yii::createObject($clearOptions);

        $type = ClassType::getTypeByClass($this->controller->modelClass);
        $isFolder = ClassType::isFolderByType($type);

        $result = [];
        foreach ($grid->asArray() as $index => $row) {
            $column = $row[$nameAttr];

            $column['isFolder'] = $isFolder;

            if (!isset($column['link']) || !($query = parse_url($column['link'], PHP_URL_QUERY))) {
                $result[$index] = $column;
                continue;
            }

            $get = [];
            parse_str($query, $get);
            if (isset($get['id'])) {
                $column['id'] = $get['id'];
            }

            if (isset($get['type'])) {
                $column['type'] = $get['type'];
            }

            $result[$index] = $column;
        }

        $this->controller->response->format = Response::FORMAT_JSON;
        return $result;
    }
}