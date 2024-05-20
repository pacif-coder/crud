<?php
namespace Crud\widgets;

use Yii;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;

use Crud\behaviors\BackUrlBehavior;
use Crud\helpers\ChildeClass;
use Crud\helpers\ClassI18N;
use Crud\helpers\Lang;
use Crud\helpers\ModelName;
use Crud\helpers\ParentModel;
use Crud\models\ModelWithParentInterface;

use Crud\models\tree_node\Type;

/**
 *
 */
class Breadcrumbs extends \yii\base\BaseObject
{
    public $withHomeIcon = true;

    public $links = [];

    public $withType;

    public $withBegin;

    public $lastUrl;

    public $removeBackUrl = true;

    public $removeGridParam = true;

    public $removeParams = [];

    public $messageCategory;

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
            // the first and last link - are the same thing
            if ($this->lastUrl && !$parents) {
                $url = $this->lastUrl;
            } else {
                $urlParams = $this->initUrlParams();
                $this->dropParamInFirstUrl($urlParams);
                $url = Url::toRoute($urlParams);
            }

            $label = Lang::t($this->messageCategory, 'Top list items');
            $this->addLink($url, $label);
        }

        end($parents);
        $lastIndex = key($parents);
        foreach ($parents as $i => $modelData) {
            if ($lastIndex != $i) {
                $this->parentModelData2breadcrumbs($modelData);
                continue;
            }

            if (!$includeLast) {
                break;
            }

            if ($this->lastUrl) {
                $this->addLink($this->lastUrl, $modelData['name']);
            } else {
                $this->parentModelData2breadcrumbs($modelData);
            }
        }

        return $this->links;
    }

    public function parentModelData2breadcrumbs($modelData)
    {
        $urlParams = $this->class2urlParams($modelData['class']);

        $urlParams['id'] = $modelData['id'];

        if ($this->withType) {
            $childeClass = ChildeClass::getChildeClass($modelData['model']);
            $urlParams['type'] = Type::getTypeByClass($childeClass);
        }

        $url = Url::toRoute($urlParams);
        $this->addLink($url, $modelData['name']);
    }

    public function class2urlParams($class)
    {
        $urlParams = $this->initUrlParams();

        if (Yii::$app->has('class2controller')) {
            $controller = Yii::$app->class2controller->getController($class);
            if ($controller) {
                $urlParams[0] = "{$controller}/{$urlParams[0]}";
            }
        }

        return $urlParams;
    }

    protected function dropParamInFirstUrl(&$urlParams)
    {
        if (isset($urlParams['id'])) {
            unset($urlParams['id']);
        }

        if ($this->withType && isset($urlParams['type'])) {
            unset($urlParams['type']);
        }
    }

    public function addLink($url, $label)
    {
        $this->links[] = (object) [
            'url' => $url,
            'label' => $label,
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
}