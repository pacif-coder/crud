<?php
namespace Crud\controls;

use Yii;
use yii\helpers\Url;

use yii\bootstrap\Html as Bootstrap3Html;

use Crud\behaviors\BackUrlBehavior;
use Crud\helpers\ClassI18N;
use Crud\helpers\Lang;
use Crud\helpers\Html;
use Crud\models\ClassType;
use Crud\models\tree_node\ActiveRecord as TreeActiveRecord;

use ReflectionClass;

/**
 * @XXX
 *
 */
class Base extends \yii\base\BaseObject
implements CopyMessageCategoryInterface
{
    public $align;

    public $label;

    public $title;

    public $place;

    public $name;

    public $icon = '';

    public $size = '';

    public $baseClass = 'btn';

    public $colorClass = 'btn-info';

    public $messageCategory;

    public $order;

    public $action;

    public $controller;

    public $modelClass;

    public $checkPermission;

    public $params;

    public $removeParams;

    public $backUrl;

    public $backUrlHash;

    public $isShow = true;

    public $disabled;

    public $options = [];

    const TYPE_PARAM = 'type';

    protected $id;

    protected static $isAddAction;

    protected static $isUseDefMessageCategory;

    protected $defMessageCategory;

    protected static $autoIdPrefix = 'fb-';

    protected static $counter = 0;

    public function init()
    {
        parent::init();

        if (!$this->defMessageCategory) {
            $this->defMessageCategory = ClassI18N::class2messagesPath('crud\controls\Button');
        }
    }

    public function getContent()
    {
        $content = '';

        $icon = $this->getIcon();
        if ($icon) {
            $content .= $icon . ' ';
        }

        $content .= $this->getTransLabel();

        return $content;
    }

    public function getTransLabel()
    {
        return $this->t($this->getLabel());
    }

    public function t($str)
    {
        $category = static::$isUseDefMessageCategory? $this->defMessageCategory : $this->messageCategory;
        return Lang::t($category, $str);
    }

    public function getIcon()
    {
        return $this->icon? Html::icon($this->icon) : '';
    }

    public function getLabel()
    {
        if (false === $this->label) {
            return null;
        }

        if ($this->label) {
            return $this->label;
        }

        if ($this->action) {
            return $this->label = ucfirst(str_replace('-', ' ', $this->action));
        }

        if ($this->controller) {
            return $this->label = ucfirst(str_replace('-', ' ', $this->controller));
        }

        $ref = new ReflectionClass($this);
        return $this->label = lcfirst($ref->getShortName());
    }

    public function getName()
    {
        if ($this->name) {
            return $this->name;
        }

        if ($this->action) {
            return $this->name = $this->action;
        }
    }

    public function html()
    {
        $atrrs = $this->getAttrs();

        foreach ($atrrs as $atrr => $value) {
            if (is_bool($value)) {
                $value = ($value) ? 1 : 0;
                $atrrs[$atrr] = $value;
            }
        }

        return Html::button($this->getContent(), $atrrs);
    }

    public function getAttrs()
    {
        $attrs = $this->options;
        Html::addCssClass($attrs, $this->baseClass);
        Html::addCssClass($attrs, $this->colorClass);

        if ('small' == $this->size) {
            $smallSize = Html::getSmallSize();
            Html::addCssClass($attrs, "btn-{$smallSize}");
        }

        $attrs['id'] = $this->getId();

        $this->addActionAttr($attrs);
        $this->addDisabledAttr($attrs);

        return $attrs;
    }

    protected function addActionAttr(&$attrs)
    {
        if (!static::$isAddAction || !$this->action) {
            return;
        }

        $attrs['data-url'] = $this->getUrl();
        $attrs['data-action'] = $this->action;
    }

    public function getUrl()
    {
        if (null === $this->action && null === $this->controller && null === $this->modelClass) {
            return;
        }

        $get = $this->processingGet();

        $controller = $this->getController();
        if ($controller) {
            $get[0] = $controller . '/' . $this->action;
        } else {
            $get[0] = $this->action;
        }

        return Url::to($get);
    }

    protected function processingGet()
    {
        $get = Yii::$app->request->get();

        if ($this->modelClass && is_a($this->modelClass, TreeActiveRecord::class, true)) {
            $get[self::TYPE_PARAM] = ClassType::getTypeByClass($this->modelClass);
        }

        foreach ((array) $this->params as $param => $value) {
            $get[$param] = $value;
        }

        foreach ((array) $this->removeParams as $removeParam) {
            if (isset($get[$removeParam])) {
                unset($get[$removeParam]);
            }
        }

        if ($this->backUrl) {
            $get = BackUrlBehavior::addBackUrl($get, $this->backUrlHash);
        } elseif (isset($get[BackUrlBehavior::BACK_URL_PARAM])) {
            unset($get[BackUrlBehavior::BACK_URL_PARAM]);
        }

        return $get;
    }

    protected function getController()
    {
        if ($this->controller) {
            return $this->controller;
        }

        if ($this->modelClass && Yii::$app->has('class2controller')) {
            return Yii::$app->class2controller->getController($this->modelClass);
        }
    }

    protected function addDisabledAttr(&$attrs)
    {
        if (null === $this->disabled) {
            return;
        }

        if ($this->disabled) {
            Html::addCssClass($attrs, 'disabled');
        } else {
            Html::removeCssClass($attrs, 'disabled');
        }
    }

    public function isShow()
    {
        return $this->isShow;
    }

    public function getId($autoGenerate = true)
    {
        if ($autoGenerate && $this->id === null) {
            $this->id = static::$autoIdPrefix . static::$counter++;
        }

        return $this->id;
    }

    /**
     * Sets the ID of the widget.
     * @param string $value id of the widget.
     */
    public function setId($value)
    {
        $this->_id = $value;
    }

    public function __toString()
    {
        return $this->html();
    }
}