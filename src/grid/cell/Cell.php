<?php
namespace Crud\grid\cell;

use yii\helpers\Html;

use Crud\helpers\ModelName;
use Crud\grid\column\ActionLinkTrait;

/**
 */
class Cell extends \ThumbOnDemand\widgets\Cell
{
    use ActionLinkTrait;

    public $withLink = true;

    protected function renderName($model, $key, $index)
    {
        $str = $this->getName($model, $key, $index);

        if ($this->withLink($model, $key, $index)) {
            $url = $this->createUrl($this->action, $model, $key, $index);
            $str = Html::a($str, $url);
        }

        $text = Html::tag('div', $str, ['class' => 'text']);
        return Html::tag('div', $text, ['class' => 'title']);
    }

    protected function withLink($model, $key, $index)
    {
        return $this->withLink && $this->checkPermission($model, $key, $index);
    }

    protected function getName($model, $key, $index)
    {
        if (is_object($model)) {
            return ModelName::getName($model);
        }

        if (is_array($model) && $this->modelClass) {
            return ModelName::getNameByClass($this->modelClass, $model);
        }
    }
}
