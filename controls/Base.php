<?php
namespace app\modules\crud\controls;

use Yii;
use yii\bootstrap\Html;

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

    public $icon = '';

    public $baseClass = 'btn';

    public $sizeClass = '';

    public $colorClass = 'btn-info';

    public $messageCategory;

    public $order;

    public $options = [];

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

        return $attrs;
    }

    public function __toString()
    {
        return $this->html();
    }
}