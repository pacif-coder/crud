<?php
namespace Crud\widgets;

use yii\helpers\Html;
use yii\helpers\Url;

use Crud\widgets\assets\JuiWidgetAsset;

class AutoComplete extends \yii\widgets\InputWidget
{
    public $sourceRoute;

    public $source = [];

    protected static $type = 'autocomplete';

    public function run()
    {
        $this->registerClientScript();

        $options = $this->options;
        $options['id'] = $this->getId();
        $options['data-widget'] = static::$type;
        $options['data-source'] = $this->getSource();

        return Html::activeTextInput($this->model, $this->attribute, $options);
    }

    public function getSource()
    {
        if (null !== $this->sourceRoute) {
            return Url::toRoute($this->sourceRoute);
        }

        return $this->source;
    }

    public function registerClientScript()
    {
        $view = $this->getView();
        JuiWidgetAsset::register($view);
    }
}