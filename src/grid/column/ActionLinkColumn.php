<?php
namespace Crud\grid\column;

use yii\helpers\Html;

/**
 * [[GridView]] widget that displays buttons for viewing and manipulating the items.
 *
 */
class ActionLinkColumn extends \yii\grid\DataColumn
{
    use ActionLinkTrait, TruncateColumnTrait;

    /**
     * @var string
     */
    public $action = 'update';

    /**
     * @var string
     */
    public $linkText;

    /**
     * @var string
     */
    public $truncateClass;

    /**
     * @var string
     */
    public $addLinkClass;

    /**
     * @var boolean
     */
    public $addHash = true;

    protected function renderDataCellContent($model, $key, $index)
    {
        if (null !== $this->linkText) {
            $text = $this->linkText;
        } else {
            $text = parent::renderDataCellContent($model, $key, $index);
        }

        if (!$this->checkPermission($model, $key, $index)) {
            return $text;
        }

        $str = '';
        if ($this->addHash) {
            $str .= Html::a('', null, ['name' => $index]);
        }

        $action = $this->getAction($model, $key, $index);
        $url = $this->createUrl($action, $model, $key, $index);

        $attrs = [];
        if ($this->addLinkClass) {
            Html::addCssClass($attrs, $this->addLinkClass);
        }

        $str .= Html::a($text, $url, $attrs);
        return $this->truncateContent($str);
    }

    protected function getAction($model, $key, $index)
    {
        if (is_callable($this->action)) {
            return call_user_func($this->action, $model, $key, $index, $this);
        }

        return $this->action;
    }
}