<?php
namespace app\modules\crud\controllers;

use Yii;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\base\InvalidConfigException;

use app\modules\crud\behaviors\BackUrlBehavior;
use app\modules\crud\grid\SearchModel;
use app\modules\crud\builder\FormBuilder;
use app\modules\crud\builder\GridBuilder;
use app\modules\crud\helpers\ClassI18N;

/**
 * Default controller for the `admin` module
 */
class DefaultController extends Controller {
    public $modelClass;
    public $messageCategory;
    public $modelSearchClass;

    public $withFilter = false;
    public $filterInGrid = true;
    public $addCreateButton = true;
    public $gridDefaultOrder;

    public function behaviors() {
        $behaviors = parent::behaviors();
        $behaviors['backUrl'] = BackUrlBehavior::className();

        return $behaviors;
    }

    public function init() {
        parent::init();

        if (!$this->modelClass) {
            throw new InvalidConfigException('Not find model class');
        }

        if (!$this->messageCategory && $this->modelClass) {
            $this->messageCategory = ClassI18N::class2messagesPath($this->modelClass);
        }
    }

    /**
     * Show model objects list
     * @return string
     */
    public function actionIndex() {
        $view = $this->getView();
        $view->title = Yii::t($this->messageCategory, 'List items');

        $query = $this->getGridQuery();
        $filterModel = $this->getGridFilter($query);
        $gridOptions = $this->getGridOptions($query, $filterModel);

        return $this->render('index', compact(['gridOptions', 'filterModel']));
    }

    protected function getGridOptions($query, $filterModel) {
        $builder = $this->getBuilder('grid');
        $builder->build($this->modelClass);

        return [
            'dataProvider' => $this->getGridProvider($query, $builder),
            'filterModel' => $this->filterInGrid && $filterModel ? $filterModel : null,
            'showHeader' => true,
            'columns' => $builder->columns,
        ];
    }

    protected function getGridProvider($query, $builder) {
        $options = [
            'query' => $query,
        ];

        if (null !== $this->gridDefaultOrder) {
            $options['sort']['defaultOrder'] = $this->gridDefaultOrder;
        } elseif ($builder->nameAttr) {
            $options['sort']['defaultOrder'] = [$builder->nameAttr => SORT_ASC];
        }

        return new ActiveDataProvider($options);
    }

    protected function getGridFilter($query) {
        if (!$this->withFilter) {
            return;
        }

        $filterModel = new SearchModel();
        $filterModel->setModel(Yii::createObject($this->modelClass));
        $filterModel->load(Yii::$app->request->get());
        $filterModel->filter($query);

        return $filterModel;
    }

    protected function getGridQuery() {
        $modelClass = $this->modelClass;
        return $modelClass::find();
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'index' page.
     * @return mixed
     */
    public function actionCreate() {
        return $this->_actionEdit();
    }

    /**
     * Updates an existing model object.
     * If update is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id) {
        return $this->_actionEdit($id);
    }

    /**
     * Create and edit object nodel
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function _actionEdit($id = null) {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->save();

            return $this->goBack();
        }

        $builder = $this->getBuilder('form');
        $builder->build($model);

        $view = $this->getView();
        $isCreate = null === $id;
        if ($isCreate) {
            $view->title = Yii::t($this->messageCategory, 'Create item');
        } elseif ($builder->nameAttr) {
            $name = $builder->nameAttr;
            $view->title = Yii::t($this->messageCategory, 'Update item "{nameAttribute}"', ['nameAttribute' => $model->{$name}]);
        } else {
            $view->title = Yii::t($this->messageCategory, 'Update item');
        }

        return $this->render('edit', compact(['model', 'builder']));
    }

    /**
     * Deletes an existing model object.
     * If deletion is successful, the browser will be redirected to the 'back' url.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id) {
        $model = $this->findModel($id, false);
        if ($model) {
            $model->delete();
        }

        return $this->goBack();
    }

    /**
     * Deletes an existing model objects.
     * If deletion is successful, the browser will be redirected to the 'back' url.
     * @return mixed
     */
    public function actionMassDelete() {
        $modelClass = $this->modelClass;
        $selection = Yii::$app->request->post('selection', []);

        foreach ($modelClass::findAll($selection) as $model) {
            $model->delete();
        }

        return $this->goBack();
    }

    protected function getBuilder($type) {
        switch ($type) {
            case 'form':
                $builder = new FormBuilder();
                break;

            case 'grid':
                $builder = new GridBuilder();
                break;
        }
        $builder->controller2this($this);

        return $builder;
    }

    /**
     * Finds the model based on its primary key value.
     *
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return \yii\db\ActiveRecord the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id = null, $exception = true) {
        $modelClass = $this->modelClass;
        if (null === $id) {
            return Yii::createObject($modelClass);
        }

        $model = $modelClass::findOne($id);
        if (null !== $model) {
            return $model;
        }

        if ($exception) {
            throw new NotFoundHttpException('The requested model does not exist.');
        }
    }

    public function getViewPath() {
        $path = parent::getViewPath();
        $path = str_replace('/' . $this->module->id . '/', '/crud/', $path);
        return $path;
    }
}