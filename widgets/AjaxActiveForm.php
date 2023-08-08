<?php
namespace Crud\widgets;

use Crud\widgets\AjaxActiveField;

/**
 *
 *
 */
class AjaxActiveForm extends \yii\bootstrap\ActiveForm
{
    public $fieldClass = AjaxActiveField::class;

    /**
     *
     *
     * @throws InvalidCallException if `beginField()` and `endField()` calls are not matching.
     */
    public function run()
    {
        if (!empty($this->_fields)) {
            throw new InvalidCallException('Each beginField() should have a matching endField() call.');
        }

        $html = ob_get_clean();
        if ($this->enableClientScript) {
            $this->registerClientScript();
        }

        return $html;
    }
}
