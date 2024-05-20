<?php
namespace Crud\grid\column;

use Crud\helpers\Html;

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

    /**
     * @var string
     */
    public $secondAction;

    /**
     * Add 'back-url' param in url
     * @var boolean
     */
    public $secondActionBackUrl;

    /**
     *
     * @var string
     */
    public $secondActionIcon = 'pencil-square';

    /**
     *
     * @var string
     */
    public $secondActionClass = 'text-success';

    protected $secondActionContAttrs = [
        'class' => 'action-link-column-with-second-link',
    ];

    protected function renderDataCellContent($model, $key, $index)
    {
        $array = $this->asArray($model, $key, $index);

        // only text - no link
        if (isset($array['text']) && count($array) == 1) {
            return $array;
        }

        // create main link
        $attrs = $this->addLinkClass? ['class' => $this->addLinkClass] : [];
        $link = Html::a($array['text'], $array['link'], $attrs);
        if (!isset($array['secondLink'])) {
            return $this->surround($link, $index);
        }

        // create second link
        $icon = $this->getSecondActionIcon($model, $key, $index);
        $secondLink = Html::a($icon, $array['secondLink']);

        // create block with main and second link
        $str = Html::tag('div', $link . $secondLink, $this->secondActionContAttrs);
        return $this->surround($str, $index);
    }

    public function asArray($model, $key, $index)
    {
        $asArray = [];
        if (null !== $this->linkText) {
            $text = $this->linkText;
        } else {
            $text = parent::renderDataCellContent($model, $key, $index);
        }
        $asArray['text'] = $text;

        if (!$this->checkPermission($model, $key, $index)) {
            return $asArray;
        }

        $action = $this->getAction($model, $key, $index);
        $asArray['link'] = $this->createUrl($action, $model, $key, $index, $this->backUrl);

        if (!$this->secondAction) {
            return $asArray;
        }

        $secondAction = $this->getSecondAction($model, $key, $index);
        if ($action == $secondAction) {
            return $asArray;
        }

        $asArray['secondLink'] = $this->createUrl($secondAction, $model, $key, $index, $this->secondActionBackUrl);
        return $asArray;
    }

    protected function getAction($model, $key, $index)
    {
        if (is_callable($this->action)) {
            return call_user_func($this->action, $model, $key, $index, $this);
        }

        return $this->action;
    }

    protected function getSecondAction($model, $key, $index)
    {
        if (is_callable($this->secondAction)) {
            return call_user_func($this->secondAction, $model, $key, $index, $this);
        }

        return $this->secondAction;
    }

    protected function getSecondActionIcon($model, $key, $index)
    {
        return Html::icon($this->secondActionIcon, $this->secondActionClass);
    }

    protected function surround($str, $index)
    {
        $str = $this->truncateContent($str);

        if ($this->addHash) {
            $str = Html::a('', null, ['name' => $index]) . $str;
        }

        return $str;
    }
}