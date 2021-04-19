<?php
namespace app\modules\crud\grid\column;

use yii\helpers\Html;

/**
 * [[GridView]] widget that displays buttons for viewing and manipulating the items.
 *
 */
class ActionLinkColumn extends \yii\grid\DataColumn
{
    use ActionLinkTrait;

    /**
     * @var string
     */
    public $linkText;

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
        return Html::a($text, $url);
    }
}