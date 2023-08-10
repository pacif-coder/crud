<?php
namespace Crud\widgets;

use Yii;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;

use Crud\behaviors\BackUrlBehavior;
use Crud\helpers\ClassI18N;
use Crud\helpers\Lang;
use Crud\helpers\ModelName;
use Crud\helpers\ParentModel;
use Crud\models\ModelWithParentInterface;

/**
 *
 */
class Breadcrumbs extends \yii\base\BaseObject
{
    public $withHome = true;

    public $withBegin;

    public $lastUrl;

    public $removeBackUrl = true;

    public $removeGridParam = true;

    public $removeParams = [];

    public $links = [];

    protected $urlParams;

    public function createIndexBreadcrumbs($model)
    {
        return $this->_make($model, false);
    }

    public function createEditBreadcrumbs($model)
    {
        return $this->_make($model, true);
    }

    public function _make($model, $includeLast = true)
    {
        $parents = [];
        if (is_a($model, ModelWithParentInterface::class)) {
            $parents = ParentModel::loadParents($model);
        }

        if ($this->withBegin) {
            $modelData = $this->model2data($model);
            $url = $this->getUrlToModel($modelData, true);

            $messageCategory = ClassI18N::class2messagesPath($modelData['class']);
            $label = Lang::t($messageCategory, 'Top list items');

            $this->addLink($url, $label);
        }

        end($parents);
        $lastIndex = key($parents);
        foreach ($parents as $i => $modelData) {
            if ($lastIndex != $i) {
                $this->modelData2breadcrumbs($modelData);
                continue;
            }

            if (!$includeLast) {
                break;
            }

            if ($this->lastUrl) {
                $this->addLink($this->lastUrl, $modelData['name']);
            } else {
                $this->modelData2breadcrumbs($modelData);
            }
        }

        return $this->links;
    }

    public function modelData2breadcrumbs($modelData, $dropID = false)
    {
        $url = $this->getUrlToModel($modelData, $dropID);
        $this->addLink($url, $modelData['name']);
    }

    public function getUrlToModel($modelData, $dropID = false, $params = [])
    {
        $urlParams = $this->initUrlParams();
        if (!$dropID) {
            $urlParams['id'] = $modelData['id'];
        } elseif (isset($urlParams['id'])) {
            unset($urlParams['id']);
        }

        if (Yii::$app->has('class2controller')) {
            $controller = Yii::$app->class2controller->getController($modelData['class']);
            if ($controller) {
                $urlParams[0] = "{$controller}/{$urlParams[0]}";
            }
        }

        $urlParams = ArrayHelper::merge($urlParams, $params);

        return Url::toRoute($urlParams);
    }

    public function addLink($url, $label)
    {
        $this->links[] = (object) [
            'url' => $url,
            'label' => $label,
        ];
    }

    protected function model2data($model)
    {
        return [
            'class' => get_class($model),
            'name' => ModelName::getName($model),
            'id' => $model->id,
        ];
    }

    protected function initUrlParams()
    {
        if (null !== $this->urlParams) {
            return $this->urlParams;
        }

        $urlParams = Yii::$app->request->get();

        $removeParams = $this->removeParams;
        if ($this->removeBackUrl) {
            $removeParams[] = BackUrlBehavior::BACK_URL_PARAM;
        }

        if ($this->removeGridParam) {
            $removeParams[] = 'page';
            $removeParams[] = 'sort';
        }

        foreach ($removeParams as $removeParam) {
            if (isset($urlParams[$removeParam])) {
                unset($urlParams[$removeParam]);
            }
        }

        $urlParams[0] = 'index';

        return $this->urlParams = $urlParams;
    }

    public function getBreadcrumbs()
    {
        return $this->links;
    }
}