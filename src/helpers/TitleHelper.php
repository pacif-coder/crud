<?php
namespace Crud\helpers;

use yii\helpers\Html;

/**
 *
 */
class TitleHelper extends \yii\helpers\Html
{
    public static function quotPair2span($str, $class = 'with-quota')
    {
        $attrs = ['class' => $class];
        if (false !== strpos($str, '«') or false !== strpos($str, '»')) {
            $attrs['class'] = null;
        }

        $str = str_replace("'", '"', $str);
        $template = Html::tag('span', '$1', $attrs);
        return preg_replace('/&quot;+(.*?)&quot;/s', $template,  Html::encode($str));
    }
}