<?php
namespace Crud\grid;

use Yii;
use yii\helpers\Json;
use yii\helpers\Html;

use Crud\grid\GridViewAsset;
use Crud\grid\toolbar\GridToolbarButtonAsset;
use Crud\grid\Toolbar;

/**
 *
 *
 */
trait GridViewTrait
{
    public $toolbar = Toolbar::class;

    public $baseToolbarButtons = [
        'delete',
        'clearFilter',
    ];
    public $addToolbarButtons = [];
    public $removeToolbarButtons = [];
    public $toolbarButtonOptions = [];

    public $isInsideForm;
    public $surroundForm;
    public $surroundFormAction = '';
    public $surroundFormMethod = 'post';
    public $surroundFormOptions = [];

    public $messageCategory;

    protected function registerDragable()
    {
        if (!$this->dragable) {
            return;
        }

        $this->dragable = Yii::createObject($this->dragableСlass,
                          $this->dragableOptions);

        $this->dragable->attach($this);
    }

    protected function registerJs()
    {
        $id = $this->options['id'];
        $options = Json::htmlEncode($this->getClientOptions());

        $view = $this->getView();
        $view->registerJs("jQuery('#$id').yiiGridView($options);");
    }

    protected function registerAsset()
    {
        $view = $this->getView();
        GridViewAsset::register($view);

        if ($this->baseToolbarButtons || $this->addToolbarButtons || null !== $this->dragable) {
            GridToolbarButtonAsset::register($view);
        }
    }

    public function renderSection($name)
    {
        switch ($name) {
            case '{toolbar}':
                return $this->renderToolbar();

            default:
                return parent::renderSection($name);
        }
    }

    protected function surroundForm($str)
    {
        if (!$this->surroundForm) {
            return $str;
        }

        $begin = Html::beginForm($this->surroundFormAction,
                        $this->surroundFormMethod,
                        $this->surroundFormOptions);

        return $begin . $str . Html::endForm();
    }

    public function renderToolbar()
    {
        $this->createToolbar();

        return $this->toolbar->render();
    }

    protected function createToolbar()
    {
        if (is_object($this->toolbar)) {
            return;
        }

        $desc = is_string($this->toolbar)? ['class' => $this->toolbar] : $this->toolbar;
        $desc['grid'] = $this;

        $this->toolbar = Yii::createObject($desc);
    }
}