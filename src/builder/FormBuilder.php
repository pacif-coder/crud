<?php
namespace Crud\builder;

use Yii;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm as BaseActiveForm;

use Crud\builder\Base;
use Crud\helpers\Enum;
use Crud\helpers\Html;
use Crud\helpers\Lang;
use Crud\helpers\ParentModel;
use Crud\models\ModelWithOrderInterface;
use Crud\widgets\ActiveForm;
use Crud\widgets\ActiveFormBootstrap5;


/**
 * XXX
 *
 */
class FormBuilder extends Base
{
    public $removeFields = [];
    public $removeSortField = true;
    public $removeParentField = true;

    public $hideWhenCreating = [];

    public $hideWhenUpdating = [];

    public $onlyWhenCreating = [];

    public $onlyWhenUpdating = [];

    public $readyOnlyWhenCreating = [];

    public $readyOnlyWhenUpdating = [];

    public $addFieldsAfter = [];

    public $fieldHint = [];

    // @XXX implement
    public $fieldPlaceholder = [];

    public $extraProcessedFields = [];

    public $fieldset2fields;
    public $fieldsetLegends = [];
    public $fieldsetAttrs = [];
    public $fieldsBeforeFieldsetLegend = [];
    public $fieldsAfterFieldsetLegend = [];

    public $formExtraControls = ['save', 'cancel'];

    public $form = [
        'class' => null,
        'layout' => 'horizontal',

        'fieldConfig' => [
            'hintOptions' => [
                'tag' => 'div',
            ],
        ],
    ];

    protected $_extraControlVar = 'form';
    protected $_extraControlDefPlace = 'bottom/right';

    public function init()
    {
        parent::init();

        if (5 == Html::getBootstrapVersion()) {
            $this->form['class'] = ActiveFormBootstrap5::class;
        } else {
            $this->form['class'] = ActiveForm::class;
        }
    }

    public function build(Model $model)
    {
        $this->_checkBuilded();

        $modelClass = get_class($model);
        if ($modelClass != $this->modelClass) {
            $this->setModelClass($modelClass);
        }

        $this->_isExtraControlCreated = false;
        foreach (['fieldTypes', 'type2fields', 'fieldOptions', 'enumFields'] as $param) {
            if (null === $this->{$param}) {
                $this->{$param} = [];
            }
        }

        foreach ($this->type2fields as $type => $fields) {
            if (is_string($fields)) {
                $fields = preg_split('/\s+/', preg_replace('/^\s+|\s+$/', '', $fields));
            }

            foreach ($fields as $field) {
                $this->fieldTypes[$field] = $type;
            }
        }

        $this->beforeBuild();

        $this->readOnlyOn($model);

        $allows = $model->activeAttributes();
        foreach ($this->fieldTypes as $attr => $type) {
            if ('static' == $type || 'staticControl' == $type) {
                $allows[] = $attr;
            }
        }
        $allows = array_merge($allows, $this->readyOnlyFields);

        if (null === $this->fields) {
            $this->fields = array_intersect($model->attributes(), $allows);
            if ($model instanceof ActiveRecord) {
                $this->fields = array_diff($this->fields, $this->modelClass::primaryKey());
            }

            if ($this->removeSortField && $model instanceof ModelWithOrderInterface) {
                $order = $modelClass::ORDER_ATTR;
                if (is_array($order)) {
                    $order = array_keys($order);
                } else {
                    $order = [$order];
                }

                $this->fields = array_diff($this->fields, $order);
            }

            if ($this->removeParentField && ($parentAttr = ParentModel::getParentModelAttr($model))) {
                $this->fields = array_diff($this->fields, [$parentAttr]);
            }

            $this->fields = array_diff($this->fields, $this->removeFields);
            foreach ($this->addFieldsAfter as $afterField => $fields) {
                if (!in_array($afterField, $this->fields)) {
                    $this->fields = array_merge($this->fields, $fields);
                    continue;
                }

                $index = array_search($afterField, $this->fields);
                if (false === $index) {
                    continue;
                }

                array_splice($this->fields, $index, 0, $fields);
            }
        } else {
            $notRule = array_diff($this->fields, $allows);
            if ($notRule) {
                $notRule = implode("', '", $notRule);
                //throw new InvalidConfigException("No exist rules on '{$notRule}' fields");
            }
        }

        $this->hideOn($model);

        foreach ($this->fields as $field) {
            $type = $this->getType($field, $model);
            if (!$type) {
                continue;
            }

            $this->fieldTypes[$field] = $type;
            if (in_array($type, $this->enumFieldTypes) || in_array($field, $this->enumFields) || isset($this->enumOptions[$field])) {
                $this->initEnumOptions($model, $field);
            }
        }

        $this->createExtraControls();

        $this->afterBuild();

        $this->extraControlsToPlace();
    }

    public function data2model($data, $model)
    {
        $skipDataFields = array_merge($this->readyOnlyFields, $this->removeFields);
        foreach ($this->fieldTypes as $field => $type) {
            if ('static' == $type || 'staticControl' == $type) {
                $skipDataFields[] = $field;
            }
        }
        $skipDataFields = array_diff($skipDataFields, $this->extraProcessedFields);

        $formName = $model->formName();
        if ($skipDataFields && isset($data[$formName])) {
            foreach ($skipDataFields as $field) {
                if (array_key_exists($field, $data[$formName])) {
                    unset($data[$formName][$field]);
                }
            }
        }

        $model->load($data);
        return $model->save();
    }

    protected function readOnlyOn($model)
    {
        if (!is_a($model, ActiveRecord::class)) {
            return;
        }

        /* @var $model ActiveRecord */
        if ($this->readyOnlyWhenCreating && $model->isNewRecord) {
            $this->readyOnlyFields = array_merge($this->readyOnlyFields, $this->readyOnlyWhenCreating);
        }

        if ($this->readyOnlyWhenUpdating && !$model->isNewRecord) {
            $this->readyOnlyFields = array_merge($this->readyOnlyFields, $this->readyOnlyWhenUpdating);
        }
    }

    protected function hideOn($model)
    {
        if (!is_a($model, ActiveRecord::class)) {
            return;
        }

        /* @var $model ActiveRecord */
        if ($this->hideWhenCreating && $model->isNewRecord) {
            $this->fields = array_diff($this->fields, $this->hideWhenCreating);
        }

        if ($this->hideWhenUpdating && !$model->isNewRecord) {
            $this->fields = array_diff($this->fields, $this->hideWhenUpdating);
        }
    }

    protected function initEnumOptions($model, $attr)
    {
        if (!in_array($attr, $this->enumFields)) {
            $this->enumFields[] = $attr;
        }

        if (isset($this->enumOptions[$attr])) {
            $this->initEnumOptionsByDesc($model, $attr);
        } elseif (Enum::isEnum($model, $attr)) {
            $this->enumOptions[$attr] = Enum::getList($model, $attr);
        }

        if ($this->isAddEmptyEnumOption($attr) && isset($this->enumOptions[$attr])) {
            $this->enumOptions[$attr] = ArrayHelper::merge(['' => $this->emptyEnumOptionLabel], $this->enumOptions[$attr]);
        }
    }

    protected function getType($attr, $model)
    {
        $this->inspectAttr($attr, $model);

        if (isset($this->fieldTypes[$attr])) {
            return $this->fieldTypes[$attr];
        }

        $type = null;
        switch ($this->innerType[$attr]) {
            case 'oneToMany':
                $type = 'checkboxList';
                break;

            case 'oneToOne':
                $type = 'select';
                break;
        }

        if (null !== $type) {
            return $type;
        }

        if (in_array($attr, $this->readyOnlyFields)) {
            return 'static';
        }

        if (is_a($model, ActiveRecord::class) && null !== ($type = $this->getControlTypeByDBColumn($attr))) {
            return $type;
        }

        if (null !== ($type = $this->getControlTypeByValidator($model, $attr))) {
            return $type;
        }

        if (in_array($attr, $this->enumFields) || isset($this->enumOptions[$attr])) {
            return 'select';
        }
    }

    public function getNotFieldsetFields()
    {
        $notInFieldSets = $this->fields;
        foreach ($this->fieldset2fields as $fieldSet => $fields) {
            if (is_string($fields)) {
                $fields = preg_split('/\s+/', trim($fields));
            }

            $notInFieldSets = array_diff($notInFieldSets, $fields);

            $fields = array_intersect($fields, $this->fields);
            if ($fields) {
                $this->fieldset2fields[$fieldSet] = $fields;
            } else {
                unset($this->fieldset2fields[$fieldSet]);
            }
        }

        return $notInFieldSets;
    }

    public function fieldsBeforeFieldsetLegend2string($fieldset, BaseActiveForm $form, Model $model)
    {
        if (!isset($this->fieldset2fields[$fieldset]) || !isset($this->fieldsBeforeFieldsetLegend[$fieldset])) {
            return '';
        }

        $fields = array_intersect($this->fieldset2fields[$fieldset], $this->fieldsBeforeFieldsetLegend[$fieldset]);
        return $this->_fieldsInput2string($fields, $form, $model);
    }

    public function fieldsAfterFieldsetLegend2string($fieldset, BaseActiveForm $form, Model $model)
    {
        if (!isset($this->fieldset2fields[$fieldset]) || !isset($this->fieldsAfterFieldsetLegend[$fieldset])) {
            return '';
        }

        $fields = array_intersect($this->fieldset2fields[$fieldset], $this->fieldsAfterFieldsetLegend[$fieldset]);
        return $this->_fieldsInput2string($fields, $form, $model);
    }

    protected function _fieldsInput2string($fields, BaseActiveForm $form, Model $model)
    {
        $str = '';
        foreach ($fields as $field) {
            $str .= (string) $this->field2string($field, $form, $model)->parts['{input}'];
        }
        return $str;
    }

    public function skipFieldsetLegendFields($fieldset, $fields)
    {
        if (isset($this->fieldsBeforeFieldsetLegend[$fieldset])) {
            $fields = array_diff($fields, $this->fieldsBeforeFieldsetLegend[$fieldset]);
        }

        if (isset($this->fieldsAfterFieldsetLegend[$fieldset])) {
            $fields = array_diff($fields, $this->fieldsAfterFieldsetLegend[$fieldset]);
        }

        return $fields;
    }

    public function getFieldsetLegend($fieldset)
    {
        if (isset($this->fieldsetLegends[$fieldset])) {
            return $this->fieldsetLegends[$fieldset];
        }

        return Lang::t($this->messageCategory, $fieldset);
    }

    public function getFormClass()
    {
        if (is_string($this->form)) {
            return $this->form;
        }

        return $this->form['class'];
    }

    public function getFormConfig()
    {
        if (is_string($this->form)) {
            return [];
        }

        $config = $this->form;
        if (isset($config['class'])) {
            unset($config['class']);
        }

        return $config;
    }

    public function formBody2string($form, $model)
    {
        $str = '';
        if ($this->fieldset2fields && $this->fields) {
            $notInFieldSets = $this->getNotFieldsetFields();
            if ($notInFieldSets) {
                $str .= "<!-- not in fieldset fields -->\r\n";
                $str .= $this->fields2string($notInFieldSets, $form, $model);
                $str .= "\r\n";
            }

            foreach ($this->fieldset2fields as $fieldset => $fields) {
                $str .= "<!-- fieldset '{$fieldset}' -->\r\n";
                $str .= Html::beginTag('fieldset', isset($this->fieldsetAttrs[$fieldset]) ? $this->fieldsetAttrs[$fieldset] : []);

                $legend = $this->fieldsBeforeFieldsetLegend2string($fieldset, $form, $model);
                $legend .= $this->getFieldSetLegend($fieldset);
                $legend .= $this->fieldsAfterFieldsetLegend2string($fieldset, $form, $model);
                $str .= Html::tag('legend', $legend);

                $fields = $this->skipFieldsetLegendFields($fieldset, $fields);
                $str .= $this->fields2string($fields, $form, $model);

                $str .= Html::endTag('fieldset') . "\r\n";
            }
        } else {
            $str .= $this->fields2string($this->fields, $form, $model);
        }

        return $str;
    }
}
