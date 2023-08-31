<?php
namespace Crud\controllers;

use Yii;
use yii\di\Instance;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Request;
use yii\web\Response;

use Crud\assets\CrudAsset;
use Crud\behaviors\BackUrlBehavior;
use Crud\builder\FormBuilder;
use Crud\builder\GridBuilder;
use Crud\helpers\Lang;
use Crud\widgets\Breadcrumbs;

/**
 *
 */
abstract class BaseController extends \yii\web\Controller
{
    public $messageCategory;

    public $assets = [];

    public $defaultAsset = CrudAsset::class;

    public $breadcrumbs = [
        'class' => Breadcrumbs::class,
    ];

    /**
     * @var Request|array|string The request.
     */
    public $request = 'request';

    /**
     * @var Response|array|string The response.
     */
    public $response = 'response';

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

    protected $action2title = [];

    protected $topMenu = [];

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['backUrl'] = BackUrlBehavior::class;

        return $behaviors;
    }

    public function init()
    {
        parent::init();

        $this->request = Instance::ensure($this->request, Request::class);
        $this->response = Instance::ensure($this->response, Response::class);

        $view = $this->getView();

        if ($this->defaultAsset) {
            $view->registerAssetBundle($this->defaultAsset);
        }

        foreach ($this->assets as $asset) {
            $view->registerAssetBundle($asset);
        }

        $this->registerTranslations();
    }

    public function beforeAction($action): bool
    {
        $r = parent::beforeAction($action);
        if (!$r) {
            return $r;
        }

        if (isset($this->action2title[$action->id])) {
            $this->setTitle($this->action2title[$action->id]);
        }

        return $r;
    }

    public function registerTranslations()
    {
        Lang::addCategory2Path('crud', dirname(__DIR__) . '/messages');
    }

    /**
     * @return FormBuilder
     */
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

    /**
     * @return FormBuilder
     */
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

    /**
     * @return GridBuilder
     */
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

    /**
     * @return GridBuilder
     */
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

    protected function addToBreadcrumbs($url, $label)
    {
        $this->breadcrumbs = Yii::createObject($this->breadcrumbs);

        $this->breadcrumbs->links[] = [
            'url' => Url::toRoute($url),
            'label' => $label
        ];
    }

    protected function setTitle($title)
    {
        $view = $this->getView();
        $view->title = $title;
    }

    protected function getTitle()
    {
        $view = $this->getView();
        return $view->title;
    }

    protected function addFlashMessage($messageType, $value = true, $removeAfterAccess = true)
    {
        Yii::$app->session->addFlash($messageType, $value, $removeAfterAccess);
    }

    protected function t($message, $params = [], $language = null)
    {
        return Lang::t($this->messageCategory, $message, $params, $language);
    }

    protected function _findModel($class, $id, $exception404 = true)
    {
        $this->testID($id);

        $model = $class::findOne($id);
        if (null !== $model) {
            return $model;
        }

        if ($exception404) {
            throw new NotFoundHttpException('The requested model does not exist.');
        }
    }

    protected function testID($id)
    {
        if (!empty($id) && !is_scalar($id)) {
            throw new BadRequestHttpException("Param 'id' mast have scalar value");
        }
    }

    protected function beforeFilterApply(\yii\base\Event $event)
    {
        /* @var $gridBuilder GridBuilder */
        $gridBuilder = $event->sender;
    }

    protected function beforeGridBuild(\yii\base\Event $event)
    {
        /* @var $gridBuilder \Crud\builder\GridBuilder */
        $gridBuilder = $event->sender;
    }

    protected function afterGridBuild(\yii\base\Event $event)
    {
        /* @var $gridBuilder \Crud\builder\GridBuilder */
        $gridBuilder = $event->sender;
    }

    protected function beforeFormBuild(\yii\base\Event $event)
    {
        /* @var $formBuilder \Crud\builder\FormBuilder */
        $formBuilder = $event->sender;
    }

    protected function afterFormBuild(\yii\base\Event $event)
    {
        /* @var $formBuilder \Crud\builder\FormBuilder */
        $formBuilder = $event->sender;
    }

    public function getTopMenu()
    {
        return $this->topMenu;
    }
}