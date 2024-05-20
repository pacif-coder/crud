<?php
namespace Crud\controllers;

use Yii;
use yii\helpers\Url;
use yii\base\InvalidConfigException;
use yii\base\Theme;
use yii\web\NotFoundHttpException;

use Crud\latte\CrudTemplateParameters;
use Crud\latte\LatteRenderer;
use Crud\helpers\ClassI18N;
use Crud\helpers\Html;
use Crud\helpers\Lang;
use Crud\helpers\ModelName;
use Crud\helpers\ParentModel;
use Crud\helpers\TitleHelper;

use Crud\models\ModelWithParentInterface;
use Crud\models\ActiveRecord;

use ReflectionClass;

/**
 * Default controller for the CRUD module
 *
 */
abstract class CrudController extends BaseController
{
    public $modelClass;

    public $parentModelID;

    public $layout = false;

    protected static $modelName2modelClass = [];

    protected $titleParams = [];

    /**
     * @var CrudTemplateParameters
     */
    protected $templateParams;

    protected $globalUseClass = [];

    protected $defaultGlobalUseClass = [
        TitleHelper::class => 'TitleHelper',
        Html::class,
        Url::class,
    ];

    public function init()
    {
        parent::init();

        $this->fillTemplateFindPaths();

        $this->fillTemplateGlobalUseClass();

        $this->mapFakeTheme();

        $this->templateParams = Yii::createObject(CrudTemplateParameters::class);
    }

    public function beforeAction($action): bool
    {
        $r = parent::beforeAction($action);
        if (!$r) {
            return $r;
        }

        $modelClass = $this->getModelClass();
        if (!$modelClass) {
            throw new InvalidConfigException('Not find model class');
        }

        if (is_subclass_of($modelClass, ModelWithParentInterface::class)) {
            $this->parentModelID = $this->getModelID();
        }

        $this->initMessageCategory();

        return $r;
    }

    protected function getModelClass()
    {
        if ($this->modelClass) {
            return $this->modelClass;
        }

        $this->fillModelClass();
        return $this->modelClass;
    }

    protected function fillModelClass()
    {
        $name = Yii::$app->request->get('model-name');
        if (!$name || !isset(static::$modelName2modelClass[$name])) {
            return ;
        }

        $this->modelClass = static::$modelName2modelClass[$name];
    }

    protected function fillTemplateGlobalUseClass()
    {
        $view = $this->getView();
        if (!isset($view->renderers['latte'])) {
            return;
        }

        $globalUse = [];
        $merged = array_merge($this->defaultGlobalUseClass, $this->globalUseClass);
        foreach ($merged as $key => $name) {
            if (!is_int($key) || !in_array($name, $globalUse)) {
                $globalUse[$key] = $name;
            }
        }

        $view->renderers['latte']['globalUseClass'] = $globalUse;
    }

    protected function initMessageCategory()
    {
        //  add crud as messageCategory ?
        if ($this->messageCategory) {
            return;
        }

        $modelClass = $this->getModelClass();
        if (is_a($modelClass, ActiveRecord::class, true)) {
            $this->messageCategory = $this->modelClass::getMessageCategory();
        }

        // add error?
    }

    protected function fillTemplateFindPaths()
    {
        $view = $this->getView();
        if (!isset($view->renderers['latte'])) {
            return;
        }

        $view->renderers['latte']['class'] = LatteRenderer::class;
        $view->renderers['latte']['options']['templateDirs'] = $this->getTemplateFindPaths();
    }

    protected function getTemplateFindPaths()
    {
        $templatePaths = [
            $this->getViewPath(),
        ];

        $ref = new ReflectionClass($this);
        $class = $ref->getParentClass()->name;
        while ($class) {
            if (0 === strpos($class, 'yii\\')) {
                break;
            }

            $ref = new ReflectionClass($class);
            if ($class == self::class) {
                $templatePaths[] = realpath(dirname($ref->getFileName()) . '/../views/crud/');
                break;
            }

            $class = $ref->getParentClass()->name;
        }

        return $templatePaths;
    }

    protected function mapFakeTheme()
    {
        $templateFindPaths = $this->getTemplateFindPaths();
        if (!$templateFindPaths) {
            return;
        }

        $first = reset($templateFindPaths);

        $fakeTheme = new Theme();
        $fakeTheme->pathMap[$first] = $templateFindPaths;

        $view = $this->getView();
        $view->theme = $fakeTheme;
    }

    /**
     * Show model objects list
     * @return string
     */
    public function actionIndex()
    {
        // build grid
        $this->templateParams->builder = $this->buildGrid();
        $this->templateParams->model = $this->createModel();

        $this->createIndexTitle();

        $this->createIndexBreadcrumbs();

        return $this->render('index.latte', $this->templateParams);
    }

    protected function createIndexTitle()
    {
        if ($this->title) {
            return;
        }

        $this->model2titleParams($this->templateParams->model);

        $title = null;
        if (!$this->getModelID()) {
            $message = 'Top list items';
            $title = $this->t($message, $this->titleParams);
            if ($title == $message) {
                $title = null;
            }
        }

        if (!$title) {
            $title = $this->t('List items', $this->titleParams);
        }

        $this->title = $title;
    }

    protected function createIndexBreadcrumbs()
    {
        if (is_array($this->breadcrumbs)) {
            $this->breadcrumbs['withBegin'] = (bool) $this->getModelID();
        }

        $breadcrumbs = $this->getBreadcrumbs();
        $breadcrumbs->createIndexBreadcrumbs($this->templateParams->model);
        $this->templateParams->breadcrumbs = $breadcrumbs;
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
     * Create and edit object model
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function _actionEdit($isCreate)
    {
        $model = $isCreate ? $this->createModel() : $this->findModel();
        $this->templateParams->model = $model;

        $this->model2titleParams($model);

        $builder = $this->buildForm($model);
        $this->templateParams->builder = $builder;

        $post = $this->request->post();
        if ($this->request->isPost && $builder->data2model($post, $model)) {
            return $this->goBack();
        }

        $this->createEditTitle($isCreate);

        $this->createEditBreadcrumbs($isCreate, $model);

        return $this->render('edit.latte', $this->templateParams);
    }

    protected function createEditTitle($isCreate)
    {
        if ($this->title) {
            return;
        }

        if ($isCreate) {
            $this->title = $this->t('Create item', $this->titleParams);
        } elseif (isset($this->titleParams['nameAttribute'])) {
            $this->title = $this->t('Update item "{nameAttribute}"', $this->titleParams);
        } else {
            $this->title = $this->t('Update item');
        }
    }

    /**
     * Sort
     * @return mixed
     */
    public function actionSort()
    {
        $sort = $this->request->post('sort', []);

        $builder = $this->buildGrid();

        $provider = $builder->getProvider();
        $begin = 0;
        if (($pagination = $provider->getPagination()) !== false) {
            $pagination->totalCount = $provider->getTotalCount();
            $begin = $pagination->getOffset();
        }

        $sortAttr = $this->modelClass::ORDER_ATTR;
        $maxSort = $this->modelClass::find()->addSelect("max([[{$sortAttr}]])")->scalar();

        // the value of the sort attribute must be unique - so move the objects to
        // the end first, and then put them in the right position
        $moved = [];
        $idKey = current($this->modelClass::primaryKey());
        foreach ($this->modelClass::findAll($sort) as $model) {
            $id = $model->{$idKey};
            $index = array_search($id, $sort);

            $pos = $index + $begin + 1;
            if ($pos == $model->{$sortAttr}) {
                continue;
            }

            $movePos = $index + $maxSort + 1;
            $model->{$sortAttr} = $movePos;
            $model->save();

            $moved[$id] = $pos;
        }

        foreach ($this->modelClass::findAll(array_keys($moved)) as $model) {
            $id = $model->{$idKey};
            $pos = $moved[$id];
            if ($pos == $model->{$sortAttr}) {
                continue;
            }

            $model->{$sortAttr} = $pos;
            $model->save();
        }

        $get = $this->request->get();
        $get[0] = 'index';
        return $this->redirect($get);
    }

    protected function model2titleParams($model)
    {
        $this->titleParams = [];

        $name = ModelName::getName($model);
        if ($name) {
            $this->titleParams['nameAttribute'] = $name;
        }

        if (is_a($model, ModelWithParentInterface::class)) {
            $parents = ParentModel::loadParents($model);
            if ($parents) {
                $this->titleParams['parentModelName'] = end($parents)['name'];
            }
        }
    }

    protected function createEditBreadcrumbs($isCreate, $model)
    {
        if (is_array($this->breadcrumbs)) {
            $this->breadcrumbs += [
                'withBegin' => (bool) $this->getModelID(),
                'lastUrl' => $this->getBackUrl(),
            ];
        }

        $breadcrumbs = $this->getBreadcrumbs();
        $breadcrumbs->createEditBreadcrumbs($model);
        $this->templateParams->breadcrumbs = $breadcrumbs;
    }

    protected function createBreadcrumbs()
    {
        $this->breadcrumbs += ['messageCategory' => $this->messageCategory];
        parent::createBreadcrumbs();
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
        $selection = $this->request->post('selection', []);

        $modelClass = $this->modelClass;
        foreach ($modelClass::findAll($selection) as $model) {
            /* @var $model \yii\db\ActiveRecord */
            $model->delete();
            if (!$model->hasErrors()) {
                continue;
            }

            $this->addFlashMessage('danger', implode("\r\n", $model->getErrorSummary(true)));
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
    protected function findModel($exception404 = true)
    {
        return $this->_findModel($this->modelClass, $this->getModelID(), $exception404);
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
        return $this->request->get('id');
    }
}