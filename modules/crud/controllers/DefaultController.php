<?php
namespace app\modules\crud\controllers;

use Yii;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;

use app\modules\crud\behaviors\BackUrlBehavior;
use app\modules\crud\grid\SearchModel;
use app\modules\crud\helpers\ClassI18N;
use app\modules\crud\models\ModelInspector;
use app\modules\crud\builder\Form;


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
    public $gridDefaultOrder = [];

    public function behaviors() {
        $behaviors = parent::behaviors();

        $behaviors['backUrl'] = BackUrlBehavior::className();
        $behaviors['access'] = [
            'class' => AccessControl::className(),
            'rules' => [
                [
                    'allow' => true,
                    'roles' => ['?'],
                ],
            ],
        ];

        return $behaviors;
    }

    public function init() {
        parent::init();

        if (!$this->messageCategory && $this->modelClass) {
            $this->messageCategory = ClassI18N::class2messagesPath($this->modelClass);
        }
    }

    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex() {
        $class = $this->modelClass;
        $query = $class::find();

        // add conditions that should always apply here
        $provider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => $this->gridDefaultOrder,
            ],
        ]);

        $inspector = $this->getInspector();
        $inspector->forGrid($class);

        $view = $this->getView();
        $view->title = Yii::t($this->messageCategory, 'List items');

        $filterModel = null;
        if ($this->withFilter) {
            $filterModel = new SearchModel();
            $filterModel->setModel(Yii::createObject($class));
            $filterModel->load(Yii::$app->request->get());
            $filterModel->filter($query);
        }

        return $this->render('index', compact(['provider', 'inspector', 'filterModel']));
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
     * Updates an existing Subscribe model.
     * If update is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id) {
        return $this->_actionEdit($id);
    }

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
     * Deletes an existing Subscribe model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id) {
        $this->findModel($id)->delete();

        return $this->goBack();
    }

    protected function getBuilder($type) {
        switch ($type) {
            case 'form':
                $builder = new Form();
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
     * @return Subscribe the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id = null) {
        if (null === $id) {
            return Yii::createObject($this->modelClass);
        }

        $modelClass = $this->modelClass;
        $model = $modelClass::findOne($id);
        if (null !== $model) {
            return $model;
        }

        throw new NotFoundHttpException('The requested model does not exist.');
    }

   public function getViewPath() {
        $path = parent::getViewPath();
        $path = str_replace('/' . $this->module->id . '/', '/crud/', $path);
        return $path;
    }
}