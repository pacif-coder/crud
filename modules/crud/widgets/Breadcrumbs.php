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

        end($parents);
        $lastIndex = key($parents);
        foreach ($parents as $i => $modelData) {
            if ($lastIndex == $i) {
                continue;
            }

            $this->modelData2breadcrumbs($modelData);
        }

        $messageCategory = ClassI18N::class2messagesPath(get_class($model));
        $params = [
            'parentModelName' => end($parents)['parentName'],
            'nameAttribute' => end($parents)['name'],
        ];
        $linkText = Yii::t($messageCategory, 'List items', $params);

        $urlParams = $this->initUrlParams();
        $urlParams['id'] = end($parents)['id'];

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

    protected function modelData2breadcrumbs($modelData)
    {
        $messageCategory = ClassI18N::class2messagesPath($modelData['class']);
        $params = [
            'parentModelName' => $modelData['parentName'],
            'nameAttribute' => $modelData['name'],
        ];
        $linkText = Yii::t($messageCategory, 'List items', $params);

        $urlParams = $this->initUrlParams();
        $urlParams['id'] = $modelData['id'];

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