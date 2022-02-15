<?php
namespace app\modules\crud\grid\column;

use Yii;
use yii\helpers\ArrayHelper;

use app\modules\crud\controls\Base;

use app\modules\crud\grid\toolbar\Link;

/**
 *
 */
class MenuColumn extends \yii\grid\Column
implements \app\modules\crud\controls\CopyMessageCategoryInterface
{
    public $items = [];

    public $itemOptions = [];

    public $messageCategory;

    protected $itemClass = Link::class;

    protected static $defItemOptions = [
        'sizeClass' => 'btn-xs',
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