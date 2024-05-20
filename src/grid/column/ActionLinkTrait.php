<?php
namespace Crud\grid\column;

use Yii;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;

use Crud\behaviors\BackUrlBehavior;

/**
 * [[GridView]] widget that displays buttons for viewing and manipulating the items.
 *
 */
trait ActionLinkTrait
{
    /**
     * @var string
     */
    public $action = 'update';

    /**
     * @var array
     */
    public $removeParams = ['sort', 'page'];

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
     * @var boolean
     */
    public $addHash = true;

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
    public function createUrl($action, $model, $key, $index, $backUrl)
    {
        if (is_callable($this->urlCreator)) {
            return call_user_func($this->urlCreator, $action, $model, $key, $index, $backUrl, $this);
        }

        $params = $this->getUrlParams($action, $model, $key, $index);
        if ($backUrl) {
            $params = $this->addBackUrl($params, $index);
        }

        return Url::toRoute($params);
    }

    protected function getUrlParams($action, $model, $key, $index)
    {
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

        $controller = $this->getController($model);
        $params[0] = $controller? "{$controller}/{$action}" : $action;

        return $params;
    }

    protected function addBackUrl($params, $index)
    {
        $hash = $this->addHash? $index : null;
        return BackUrlBehavior::addBackUrl($params, $hash);
    }

    protected function checkPermission($model, $key, $index)
    {
        if (null === $this->checkPermission) {
            return true;
        }

        if ($this->checkPermission instanceof \Closure) {
            return call_user_func($this->checkPermission, $this->action, $model, $key, $index, $this);
        }

        return $this->checkPermission;
    }

    protected function getController($model)
    {
        if ($this->controller) {
            return $this->controller;
        }

        if (!Yii::$app->has('class2controller')) {
            return;
        }

        $modelClass = $this->grid->dataProvider->query->modelClass;
        return Yii::$app->class2controller->getController($modelClass);
    }
}