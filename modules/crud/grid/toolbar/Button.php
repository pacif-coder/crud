<?php
namespace app\modules\crud\grid\toolbar;

use Yii;
use yii\bootstrap\Html;
use app\modules\crud\helpers\ClassI18N;
use yii\web\Request;
use yii\helpers\ArrayHelper;

/**
 * @XXX
 *
 */
class Button extends \yii\base\BaseObject {
    public $grid;

    public $align;

    public $label = '';

    public $icon = '';

    public $baseClass = 'btn';

    public $sizeClass = 'btn-xs';

    public $colorClass = 'btn-info';

    public $messageCategory;

    public $options = [];

    public function init() {
        parent::init();

        if (!$this->messageCategory) {
            $this->messageCategory = ClassI18N::class2messagesPath('app\modules\crud\grid\toolbar\Button');
        }
    }

    public function getContent() {
        $content = '';
        if ($this->icon) {
            $content .= Html::icon($this->icon) . ' ';
        }
        $content .= Yii::t($this->messageCategory, $this->label);

        return $content;
    }

    public function html() {
        return Html::button($this->getContent(), $this->getAttrs());
    }

    public function getAttrs() {
        $attrs = $this->options;
        Html::addCssClass($attrs, $this->baseClass);
        Html::addCssClass($attrs, $this->colorClass);
        Html::addCssClass($attrs, $this->sizeClass);

        if ($this->grid) {
            $attrs['data-target'] = $this->grid->options['id'];
        }

        return $attrs;
    }

    public function addCsrf(&$attrs) {
        $request = Yii::$app->getRequest();
        if (!($request instanceof Request)) {
            return;
        }

        $csrf = ArrayHelper::remove($this->options, 'csrf', true);
        if (!$csrf || !$request->enableCsrfValidation) {
            return;
        }

        $attrs['data-csrf-param'] = $request->csrfParam;
        $attrs['data-csrf-token'] = $request->getCsrfToken();
    }
}