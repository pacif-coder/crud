<?php
namespace Crud\widgets;

use yii\helpers\Json;
use yii\widgets\MaskedInputAsset;

/**
 * XXX Add desc
 *
 */
class MaskedInput extends \yii\widgets\MaskedInput
{
    public function registerClientScript()
    {
        $js = '';
        $view = $this->getView();
        $this->initClientOptions();
        if (!empty($this->mask)) {
            $this->clientOptions['mask'] = $this->mask;
        }

        if (is_array($this->definitions) && !empty($this->definitions)) {
            $js .= ucfirst(self::PLUGIN_NAME) . '.extendDefinitions(' . Json::htmlEncode($this->definitions) . ');';
        }
        if (is_array($this->aliases) && !empty($this->aliases)) {
            $js .= ucfirst(self::PLUGIN_NAME) . '.extendAliases(' . Json::htmlEncode($this->aliases) . ');';
        }

        $id = $this->options['id'];
        $encOptions = empty($this->clientOptions) ? '{}' : Json::htmlEncode($this->clientOptions);
        $js .= 'jQuery("#' . $id . '").' . self::PLUGIN_NAME . '(' . $encOptions . ');';
        MaskedInputAsset::register($view);
        $view->registerJs($js);
    }
}