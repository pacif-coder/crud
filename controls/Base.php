<?php
namespace app\modules\crud\controls;

use Yii;
use yii\bootstrap\Html;
use yii\helpers\Url;

use app\modules\crud\behaviors\BackUrlBehavior;
use app\modules\crud\helpers\ClassI18N;

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

    public $baseClass = 'btn';

    public $sizeClass = '';

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

    public $isShow = true;

    public $disabled;

    public $options = [];

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
            $this->defMessageCategory = ClassI18N::class2messagesPath('app\modules\crud\controls\Button');
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
        return Yii::t($category, $str);
    }

    public function getIcon()
    {
        return $this->icon? Html::icon($this->icon) : '';
    }

    public function getLabel()
    {
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
        Html::addCssClass($attrs, $this->sizeClass);

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

        $get = Yii::$app->request->get();

        foreach ((array) $this->params as $param => $value) {
            $get[$param] = $value;
        }

        foreach ((array) $this->removeParams as $removeParam) {
            if (isset($get[$removeParam])) {
                unset($get[$removeParam]);
            }
        }

        $controller = $this->getController();
        if ($controller) {
            $get[0] = $controller . '/' . $this->action;
        } else {
            $get[0] = $this->action;
        }

        if ($this->backUrl) {
            $get = BackUrlBehavior::addBackUrl($get);
        }

        return Url::to($get);
    }

    protected function getController()
    {
        if ($this->controller) {
            return $this->controller;
        }

        if ($this->modelClass) {
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