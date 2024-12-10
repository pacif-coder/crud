<?php
namespace Crud\controllers;

use Yii;
use yii\helpers\Url;
use yii\base\InvalidConfigException;
use yii\base\Theme;
use yii\web\NotFoundHttpException;

use Crud\latte\CrudTemplateParameters;
use Crud\latte\LatteRenderer;
use Crud\helpers\Html as BaseHtmlHelper;
use Crud\helpers\ModelName;
use Crud\helpers\ParentModel;
use Crud\helpers\TitleHelper;

use Crud\models\ModelWithParentInterface;
use Crud\models\ActiveRecord;

use ReflectionClass;

/**
 * Abstract base controller for managing CRUD operations.
 * This class provides a framework for creating, reading, updating, and deleting models.
 */
abstract class CrudController extends BaseController
{
    /**
     * @var string Class for the model this controller manages
     */
    public $modelClass;

    // Layout for rendering views, set to false to disable layouts
    public $layout = false;

    // Mapping of model names to their corresponding class names
    public $modelName2modelClass = [];

    public $parentModelID;

    /**
     * @var CrudTemplateParameters Parameters for rendering templates with Latte
     */
    public $templateParams;

    // Parameters for setting the page title
    protected $titleParams = [];

    // Global classes used in the template rendering
    protected $globalUseClass = [];

    // Default classes used globally in template rendering
    protected $defaultGlobalUseClass = [
        TitleHelper::class,
        Url::class,
    ];

    /**
     * Initializes the controller.
     * Sets up paths for finding templates and global classes used in rendering.
     */
    public function init()
    {
        parent::init();

        // Configure the paths to search for templates
        $this->fillTemplateFindPaths();

        // Set up global classes used in Latte template rendering
        $this->fillTemplateGlobalUseClass();

        // Configure a fake theme for Latte renderer
        $this->mapFakeTheme();

        // Create template parameters object for rendering
        $this->templateParams = Yii::createObject(CrudTemplateParameters::class);
    }

    /**
     * Performs actions before executing the requested action.
     * Validates the model class and checks for necessary configurations.
     *
     * @param \yii\base\Action $action The action to be executed
     * @return bool Whether the action should continue to run
     * @throws InvalidConfigException if the model class is not set
     */
    public function beforeAction($action): bool
    {
        $r = parent::beforeAction($action);
        if (!$r) {
            return $r;
        }

        // Ensure that the model class is set
        $modelClass = $this->getModelClass();
        if (!$modelClass) {
            throw new InvalidConfigException('Model class not found');
        }

        if (is_subclass_of($modelClass, ModelWithParentInterface::class)) {
            $this->parentModelID = $this->getModelID();
        }

        return $r;
    }

    /**
     * Retrieves the class name of the model managed by this controller.
     * If not set, it tries to determine the class based on the request parameters.
     *
     * @return string|null The class name of the model, or null if not found
     */
    public function getModelClass()
    {
        // Determine the model class from request parameters
        if (null === $this->modelClass) {
            $this->fillModelClass();
        }

        if (!$this->modelClass) {
            return $this->modelClass = false;
        }

        // Initialize message category for translations
        $this->modelClass2messageCategory($this->modelClass);

        return $this->modelClass;
    }

    /**
     * Sets the model class based on the 'model-name' parameter in the request.
     */
    protected function fillModelClass()
    {
        if (!$this->hasModelNameInGet()) {
            return;
        }

        $name = $this->getModelName();
        if (!isset($this->modelName2modelClass[$name])) {
            return;
        }

        $this->modelClass = $this->modelName2modelClass[$name];
    }

    /**
     * Checks if the 'model-name' parameter is present in the request.
     *
     * @return bool Whether the 'model-name' parameter is present
     */
    protected function hasModelNameInGet()
    {
        return $this->getModelName() !== null;
    }

    /**
     * Retrieves the 'model-name' parameter from the request.
     *
     * @return string|null The model name from the request, or null if not present
     */
    protected function getModelName()
    {
        return $this->request->get('model-name');
    }

    /**
     * Sets up the global classes used in Latte template rendering.
     */
    protected function fillTemplateGlobalUseClass()
    {
        $view = $this->getView();
        if (!isset($view->renderers['latte'])) {
            return;
        }

        // Merge default global classes with custom ones
        $globalUse = [];
        $merged = array_merge($this->defaultGlobalUseClass, $this->globalUseClass);

        // special case - use different versions of Bootstrap
        $htmlClass = BaseHtmlHelper::getBootstrapClass('Html');
        $merged[$htmlClass] = 'Html';

        foreach ($merged as $key => $name) {
            if (!is_int($key) || !in_array($name, $globalUse)) {
                $globalUse[$key] = $name;
            }
        }

        // Assign global classes to the Latte renderer
        $view->renderers['latte']['globalUseClass'] = $globalUse;
    }

    /**
     * Initialization category message to translate from the model
     * class, if it is not defined
     *
     * This is typically set to the category defined in the model.
     */
    protected function modelClass2messageCategory($modelClass)
    {
        // If message category is already set, do nothing
        if ($this->messageCategory) {
            return;
        }

        if (is_a($modelClass, ActiveRecord::class, true)) {
            $this->messageCategory = $modelClass::getMessageCategory();
        }

        // add error?
    }

    /**
     * Configures paths where Latte templates can be found.
     */
    protected function fillTemplateFindPaths()
    {
        $view = $this->getView();
        if (!isset($view->renderers['latte'])) {
            return;
        }

        // Set Latte renderer class and template directories
        $view->renderers['latte']['class'] = LatteRenderer::class;
        $view->renderers['latte']['options']['templateDirs'] = $this->getTemplateFindPaths();
    }

    /**
     * Retrieves the paths where templates should be searched for.
     * This method collects paths from the current class hierarchy.
     *
     * @return array The list of template paths
     */
    protected function getTemplateFindPaths()
    {
        $templatePaths = [
            $this->getViewPath(),
        ];

        // Traverse the class hierarchy to find additional template paths
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

    /**
     * Sets up a fake theme to map template paths for the Latte renderer.
     */
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
     * Displays a list of model objects.
     * Builds a grid for the model list and renders it.
     *
     * @return string The rendered index view
     */
    public function actionIndex()
    {
        // Prepare parameters for the index view
        $this->templateParams->builder = $this->buildGrid();
        $this->templateParams->model = $this->getModelByAction('index');

        // Create title and breadcrumbs for the index page
        $this->createIndexTitle();

        $this->createIndexBreadcrumbs();

        // Render the index view
        return $this->render('index.latte', $this->templateParams);
    }

    /**
     * Creates the title for the index page.
     * Sets the title based on the model and its attributes.
     */
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

    /**
     * Creates breadcrumbs for the index page.
     * Sets breadcrumbs based on the model's hierarchy.
     */
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
     * Creates a new model object.
     * Redirects to the 'back' URL upon successful creation.
     *
     * @return mixed The response after creating the model
     */
    public function actionCreate()
    {
        return $this->_actionEdit('create');
    }

    /**
     * Updates an existing model object.
     * Redirects to the 'back' URL upon successful update.
     *
     * @param string $id The ID of the model to update
     * @return mixed The response after updating the model
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate()
    {
        return $this->_actionEdit('update');
    }

    /**
     * Handles the creation and editing of a model object.
     * Determines whether the operation is a create or update and processes accordingly.
     *
     * @param bool $isCreate Whether the operation is to create a new model
     * @return mixed The response after processing the model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function _actionEdit($action)
    {
        $model = $this->getModelByAction($action);
        $this->templateParams->model = $model;

        $this->model2titleParams($model);

        $builder = $this->buildForm($model);
        $this->templateParams->builder = $builder;

        $post = $this->request->post();
        if ($this->request->isPost && $builder->data2model($post, $model)) {
            return $this->goBack();
        }

        $this->createEditTitle($action);

        $this->createEditBreadcrumbs($action, $model);

        return $this->render('edit.latte', $this->templateParams);
    }

    public function getModelByAction($action)
    {
        return in_array($action, ['create', 'index'])? $this->createModel() : $this->findModel();
    }

    /**
     * Creates the title for the edit page.
     * Sets the title based on whether the operation is create or update.
     *
     * @param bool $isCreate Whether the operation is to create a new model
     */
    protected function createEditTitle($action)
    {
        if ($this->title) {
            return;
        }

        if ('create' == $action) {
            $this->title = $this->t('Create item', $this->titleParams);
        } elseif (isset($this->titleParams['nameAttribute'])) {
            $this->title = $this->t('Update item "{nameAttribute}"', $this->titleParams);
        } else {
            $this->title = $this->t('Update item');
        }
    }

    /**
     * Sorts the list of model objects based on the provided order.
     * Reorders the models and updates their sort attributes.
     *
     * @return mixed The response after sorting the models
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

        $modelClass = $this->getModelClass();
        $sortAttr = $modelClass::ORDER_ATTR;
        $maxSort = $modelClass::find()->addSelect("max([[{$sortAttr}]])")->scalar();

        // Move objects to the end first, then position them correctly
	// The value of the sort attribute must be unique
        $moved = [];
        $idKey = current($modelClass::primaryKey());
        foreach ($modelClass::findAll($sort) as $model) {
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

        foreach ($modelClass::findAll(array_keys($moved)) as $model) {
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

    /**
     * Maps model attributes to title parameters.
     * Extracts attributes such as name and parent model for use in titles.
     *
     * @param \yii\db\ActiveRecord $model The model object
     */
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

    /**
     * Creates breadcrumbs for the edit page.
     * Sets breadcrumbs based on the model's attributes and operation type.
     *
     * @param bool $action
     * @param \yii\db\ActiveRecord $model The model object
     */
    protected function createEditBreadcrumbs($action, $model)
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

    /**
     * Creates general breadcrumbs for the page.
     * Adds the message category to the breadcrumbs.
     */
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
        $selection = $this->getSelection();
        if (!$selection) {
            return $this->goBack();
        }

        $modelClass = $this->getModelClass();
        $primaryKey = $modelClass::primaryKey();
        $where = [
            $primaryKey[0] => $this->getSelection(),
        ];

        $parentAttr = ParentModel::getParentModelAttr($modelClass);
        if ($parentAttr) {
            $where[$parentAttr] = $this->parentModelID;
        }

        $query = $modelClass::find()->where($where);
        foreach ($query->all() as $model) {
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
     * Retrieves the 'selection' data from the POST request.
     *
     * This method fetches the 'selection' data from the POST request,
     * defaulting to an empty array if 'selection' is not set.
     *
     * @return array The 'selection' data from the POST request.
     */
    public function getSelection()
    {
        return $this->request->post('selection', []);
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
        $modelClass = $this->getModelClass();
        return $this->_findModel($modelClass, $this->getModelID(), $exception404);
    }

    /**
     * Create new model object, and set parent model id
     *
     * @return \yii\db\ActiveRecord the created model
     */
    protected function createModel()
    {
        $model = Yii::createObject($this->getModelClass());

        $parentModelAttr = ParentModel::getParentModelAttr($model);
        if ($parentModelAttr) {
            $model->{$parentModelAttr} = $this->getModelID();
        }

        return $model;
    }

    /**
     * Retrieves the ID of the model from the request parameters.
     *
     * @return string|null The model ID from the request, or null if not present
     */
    public function getModelID()
    {
        return $this->request->get('id');
    }
}