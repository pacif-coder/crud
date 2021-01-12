<?php
namespace app\modules\crud\grid\column;

use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\grid\DataColumn;

use app\modules\crud\behaviors\BackUrlBehavior;

/**
 * [[GridView]] widget that displays buttons for viewing and manipulating the items.
 *
 */
class ActionLinkColumn extends DataColumn {
    /**
     * @var string
     */
    public $action = 'update';

    /**
     * @var array
     */
    public $removeParams = ['sort'];

    /**
     * Add 'back-url' param in url
     * @var boolean
     */
    public $backUrl = true;

    /**
     * @var string
     */
    public $urlKey = 'id';

    /**
     * @var string
     */
    public $linkText;

    /**
     * @var string the ID of the controller that should handle the actions specified here.
     * If not set, it will use the currently active controller. This property is mainly used by
     * [[urlCreator]] to create URLs for different actions. The value of this property will be prefixed
     * to each action name to form the route of the action.
     */
    public $controller;

    /**
     * @var callable a callback that creates a button URL using the specified model information.
     * The signature of the callback should be the same as that of [[createUrl()]]
     * Since 2.0.10 it can accept additional parameter, which refers to the column instance itself:
     *
     * ```php
     * function (string $action, mixed $model, mixed $key, integer $index, ActionColumn $this) {
     *     //return string;
     * }
     * ```
     *
     * If this property is not set, button URLs will be created using [[createUrl()]].
     */
    public $urlCreator;

    /**
     * @var callable a callback that creates

     *
     * ```php
     * function (string $action, mixed $model, mixed $key, integer $index, ActionColumn $this) {
     *     //return string;
     * }
     * ```
     *
     */
    public $checkPermission = null;

    /**
     * Creates a URL for the given action and model.
     * This method is called for each button and each row.
     *
     * @param string $action the button name (or action ID)
     * @param \yii\db\ActiveRecordInterface $model the data model
     * @param mixed $key the key associated with the data model
     * @param int $index the current row index
     * @return string the created URL
     */
    public function createUrl($action, $model, $key, $index) {
        if (is_callable($this->urlCreator)) {
            return call_user_func($this->urlCreator, $action, $model, $key, $index, $this);
        }

        $params = Yii::$app->request->get();
        foreach ($this->removeParams as $removeParam) {
            if (isset($params[$removeParam])) {
                unset($params[$removeParam]);
            }
        }

        if (is_array($key)) {
            $params = ArrayHelper::merge($params, $key);
        } else {
            $params[$this->urlKey] = (string) $key;
        }

        $params[0] = $this->controller ? $this->controller . '/' . $action : $action;

        if ($this->backUrl) {
            $params = BackUrlBehavior::addBackUrl($params);
        }

        return Url::toRoute($params);
    }

    protected function renderDataCellContent($model, $key, $index) {
        if (null !== $this->linkText) {
            $text = $this->linkText;
        } else {
            $text = parent::renderDataCellContent($model, $key, $index);
        }

        $withLink = true;
        if (null !== $this->checkPermission) {
            if ($this->checkPermission instanceof \Closure) {
                $withLink = call_user_func($this->checkPermission, $this->action, $model, $key, $index, $this);
            } else {
                $withLink = $this->checkPermission;
            }
        }

        if (!$withLink) {
            return $text;
        }

        $url = $this->createUrl($this->action, $model, $key, $index);
        return Html::a($text, $url);
    }
}