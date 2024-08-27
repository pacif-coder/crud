<?php
namespace Crud\grid\column;

use Yii;
use yii\helpers\ArrayHelper;

use Crud\controls\Base;
use Crud\grid\toolbar\Link;

use Closure;

/**
 *
 */
class MenuColumn extends \yii\grid\Column
implements \Crud\controls\CopyMessageCategoryInterface
{
    public $items = [];

    public $itemOptions = [];

    public $messageCategory;

    protected $itemClass = Link::class;

    protected static $defItemOptions = [
        'size' => 'small',
        'colorClass' => 'btn-success',
        'baseClass' => 'btn btn-block',
    ];

    /**
     * @var string
     */
    public $urlKey = 'id';

    public function init()
    {
        parent::init();

        $this->itemOptions = ArrayHelper::merge(static::$defItemOptions,
                             $this->itemOptions);
    }

    protected function renderDataCellContent($model, $key, $index)
    {
        if ($this->content !== null) {
            return parent::renderDataCellContent($model, $key, $index);
        }

        $str = '';
        foreach ($this->items as $item) {
            $item = $this->createItem($item, $key);

            $item = Yii::createObject($item);

            $item->columnData2this($model, $key, $index);

            /* @var $item Base */
            if ($item->isShow()) {
                $str .= (string) $item;
            }
        }

        return $str;
    }

    protected function createItem($item, $key)
    {
        if (is_object($item)) {
            return $item;
        }

        if (is_string($item)) {
            $item = ['action' => $item];
        }

        if (is_callable($item) || $item instanceof Closure) {
            $item = call_user_func($item, $key, $this);
        }

        if (!isset($item['class'])) {
            $item['class'] = $this->itemClass;
        }

        $item['messageCategory'] = $this->messageCategory;

        $item = ArrayHelper::merge($item, $this->itemOptions);

        if (!isset($item['params'])) {
            $item['params'] = [];
        }

        if (is_array($key)) {
            $item['params'] = ArrayHelper::merge($item['params'], $key);
        } else {
            $urlKey = $this->urlKey;
            if (isset($item['urlKey'])) {
                $urlKey = $item['urlKey'];

                unset($item['urlKey']);
            }

            $item['params'][$urlKey] = (string) $key;
        }

        return $item;
    }
}