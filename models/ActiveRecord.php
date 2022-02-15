<?php
namespace app\modules\crud\models;

use Yii;
use app\modules\crud\helpers\ClassI18N;

/**
 *
 *
 */
class ActiveRecord extends \yii\db\ActiveRecord
{
    protected static $fb_fields;
    protected static $fb_removeFields = [];
    protected static $fb_addFieldsAfter = [];

    protected static $fb_fieldTypes;
    protected static $fb_type2fields;
    protected static $fb_fieldOptions;
    protected static $fb_fieldAddClass = [];

    protected static $fb_readyOnlyFields = [];

    protected static $fb_fieldHint = [];

    protected static $fb_fieldset2fields;
    protected static $fb_fieldsetLegends = [];
    protected static $fb_fieldsetAttrs = [];
    protected static $fb_fieldsBeforeFieldsetLegend = [];
    protected static $fb_fieldsAfterFieldsetLegend = [];

    protected static $fb_fieldType2fieldMethod = [];
    protected static $fb_fieldType2widget = [];
    protected static $fb_fieldType2widgetOptions = [];

    protected static $fb_enumFields;
    protected static $fb_enumOptions;
    protected static $fb_translationEnumOptions = [];
    protected static $fb_addEmptyEnumOption = true;
    protected static $fb_emptyEnumOptionLabel = '---';

    protected static $fb_formExtraControls = ['save', 'cancel'];
    protected static $fb_gridExtraControls = ['create'];
    protected static $fb_addExtraControls = [];
    protected static $fb_removeExtraControls = [];
    protected static $fb_extraControlOptions = [];

    protected static $fb_extraProcessedFields = [];

    public function attributeLabels()
    {
        $file = ClassI18N::class2messagesPath(static::class);

        $list = [];
        foreach ($this->attributes() as $attribute) {
            $list[$attribute] = Yii::t($file, $attribute);
        }

        return $list;
    }
}