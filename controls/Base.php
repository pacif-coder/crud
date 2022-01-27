<?php
namespace app\modules\crud\controls;

use Yii;
use yii\bootstrap\Html;
use yii\helpers\Url;

use app\modules\crud\helpers\ClassI18N;

use ReflectionClass;

/**
 * @XXX
 *
 */
class Base extends \yii\base\BaseObject
{
    public $align;

    public $label = '';

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

    public $options = [];

    protected static $isAddAction;

    public function init()
    {
        parent::init();

        if (!$this->messageCategory) {
            $this->messageCategory = ClassI18N::class2messagesPath('app\modules\crud\controls\Button');
        }
    }

    public function getContent()
    {
        $content = '';
        if ($this->icon) {
            $content .= Html::icon($this->icon) . ' ';
        }

        if (!$this->label) {
            $ref = new ReflectionClass($this);
            $this->label = lcfirst($ref->getShortName());
        }

        $content .= Yii::t($this->messageCategory, $this->label);

        return $content;
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

        if ($this->name) {
            $attrs['id'] = $this->name;
        } elseif ($this->action) {
            $attrs['id'] = $this->action;
        }

        $this->addActionAttr($attrs);

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
        if (!$this->action) {
            return;
        }

        $get = Yii::$app->request->get();

        if ($this->controller) {
            $get[0] = $this->controller . '/' . $this->action;
        } else {
            $get[0] = $this->action;
        }

        return Url::to($get);
    }

    public function isShow()
    {
        return true;
    }

    public function __toString()
    {
        return $this->html();
    }
}