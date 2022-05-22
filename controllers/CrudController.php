<?php
namespace app\modules\crud\controllers;

use Yii;
use yii\web\NotFoundHttpException;
use yii\base\InvalidConfigException;

use app\modules\crud\helpers\ClassI18N;
use app\modules\crud\helpers\ParentModel;
use app\modules\crud\helpers\ModelName;
use app\modules\crud\models\ModelWithParentInterface;
use app\modules\crud\widgets\Breadcrumbs;

/**
 * Default controller for the CRUD module
 *
 * @property string $title Page title
 */
abstract class CrudController extends BaseController
{
    public $modelClass;
    public $modelSearchClass;

    public $parentModelID;

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
    }

    /**
     * Show model objects list
     * @return string
     */
    public function actionIndex()
    {
        // build grid
        $builder = $this->buildGrid();

        $this->createIndexTitle();

        $this->createIndexBreadcrumbs();

        return $this->render('index', compact(['builder']));
    }

    protected function createIndexTitle()
    {
        $model = $this->createModel();
        $this->title = $this->t('List items', $this->getTitleParams($model));
    }

    protected function createIndexBreadcrumbs()
    {
        $view = $this->getView();
        $br = new Breadcrumbs();
        $view->params['breadcrumbs'] = $br->createIndexBreadcrumbs($this->createModel());
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
    protected function _actionEdit($create)
    {
        $model = $create? $this->createModel() : $this->findModel();

        $builder = $this->buildForm($model);
        if ($builder->data2model($this->request->post(), $model)) {
            return $this->goBack();
        }

        $this->createEditTitle($create, $model);

        $this->createEditBreadcrumbs($model);

        return $this->render('edit', compact(['model', 'builder']));
    }

    protected function createEditTitle($isCreate, $model)
    {
        $params = $this->getTitleParams($model);
        if ($isCreate) {
            $this->title = $this->t('Create item');
        } elseif (isset($params['nameAttribute'])) {
            $this->title = $this->t('Update item "{nameAttribute}"', $params);
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
        $this->createReadTitle($model);

        $builder = $this->getFromBuilder();
        return $this->render('read', compact(['model', 'builder']));
    }

    protected function createReadTitle($model)
    {
        $params = $this->getTitleParams($model);
        if (isset($params['nameAttribute'])) {
            $this->title = $this->t('Show item "{nameAttribute}"', $params);
        } else {
            $this->title = $this->t('Show item');
        }
    }

    protected function getTitleParams($model)
    {
        $params = [];

        $name = ModelName::getName($model);
        if ($name) {
            $params['nameAttribute'] = ModelName::getName($model);
        }

        $parents = ParentModel::loadParents($model);
        if ($parents) {
            $params['parentModelName'] = end($parents)['name'];
        }

        return $params;
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

    protected function createEditBreadcrumbs($model)
    {
        $br = new Breadcrumbs();

        $view = $this->getView();
        $view->params['breadcrumbs'] = $br->createEditBreadcrumbs($model, $this->getBackUrl());
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