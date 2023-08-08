<?php
namespace Crud\widgets;

use Crud\helpers\Html;

use yii\base\InvalidConfigException;

use yii\widgets\ActiveForm;
use yii\bootstrap\InputWidget;

/**
 *
 *
 */
class FileInput extends InputWidget
{
    /**
     * {@inheritdoc}
     */
    public function run()
    {
        // https://github.com/yiisoft/yii2/pull/795
        if ($this->field->inputOptions !== ['class' => 'form-control']) {
            $this->options = array_merge($this->field->inputOptions, $this->options);
        }

        // https://github.com/yiisoft/yii2/issues/8779
        if (!isset($this->field->form->options['enctype'])) {
            $this->field->form->options['enctype'] = 'multipart/form-data';
        }

        if ($this->field->form->validationStateOn === ActiveForm::VALIDATION_STATE_ON_INPUT) {
            $this->field->addErrorClassIfNeeded($this->options);
        }

        //$this->field->addAriaAttributes($this->options);
        // $this->field->adjustLabelFor($this->options);

        $link = $fileUpload = '';
        $dropInputAttrs = ['data-role' => 'drop', 'disabled' => 'disabled', 'id' => null, 'value' => ''];
        $fileInputAttrs = ['data-role' => 'file'];
        if ($this->hasModel()) {
            $dropInput = Html::activeInput('hidden', $this->model, $this->attribute, $dropInputAttrs);
            $fileInput = Html::activeInput('file', $this->model, $this->attribute, $fileInputAttrs);
        } else {
            $dropInput = Html::input('hidden', $this->name, '', $dropInputAttrs);
            $fileInput = Html::input('file', $this->name, '', $fileInputAttrs);
        }

        $str = $dropInput;
        $trashButtonAttrs = ['data-role' => 'remove', 'data-confirm' => 'Удалить?'];
        $value = Html::getAttributeValue($this->model, $this->attribute);
        if ($this->hasModel() && $value) {
            if ($this->model->hasMethod('getWebLink2attachment')) {
                $link = Html::a($value, $this->model->getWebLink2attachment(), ['target' => '_new']);

                $trashButtonAttrs['data-fill'] = 'true';
                $trash = Html::tag('span', Html::icon('trash'), $trashButtonAttrs);

                $str .= Html::tag('div', $link . $trash);
                $str .= $fileInput;
            } else {
                $str = $fileInput;
            }
        } else {
            $trashButtonAttrs['class'] = 'hidden';
            $trash = Html::tag('span', Html::icon('trash'), $trashButtonAttrs);

            $str = $fileInput . $trash;
        }

        echo Html::tag('div', $str, ['data-role' => 'widget-file-input', 'class' => 'widget-file-input']);
    }
}