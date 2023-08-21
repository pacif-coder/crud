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
}