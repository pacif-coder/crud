<?php
namespace app\modules\crud\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\base\InvalidConfigException;
use yii\base\Theme;
use yii\helpers\Url;

use app\modules\crud\models\ModelWithParentInterface;
use app\modules\crud\behaviors\BackUrlBehavior;

use app\modules\crud\widgets\Breadcrumbs;

use app\modules\crud\builder\FormBuilder;
use app\modules\crud\builder\GridBuilder;
use app\modules\crud\helpers\ClassI18N;
use app\modules\crud\helpers\ParentModel;
use app\modules\crud\assets\CrudAsset;

use app\modules\crud\Module as CrudModule;

/**
 * Default controller for the `admin` module
 *
 * @property View|\yii\web\View $view The view object that can be used to render views or view files.
 */
class CrudController extends Controller
{
    use ControllerTrait {
        ControllerTrait::init as traitInit;
    }

    public $modelClass;
    public $messageCategory;
    public $modelSearchClass;

    public $addCreateButton = true;

    public $parentModelID;

    /**
     * @var \app\modules\crud\builder\GridBuilder
     */
    protected $gridBuilder;

    protected $gridBuilderEvent = [
        GridBuilder::EVENT_BEFORE_BUILD => 'beforeGridBuild',
        GridBuilder::EVENT_AFTER_BUILD => 'afterGridBuild',
        GridBuilder::EVENT_BEFORE_FILTER_APPLY => 'beforeFilterApply',
    ];

    /**
     * @var \app\modules\crud\builder\FormBuilder
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

        if (!$this->modelClass) {
            throw new InvalidConfigException('Not find model class');
        }

        if (!$this->messageCategory && $this->modelClass) {
            $this->messageCategory = ClassI18N::class2messagesPath($this->modelClass);
        }

        if (is_subclass_of($this->modelClass, ModelWithParentInterface::class)) {
            $this->parentModelID = $this->getModelID();
        }

        $this->traitInit();
    }

    /**
     * Show model objects list
     * @return string
     */
    public function actionIndex()
    {
        // build grid
        $builder = $this->createGrid();

        $this->createIndexTitle();

        $view = $this->getView();
        $br = new Breadcrumbs();
        $view->params['breadcrumbs'] = $br->createIndexBreadcrumbs($this->createModel());

        return $this->render('index', compact(['builder']));
    }

    protected function getGridBuilder($withCopy = true)
    {
        if ($this->gridBuilder) {
            return $this->gridBuilder;
        }

        $this->gridBuilder = new GridBuilder();
        if ($withCopy) {
            $this->gridBuilder->controller2this($this);
        }

        return $this->gridBuilder;
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'back' url page.
     * @return mixed
     */
    public function actionCreate()
    {
        return $this->_actionEdit(true);
    }

    /**
     * Updates an existing model object.
     * If update is successful, the browser will be redirected to the 'back' url page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate()
    {
        return $this->_actionEdit(false);
    }

    /**
     * Sort 
     * @return mixed
     */
    public function actionSort()
    {
        $sort = Yii::$app->request->post('sort', []);

        $builder = $this->createGrid();

        $provider = $builder->getProvider();
        $begin = 0;
        if (($pagination = $provider->getPagination()) !== false) {
            $pagination->totalCount = $provider->getTotalCount();
            $begin = $pagination->getOffset();
        }

        $sort_attr = $this->modelClass::ORDER_ATTR;
        foreach ($sort as $index => $keys) {
            $model = $this->modelClass::findOne($keys);
            $pos = $index + $begin + 1;
            if ($pos == $model->{$sort_attr}) {
                continue;
            }

            $model->{$sort_attr} = $pos;
            $model->save();
        }

        return $this->goBack();
    }

    /**
     * Show an existing model object.
     * If update is successful, the browser will be redirected to the 'back' url page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function actionRead()
    {
        $model = $this->findModel();
        $builder = $this->getFromBuilder();

        $this->createReadTitle();

        return $this->render('read', compact(['model', 'builder']));
    }

    /**
     * Create and edit object nodel
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function _actionEdit($create)
    {
        $model = $create? $this->createModel() : $this->findModel();

        $builder = $this->createForm($model);
        if ($builder->data2model(Yii::$app->request->post(), $model)) {
            return $this->goBack();
        }

        // $this->addParentToBreadcrumbs($model);

        $view = $this->getView();
        $br = new Breadcrumbs();
        $view->params['breadcrumbs'] = $br->createEditBreadcrumbs($model, $this->getBackUrl());

        $this->createEditTitle($create, $model);

        return $this->render('edit', compact(['model', 'builder']));
    }

    protected function createForm($model)
    {
        $builder = $this->getFromBuilder();

        // call model event callback first
        $builder->bindEventsHandler($model, $this->formBuilderEvent);

        // call controller event callback second
        $builder->bindEventsHandler($this, $this->formBuilderEvent);

        // build form description
        $builder->build($model);

        return $builder;
    }

    protected function createGrid()
    {
        $builder = $this->getGridBuilder();

        // call model class event callback first
        $builder->bindEventsHandler($this->modelClass, $this->gridBuilderEvent);

        // call controller event callback
        $builder->bindEventsHandler($this, $this->gridBuilderEvent);

        // build grid description
        $builder->build();

        return $builder;
    }

    protected function createEditTitle($isCreate, $model)
    {
        $view = $this->getView();
        $builder = $this->getFromBuilder();

        if ($isCreate) {
            $view->title = Yii::t($this->messageCategory, 'Create item');
        } elseif ($builder->nameAttr) {
            $name = $builder->nameAttr;
            $view->title = Yii::t($this->messageCategory, 'Update item "{nameAttribute}"', ['nameAttribute' => $model->{$name}]);
        } else {
            $view->title = Yii::t($this->messageCategory, 'Update item');
        }
    }

    protected function createReadTitle()
    {
        $view = $this->getView();
        $builder = $this->getFromBuilder();

        if ($builder->nameAttr) {
            $name = $builder->nameAttr;
            $view->title = Yii::t($this->messageCategory, 'Show item "{nameAttribute}"', ['nameAttribute' => $model->{$name}]);
        } else {
            $view->title = Yii::t($this->messageCategory, 'Show item');
        }
    }

    protected function createIndexTitle()
    {
        $model = $this->createModel();
        $parents = ParentModel::loadParents($model);

        $params = [];
        if ($parents) {
            $params = [
                'parentModelName' => end($parents)['parentName'],
                'nameAttribute' => end($parents)['name'],
            ];
        }

        $view = $this->getView();
        $view->title = Yii::t($this->messageCategory, 'List items', $params);
    }

    protected function addParentToBreadcrumbs($model)
    {
        $parents = ParentModel::loadParents($model);

        $params = [];
        if ($parents) {
            $params = [
                'parentModelName' => end($parents)['parentName'],
                'nameAttribute' => end($parents)['name'],
            ];
        }

        $this->addToBreadcrumbs($this->getBackUrl(),
                Yii::t($this->messageCategory, 'List items', $params));
    }

    protected function getFromBuilder($withCopy = true)
    {
        if ($this->formBuilder) {
            return $this->formBuilder;
        }

        $this->formBuilder = new FormBuilder();
        if ($withCopy) {
            $this->formBuilder->controller2this($this);
        }

        return $this->formBuilder;
    }

    /**
     * Deletes an existing model object.
     *
     * If deletion is successful, the browser will be redirected to the 'back' url.
     * @param string $id
     * @return mixed
     */
    public function actionDelete()
    {
        $selection = Yii::$app->request->post('selection', []);

        $modelClass = $this->modelClass;
        foreach ($modelClass::findAll($selection) as $model) {
            /* @var $model \yii\db\ActiveRecord */
            $model->delete();
            if (!$model->hasErrors()) {
                continue;
            }

            Yii::$app->session->setFlash('danger', implode("\r\n", $model->getErrorSummary(true)));
            break;
        }

        return $this->goBack();
    }

    /**
     * Finds the model based on its primary key value.
     *
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return \yii\db\ActiveRecord the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($exception = true)
    {
        $model = $this->modelClass::findOne($this->getModelID());
        if (null !== $model) {
            return $model;
        }

        if ($exception) {
            throw new NotFoundHttpException('The requested model does not exist.');
        }
    }

    /**
     * Create new model object, and set parent model id
     *
     * @return \yii\db\ActiveRecord the created model
     */
    protected function createModel()
    {
        $model = Yii::createObject($this->modelClass);

        $parentModelAttr = ParentModel::getParentModelAttr($model);
        if ($parentModelAttr) {
            $model->{$parentModelAttr} = $this->getModelID();
        }

        return $model;
    }

    public function getModelID()
    {
        return Yii::$app->request->get('id');
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