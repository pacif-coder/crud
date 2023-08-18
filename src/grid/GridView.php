<?php
namespace Crud\grid;

use Yii;
use yii\grid\GridViewAsset as BaseGridViewAsset;
use yii\widgets\BaseListView;

use Crud\grid\column\CheckboxColumn;
use Crud\grid\column\DataColumn;
use Crud\helpers\Html;

use Crud\controls\CopyMessageCategoryInterface;

use Crud\grid\Dragable;

/**
 *
 *
 */
class GridView extends \yii\grid\GridView
implements CopyMessageCategoryInterface
{
    use GridViewTrait;

    public $dataColumnClass = DataColumn::class;

    public $layout = "{toolbar}\r\n{items}\r\n{summary}\r\n{pager}";

    public $addCheckboxColumn;
    public $checkboxColumn = CheckboxColumn::class;

    public $addActionColumn = false;
    public $actionColumn = 'yii\grid\ActionColumn';

    public $messageCategory;

    public $dragable;
    public $dragableСlass = Dragable::class;
    public $dragableOptions = [];

    public $renamedLink2ModelAttr = [];

    public function init()
    {
        parent::init();

        if (is_array($this->pager) && !isset($this->pager['class'])) {
            $this->pager['class'] = Html::getBootstrapClass('LinkPager');
        }
    }

    protected function initColumns()
    {
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

        if ($this->messageCategory) {
            foreach ($this->columns as $i => $column) {
                if (is_a($column, CopyMessageCategoryInterface::class)) {
                    $column->messageCategory = $this->messageCategory;
                }
            }
        }
    }

    /**
     * Runs the widget.
     */
    public function run()
    {
        $this->registerDragable();

        $this->options['data-is-inside-form'] = $this->isInsideForm;

        $view = $this->getView();
        BaseGridViewAsset::register($view);

        BaseListView::run();

        $this->registerJs();
        $this->registerAsset();
    }

    public function renderTableBody(): string
    {
        return $this->surroundForm(parent::renderTableBody());
    }

    /**
     * Renders a table row with the given data model and key.
     * @param mixed $model the data model to be rendered
     * @param mixed $key the key associated with the data model
     * @param int $index the zero-based index of the data model among the model array returned by [[dataProvider]].
     * @return string the rendering result
     */
    public function renderTableRow($model, $key, $index)
    {
        $cells = [];
        /* @var $column Column */
        foreach ($this->columns as $column) {
            $cells[] = $column->renderDataCell($model, $key, $index);
        }

        if (is_callable($this->rowOptions) || $this->rowOptions instanceof Closure) {
            $options = call_user_func($this->rowOptions, $model, $key, $index, $this);
        } else {
            $options = $this->rowOptions;
        }
        $options['data-key'] = is_array($key) ? json_encode($key) : (string) $key;

        return Html::tag('tr', implode('', $cells), $options);
    }
}
