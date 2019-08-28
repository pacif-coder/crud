<?php
namespace app\modules\crud\grid;

use Yii;
use yii\grid\GridView as BaseGridView;
use yii\grid\GridViewAsset as BaseGridViewAsset;
use yii\widgets\BaseListView;
use yii\helpers\Json;

use app\modules\crud\grid\GridViewAsset;
use app\modules\crud\grid\toolbar\GridToolbarButtonAsset;


/**
 *
 *
 */
class GridView extends BaseGridView {
    public $layout = "{toolbar}\r\n{items}\r\n{summary}\r\n{pager}";

    public $addCheckboxColumn;
    public $checkboxColumn = 'app\modules\crud\grid\column\CheckboxColumn';

    public $addActionColumn = false;
    public $actionColumn = 'yii\grid\ActionColumn';

    public $toolbar = 'app\modules\crud\grid\Toolbar';
    public $baseToolbarButtons = [
        'delete',
        'clearFilter',
    ];
    public $addToolbarButtons = [];
    public $removeToolbarButtons = [];
    public $toolbarButtonOptions = [];

    protected function initColumns() {
        $isGuessColumn = empty($this->columns);

        $addCheckboxColumn = $this->addCheckboxColumn;
        // If there is no direct order to add or not a column with checkbox, add it
        // yourself, if there are buttons working with this column
        if (null === $addCheckboxColumn) {
            $this->createToolbar();
            $addCheckboxColumn = $this->toolbar->isNeedCheckboxColumn();
        }

        if ($addCheckboxColumn) {
            if (is_string($this->checkboxColumn)) {
                $checkboxColumn = ['class' => $this->checkboxColumn];
            } else {
                $checkboxColumn = $this->checkboxColumn;
            }

            array_unshift($this->columns, $checkboxColumn);
        }

        if ($isGuessColumn) {
            $this->guessColumns();
        }

        if ($this->addActionColumn) {
            if (is_string($this->actionColumn)) {
                $this->columns[] = ['class' => $this->actionColumn];
            } else {
                $this->columns[] = $this->actionColumn;
            }
        }

        parent::initColumns();
    }

    /**
     * Runs the widget.
     */
    public function run() {
        $view = $this->getView();
        BaseGridViewAsset::register($view);
        GridViewAsset::register($view);

        if ($this->baseToolbarButtons || $this->addToolbarButtons) {
            GridToolbarButtonAsset::register($view);
        }

        BaseListView::run();

        $id = $this->options['id'];
        $options = Json::htmlEncode($this->getClientOptions());
        $view->registerJs("jQuery('#$id').yiiGridView($options);");
    }

    public function renderSection($name) {
        switch ($name) {
            case '{toolbar}':
                return $this->renderToolbar();
            default:
                return parent::renderSection($name);
        }
    }

    public function renderToolbar() {
        $this->createToolbar();

        return $this->toolbar->render();
    }

    protected function createToolbar() {
        if (is_object($this->toolbar)) {
            return;
        }

        $desc = is_string($this->toolbar)? ['class' => $this->toolbar] : $this->toolbar;
        $desc['grid'] = $this;

        $this->toolbar = Yii::createObject($desc);
    }
}