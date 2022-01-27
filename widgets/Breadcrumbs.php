<?php
namespace app\modules\crud\widgets;

use Yii;
use yii\helpers\Url;

use app\modules\crud\helpers\ParentModel;
use app\modules\crud\helpers\ClassI18N;

/**
 *
 */
class Breadcrumbs
{
    protected $parents;

    protected $breadcrumbs = [];

    protected $urlParams;

    public function createEditBreadcrumbs($model)
    {
        $parents = ParentModel::loadParents($model);

        $parent = null;
        if ($parents) {
            $begin = reset($parents);
            $this->modelData2breadcrumbs($begin, true);

            $parent = end($parents);
            $lastIndex = key($parents);
            foreach ($parents as $i => $modelData) {
                if ($lastIndex == $i) {
                    break;
                }

                $this->modelData2breadcrumbs($modelData);
            }

            $parent = $modelData;
        }

        $modelClass = get_class($model);
        $messageCategory = ClassI18N::class2messagesPath($modelClass);
        $params = $parent? ['parentModelName' => $parent['name']] : [];
        $linkText = Yii::t($messageCategory, 'List items', $params);

        $urlParams = $this->initUrlParams();
        $controller = Yii::$app->class2controller->getController($modelClass);
        if ($controller) {
            $urlParams[0] = "{$controller}/{$urlParams[0]}";
        }

        if ($parent) {
            $urlParams['id'] = $parent['id'];
        }

        $this->breadcrumbs[] = [
            'url' => Url::toRoute($urlParams),
            'label' => $linkText,
        ];

        return $this->breadcrumbs;
    }

    public function createIndexBreadcrumbs($model)
    {
        $parents = ParentModel::loadParents($model);

        end($parents);
        $lastIndex = key($parents);
        foreach ($parents as $i => $modelData) {
            if ($lastIndex == $i) {
                //continue;
            }

            $this->modelData2breadcrumbs($modelData);
        }

        return $this->breadcrumbs;
    }

    protected function modelData2breadcrumbs($modelData, $dropID = false)
    {
        $messageCategory = ClassI18N::class2messagesPath($modelData['class']);
        $params = [
            'parentModelName' => $modelData['parentName'],
            'nameAttribute' => $modelData['name'],
        ];
        $linkText = Yii::t($messageCategory, 'List items', $params);

        $urlParams = $this->initUrlParams();
        if (!$dropID) {
            $urlParams['id'] = $modelData['id'];
        } elseif (isset($urlParams['id'])) {
            unset($urlParams['id']);
        }

        $controller = Yii::$app->class2controller->getController($modelData['class']);
        if ($controller) {
            $urlParams[0] = "{$controller}/{$urlParams[0]}";
        }

        $this->breadcrumbs[] = [
            'url' => Url::toRoute($urlParams),
            'label' => $linkText,
        ];
    }

    protected function initUrlParams()
    {
        if (null !== $this->urlParams) {
            return $this->urlParams;
        }

        $urlParams = Yii::$app->request->get();
        if (isset($urlParams['back-url'])) {
            unset($urlParams['back-url']);
        }

        if (isset($urlParams['page'])) {
            unset($urlParams['page']);
        }

        $urlParams[0] = 'index';

        return $this->urlParams = $urlParams;
    }

    public function getLastName()
    {
        if (!$this->parents) {
            return;
        }

        return end($this->parents)['name'];
    }
}