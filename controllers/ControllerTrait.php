<?php
namespace app\modules\crud\controllers;

use yii\base\Theme;
use yii\helpers\Url;

use app\modules\crud\assets\CrudAsset;
use app\modules\crud\Module as CrudModule;

/**
 *
 */
trait ControllerTrait
{
    public $assets = [];

    public $defaultAsset = CrudAsset::class;

    public function init()
    {
        $view = $this->getView();

        if ($this->defaultAsset) {
            $view->registerAssetBundle($this->defaultAsset);
        }

        foreach ($this->assets as $asset) {
            $view->registerAssetBundle($asset);
        }

        $this->mapFakeTheme();
    }

    protected function mapFakeTheme()
    {
        $view = $this->getView();

        $crudModule = new CrudModule('crud');
        $crudViewPath = $crudModule->getViewPath() . DIRECTORY_SEPARATOR . $crudModule->defaultRoute;

        $thisViewPath = $this->getViewPath();

        $fakeTheme = new Theme();
        $fakeTheme->pathMap[$thisViewPath] = [
            $thisViewPath,
            $crudViewPath,
        ];

        $view->theme = $fakeTheme;
    }

    protected function addToBreadcrumbs($url, $label)
    {
        $view = $this->getView();
        $view->params['breadcrumbs'][] = ['url' => Url::toRoute($url),
            'label' => $label];
    }
}
