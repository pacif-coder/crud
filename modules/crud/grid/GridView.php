<?php
namespace app\modules\crud\grid;

use Yii;
use yii\grid\GridView as BaseGridView;
use yii\grid\GridViewAsset as BaseGridViewAsset;
use yii\helpers\Json;

use app\modules\crud\grid\GridViewAsset;
use app\modules\crud\grid\toolbar\GridToolbarButtonAsset;

/**
 *
 *
 */
class GridView extends BaseGridView {
    public $layout = "{toolbar}\r\n{items}\r\n{summary}\r\n{pager}";

    public $addCheckboxColumn = true;
    public $checkboxColumn = 'app\modules\crud\grid\column\CheckboxColumn';

    public $addActionColumn = false;
    public $actionColumn = 'yii\grid\ActionColumn';

    public $toolbar = 'app\modules\crud\grid\Toolbar';
    public $toolbarButtons = [
        'delete',
        'clearFilter',
    ];

    protected function initColumns() {
        $isGuessColumn = empty($this->columns);

        if ($this->addCheckboxColumn) {
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

    public function getClearFilterUrl() {
        $filterUrl = isset($this->filterUrl) ? $this->filterUrl : Yii::$app->request->url;

        $url = '';
        $components = parse_url($filterUrl);
        if (isset($components['scheme'])) {
            $url .= $components['scheme'] . '://';
        }

        if (isset($components['host'])) {
            $url .= $components['host'];
        }

        if (isset($components['port'])) {
            $url .= ':' . $components['port'];
        }

        if (isset($components['path'])) {
            $url .= $components['path'];
        }

        if (isset($components['query'])) {
            $get = [];
            parse_str($components['query'], $get);
            if (isset($get[$this->filterModel->formName()])) {
                unset($get[$this->filterModel->formName()]);
            }

            $query = http_build_query($get);
            if ($query) {
                $url .= '?' . $query;
            }
        }

        return $url;
    }

    /**
     * Runs the widget.
     */
    public function run() {
        $id = $this->options['id'];
        $options = Json::htmlEncode($this->getClientOptions());

        $view = $this->getView();
        BaseGridViewAsset::register($view);
        GridViewAsset::register($view);

        GridToolbarButtonAsset::register($view);

        $view->registerJs("jQuery('#$id').yiiGridView($options);");

        parent::run();
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
        $desc = is_string($this->toolbar)? ['class' => $this->toolbar] : $this->toolbar;
        $desc['grid'] = $this;
        $toolbar = Yii::createObject($desc);

        return $toolbar->render();
    }
}