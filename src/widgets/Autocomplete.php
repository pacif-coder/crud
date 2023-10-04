<?php
namespace Crud\widgets;

use yii\helpers\Html;
use yii\helpers\Url;

use Crud\widgets\assets\JuiWidgetAsset;

class Autocomplete extends \yii\widgets\InputWidget
{
    public $sourceRoute;

    public $source = [];

    public function run()
    {
        $this->registerClientScript();

        $options = $this->options;
        $options['id'] = $this->getId();
        $options['data-widget'] = 'autocomplete';

        if (null !== $this->sourceRoute) {
            $options['data-source'] = Url::toRoute($this->sourceRoute);
        } else {
            $options['data-source'] = $this->source;
        }

        return Html::activeTextInput($this->model, $this->attribute, $options);
    }

    public function registerClientScript()
    {
        $view = $this->getView();
        JuiWidgetAsset::register($view);
    }
}