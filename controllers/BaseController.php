<?php
namespace app\modules\crud\controllers;

use Yii;
use yii\base\Theme;
use yii\helpers\Url;

use app\modules\crud\Module as CrudModule;
use app\modules\crud\assets\CrudAsset;
use app\modules\crud\behaviors\BackUrlBehavior;
use app\modules\crud\builder\FormBuilder;
use app\modules\crud\builder\GridBuilder;
use app\modules\crud\widgets\Breadcrumbs;

/**
 *
 */
abstract class BaseController extends \yii\web\Controller
{
    public $messageCategory;

    public $assets = [];

    public $defaultAsset = CrudAsset::class;

    /**
     * @var GridBuilder
     */
    protected $gridBuilder;

    protected $gridBuilderEvent = [
        GridBuilder::EVENT_BEFORE_BUILD => 'beforeGridBuild',
        GridBuilder::EVENT_AFTER_BUILD => 'afterGridBuild',
        GridBuilder::EVENT_BEFORE_FILTER_APPLY => 'beforeFilterApply',
    ];

    /**
     * @var FormBuilder
     */
    protected $formBuilder;

    protected $formBuilderEvent = [
        FormBuilder::EVENT_BEFORE_BUILD => 'beforeFormBuild',
        FormBuilder::EVENT_AFTER_BUILD => 'afterFormBuild',
    ];

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['backUrl'] = BackUrlBehavior::class;

        return $behaviors;
    }

    public function init()
    {
        parent::init();

        $view = $this->getView();

        if ($this->defaultAsset) {
            $view->registerAssetBundle($this->defaultAsset);
        }

        foreach ($this->assets as $asset) {
            $view->registerAssetBundle($asset);
        }

        $this->mapFakeTheme();
    }

    protected function getFromBuilder($withController = true)
    {
        if ($this->formBuilder) {
            return $this->formBuilder;
        }

        $this->formBuilder = new FormBuilder();
        if ($withController) {
            $this->formBuilder->controller2this($this);
        }

        return $this->formBuilder;
    }

    protected function buildForm($model, $withController = true)
    {
        $builder = $this->getFromBuilder($withController);

        // call model event callback first
        $builder->bindEventsHandler($model, $this->formBuilderEvent);

        // call controller event callback second
        if ($withController) {
            $builder->bindEventsHandler($this, $this->formBuilderEvent);
        }

        // build form description
        $builder->build($model);

        return $builder;
    }

    protected function buildGrid($withController = true)
    {
        $builder = $this->getGridBuilder($withController);

        // call model class event callback first
        $builder->bindEventsHandler($this->modelClass, $this->gridBuilderEvent);

        // call controller event callback
        if ($withController) {
            $builder->bindEventsHandler($this, $this->gridBuilderEvent);
        }

        // build grid description
        $builder->build();

        return $builder;
    }

    protected function getGridBuilder($withController = true)
    {
        if ($this->gridBuilder) {
            return $this->gridBuilder;
        }

        $this->gridBuilder = new GridBuilder();
        if ($withController) {
            $this->gridBuilder->controller2this($this);
        }

        return $this->gridBuilder;
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

    protected function createEditBreadcrumbs($model)
    {
        $br = new Breadcrumbs();

        $view = $this->getView();
        $view->params['breadcrumbs'] = $br->createEditBreadcrumbs($model, $this->getBackUrl());
    }

    protected function addToBreadcrumbs($url, $label)
    {
        $view = $this->getView();
        $view->params['breadcrumbs'][] = ['url' => Url::toRoute($url),
            'label' => $label];
    }

    protected function setTitle($title)
    {
        $view = $this->getView();
        $view->title = $title;
    }

    protected function addFlashMessage($messageType, $value = true, $removeAfterAccess = true)
    {
        Yii::$app->session->addFlash($messageType, $value, $removeAfterAccess);
    }

    protected function t($message, $params = [], $language = null)
    {
        return Yii::t($this->messageCategory, $message, $params, $language);
    }

    protected function beforeFilterApply(\yii\base\Event $event)
    {
        /* @var $gridBuilder \app\modules\crud\builder\GridBuilder */
        $gridBuilder = $event->sender;
    }

    protected function beforeGridBuild(\yii\base\Event $event)
    {
        /* @var $gridBuilder \app\modules\crud\builder\GridBuilder */
        $gridBuilder = $event->sender;
    }

    protected function afterGridBuild(\yii\base\Event $event)
    {
        /* @var $gridBuilder \app\modules\crud\builder\GridBuilder */
        $gridBuilder = $event->sender;
    }

    protected function beforeFormBuild(\yii\base\Event $event)
    {
        /* @var $formBuilder \app\modules\crud\builder\FormBuilder */
        $formBuilder = $event->sender;
    }

    protected function afterFormBuild(\yii\base\Event $event)
    {
        /* @var $formBuilder \app\modules\crud\builder\FormBuilder */
        $formBuilder = $event->sender;
    }
}