<?php
namespace Crud\latte;

use yii\base\Model;
use yii\helpers\Html;

use Crud\builder\FormBuilder;
use Crud\builder\GridBuilder;

/**
 *
 */
class CrudTemplateParameters extends \yii\base\BaseObject
{
    /**
     * @var Model
     */
    public $model;

    /**
     * @var FormBuilder|GridBuilder
     */
    public $builder;

    /**
     * @var string
     */
    public $title;

    /**
     * @var object
     */
    public $breadcrumbs;

    /**
     * @var FormBuilder|GridBuilder
     */
    public $view;

    /**
     * @var
     */
    public $context;

    /**
     * @var
     */
    public $app;

    /** @function */
    public function quotPair2span($str, $class = 'with-quota')
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