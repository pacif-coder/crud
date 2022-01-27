<?php
namespace app\modules\crud\grid\column;

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

        $url = $this->createUrl($this->action, $model, $key, $index);

        $attrs = [];
        if ($this->addLinkClass) {
            Html::addCssClass($attrs, $this->addLinkClass);
        }

        $str = Html::a($text, $url, $attrs);
        return $this->truncateContent($str);
    }
}