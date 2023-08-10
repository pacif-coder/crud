<?php
namespace Crud\controllers;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Theme;
use yii\web\NotFoundHttpException;

use Crud\latte\CrudTemplateParameters;
use Crud\latte\LatteRenderer;
use Crud\helpers\ClassI18N;
use Crud\helpers\ParentModel;
use Crud\helpers\ModelName;
use Crud\models\ModelWithParentInterface;

use ReflectionClass;

/**
 * Default controller for the CRUD module
 *
 */
abstract class CrudController extends BaseController
{
    public $modelClass;

    public $parentModelID;

    public $topMenu = [];

    /**
     * @var CrudTemplateParameters
     */
    protected $templateParams;

    protected $titleParams = [];

    public function init()
    {
        if (!$this->modelClass) {
            throw new InvalidConfigException('Not find model class');
        }

        parent::init();

        if (!$this->messageCategory) {
            $this->messageCategory = ClassI18N::class2messagesPath($this->modelClass);
        }

        if (is_subclass_of($this->modelClass, ModelWithParentInterface::class)) {
            $this->parentModelID = $this->getModelID();
        }

        $this->layout = false;

        $this->fillTemplateFindPaths();
        $this->mapFakeTheme();

        $this->templateParams = Yii::createObject(CrudTemplateParameters::class);
    }

    protected function fillTemplateFindPaths()
    {
        $view = $this->getView();
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

        $this->createIndexTitle();

        $this->createIndexBreadcrumbs();

        return $this->render('index.latte', $this->templateParams);
    }

    protected function createIndexTitle()
    {
        $model = $this->createModel();
        $this->model2titleParams($model);

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

        $this->breadcrumbs = Yii::createObject($this->breadcrumbs);
        $this->breadcrumbs->createIndexBreadcrumbs($this->createModel());
        $this->templateParams->breadcrumbs = $this->breadcrumbs;
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
     * Create and edit object nodel
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

        $this->createEditTitle($isCreate, $model);

        $this->createEditBreadcrumbs($isCreate, $model);

        return $this->render('edit.latte', $this->templateParams);
    }

    protected function createEditTitle($isCreate, $model)
    {
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

        return $this->goBack();
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
            $this->breadcrumbs['withBegin'] = (bool) $this->getModelID();
            $this->breadcrumbs['lastUrl'] = $this->getBackUrl();
        }

        $this->breadcrumbs = Yii::createObject($this->breadcrumbs);
        $this->breadcrumbs->createEditBreadcrumbs($model);

        $this->templateParams->breadcrumbs = $this->breadcrumbs;
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

    protected function setTitle($title)
    {
        parent::setTitle($title);

        if ($this->templateParams) {
            $this->templateParams->title = $title;
        }
    }
}