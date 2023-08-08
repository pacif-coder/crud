<?php
namespace Crud\models;

use Yii;
use yii\helpers\Url;
use yii\helpers\Html;

use Crud\helpers\ClassI18N;
use Crud\helpers\Lang;
use Crud\widgets\ProgressBar;
use Crud\builder\FormBuilder;

use Crud\controls\Link;

/**
 *
 */
class ProgressReport extends \yii\base\Model
{
    public $persent;

    public $message;

    public $stopAction;

    protected static $fb_fieldTypes = [
        'persent' => 'progressBar',
        'message' => 'static',
    ];

    protected static $fb_fieldType2widget = [
        'progressBar' => ProgressBar::class,
    ];

    protected static $fb_fieldOptions = [
        'message' => ['encode' => false],
    ];

    protected static $fb_removeFields = ['stopAction'];

    protected static $fb_removeExtraControls = ['save'];

    protected static $fb_readyOnlyFields = ['persent'];

    public function beforeFormBuild(\yii\base\Event $event)
    {
        /* @var $formBuilder FormBuilder */
        $formBuilder = $event->sender;

        if (!isset($formBuilder->form['options'])) {
            $formBuilder->form['options'] = [];
        }
        $options = &$formBuilder->form['options'];

        $options['data-role'] = 'refresh-ajax-form';
        $options['data-ajax-url'] = Url::current(['isAjax' => 1]);

        if ($this->persent >= 100) {
            $formBuilder->fieldOptions['persent']['colorClass'] = 'success';
        }

        if (!$this->stopAction) {
            return;
        }

        $formBuilder->extraControlOptions['cancel'] = [
            'class' => Link::class,
            'action' => $this->stopAction,
            'label' => 'cancel',
            'place' => 'bottom/right',
        ];
    }

    public function asAjax(FormBuilder $builder)
    {
        $formClass = $builder->getFormClass();
        $form = $formClass::begin($builder->getFormConfig());

        $data = [];
        foreach ($builder->fields as $field) {
            $id = Html::getInputId($this, $field);
            $data["#{$id}"] = (string) $builder->field2string($field, $form, $this);
        }
        $formClass::end();

        return $data;
    }

    public function format($text, $classColor = 'info')
    {
        return Html::tag('span', $text, ['class' => "text-{$classColor}"]);
    }

    public function attributeLabels()
    {
        $file = ClassI18N::class2messagesPath(static::class);

        $list = [];
        foreach ($this->attributes() as $attribute) {
            $list[$attribute] = Lang::t($file, $attribute);
        }

        return $list;
    }
}
