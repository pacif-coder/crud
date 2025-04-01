<?php
namespace Crud\grid\column;

use Yii;
use yii\base\Model;

use Crud\helpers\Html;

use Closure;

/**
 *
 *
 */
class DataColumn extends \yii\grid\DataColumn
{
    use TruncateColumnTrait;

    /**
     * @var string
     */
    public $truncateClass;

    public $filterWidgetClass;

    public function init()
    {
        parent::init();

        if (!isset($this->filterInputOptions['class'])) {
            return;
        }

        // only Bootstrap5
        if (5 != Html::getBootstrapVersion()) {
            return;
        }

        // only dropdawn
        if (!(is_array($this->filter) || 'boolean' === $this->format)) {
            return;
        }

        // change control class name
        if ('form-control' == $this->filterInputOptions['class']) {
            $this->filterInputOptions['class'] = 'form-select';
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function renderFilterCellContent()
    {
        if (!$this->filterWidgetClass) {
            return parent::renderFilterCellContent();
        }

        $model = $this->grid->filterModel;
        if ($this->filter === false || !($model instanceof Model) || $this->filterAttribute === null || !$model->isAttributeActive($this->filterAttribute)) {
            return parent::renderFilterCellContent();
        }

        $config = [
            'model' => $model,
            'attribute' => $this->filterAttribute,
        ];

        if (is_array($this->filter)) {
            $config['items'] = $this->filter;
        }
        $config = array_merge($config, $this->filterInputOptions);

        return $this->filterWidgetClass::widget($config);
    }

    public function renderDataCell($model, $key, $index)
    {
        $content = $this->renderDataCellContent($model, $key, $index);
        $contentOptions = $this->getContentOptions($model, $key, $index);

        return Html::tag('td', $content, $contentOptions);
    }

    protected function renderDataCellContent($model, $key, $index)
    {
        $str = parent::renderDataCellContent($model, $key, $index);

        return $this->truncateContent($str);
    }

    protected function getContentOptions($model, $key, $index)
    {
        if (is_callable($this->contentOptions) || $this->contentOptions instanceof Closure) {
            return call_user_func($this->contentOptions, $model, $key, $index, $this);
        }

        return $this->contentOptions;
    }
}