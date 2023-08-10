<?php
namespace Crud\helpers;

use Yii;
use yii\helpers\ArrayHelper;

use yii\bootstrap\Html as Bootstrap3Html;

use Crud\helpers\Lang;

/**
 *
 */
class Html extends \yii\helpers\Html
{
    protected static $bootstrapIcon3to5Map = [
        'remove' => 'x-lg',
        'plus' => 'plus-lg',
        'move' => 'arrows-move',
        'remove-sign' => 'exclamation-circle',
        'info-sign' => 'info-circle',
        'question-sign' => 'question-circle',
        'ok' => 'check-circle',
    ];

    /**
     * Generates a summary of the validation errors.
     * If there is no validation error, an empty error summary markup will still be generated, but it will be hidden.
     * @param Model|Model[] $models the model(s) whose validation errors are to be displayed.
     * @param array $options the tag options in terms of name-value pairs. The following options are specially handled:
     *
     * - header: string, the header HTML for the error summary. If not set, a default prompt string will be used.
     * - footer: string, the footer HTML for the error summary. Defaults to empty string.
     * - encode: boolean, if set to false then the error messages won't be encoded. Defaults to `true`.
     * - showAllErrors: boolean, if set to true every error message for each attribute will be shown otherwise
     *   only the first error message for each attribute will be shown. Defaults to `false`.
     *   Option is available since 2.0.10.
     *
     * The rest of the options will be rendered as the attributes of the container tag.
     *
     * @return string the generated error summary
     */
    public static function errorSummary($models, $options = [])
    {
        $header = isset($options['header']) ? $options['header'] : '<p>' . Lang::t('yii', 'Please fix the following errors:') . '</p>';
        $footer = ArrayHelper::remove($options, 'footer', '');
        $encode = ArrayHelper::remove($options, 'encode', true);
        $showAllErrors = ArrayHelper::remove($options, 'showAllErrors', false);
        $nl2br = ArrayHelper::remove($options, 'nl2br', true);

        unset($options['header']);
        $lines = self::collectErrors($models, $encode, $showAllErrors, $nl2br);
        if (empty($lines)) {
            // still render the placeholder for client-side validation use
            $content = '<ul></ul>';
            $options['style'] = isset($options['style']) ? rtrim($options['style'], ';') . '; display:none' : 'display:none';
        } else {

            $content = '<ul><li>' . implode("</li>\n<li>", $lines) . '</li></ul>';
        }

        return Html::tag('div', $header . $content . $footer, $options);
    }

    /**
     * Return array of the validation errors
     * @param Model|Model[] $models the model(s) whose validation errors are to be displayed.
     * @param $encode boolean, if set to false then the error messages won't be encoded.
     * @param $showAllErrors boolean, if set to true every error message for each attribute will be shown otherwise
     * only the first error message for each attribute will be shown.
     * @return array of the validation errors
     * @since 2.0.14
     */
    protected static function collectErrors($models, $encode, $showAllErrors, $nl2br)
    {
        $lines = [];
        if (!is_array($models)) {
            $models = [$models];
        }

        foreach ($models as $model) {
            $lines = array_unique(array_merge($lines, $model->getErrorSummary($showAllErrors)));
        }

        // If there are the same error messages for different attributes, array_unique will leave gaps
        // between sequential keys. Applying array_values to reorder array keys.
        $lines = array_values($lines);

        if ($encode) {
            foreach ($lines as &$line) {
                $line = Html::encode($line);
            }
        }

        if ($nl2br) {
            foreach ($lines as &$line) {
                $line = nl2br($line);
            }
        }

        return $lines;
    }

    public static function icon($icon, $class = '')
    {
        if (5 == self::getBootstrapVersion()) {
            $icon = self::$bootstrapIcon3to5Map[$icon] ?? $icon;
            $attrs = ['class' => "bi bi-{$icon}"];
            self::addCssClass($attrs, $class);
            return Html::tag('i', '', $attrs);
        }

        return Bootstrap3Html::icon($icon);
    }

    public static function getSmallSize()
    {
        if (5 == self::getBootstrapVersion()) {
            return 'sm';
        }

        return 'xs';
    }

    public static function getBootstrapVersion()
    {
        if (isset(Yii::$app->extensions['yiisoft/yii2-bootstrap5'])) {
            return 5;
        }

        return 3;
    }
}