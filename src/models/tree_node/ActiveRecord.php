<?php
namespace Crud\models\tree_node;

use Yii;
use yii\helpers\Inflector;

use Crud\helpers\ClassI18N;
use Crud\helpers\Lang;
use Crud\helpers\ModelName;
use Crud\helpers\ParentModel;
use Crud\widgets\PathInput;
use Crud\behaviors\SortInit;

use Crud\models as Models;

use Exception;

/**
 *
 *
 */
class ActiveRecord extends \Crud\models\ActiveRecord
implements Models\ModelWithOrderInterface, Models\ModelWithParentInterface
{
    const ORDER_ATTR = 'sort';

    const CHILD_CLASS = null;

    const PARENT_MODEL_ATTR = null;

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['sortInit'] = SortInit::class;

        return $behaviors;
    }

    public function getChildClass()
    {
        throw new Exception('Method "getChildClass" in class ' . static::class . ' not implement');
    }

    protected function afterFormBuild(\yii\base\Event $event)
    {
        /* @var $formBuilder \Crud\builder\FormBuilder */
        $formBuilder = $event->sender;

        if ($this instanceof WithPathInterface) {
            $formBuilder->fieldTypes['path'] = 'crud:path';
            $formBuilder->fieldType2widget['crud:path'] = PathInput::class;
        }
    }

    public function beforeValidate()
    {
        $r = parent::beforeValidate();
        if (!$r) {
            return $r;
        }

        if ($this instanceof WithPathInterface) {
            $nameAttr = ModelName::getNameAttr($this);
            $path = $this->path;
            if (!$path && $nameAttr) {
                $path = $this->{$nameAttr};
            }

            $this->path = static::pathClear($path);
        }

        return $r;
    }

    protected static function pathClear($path)
    {
        $path = trim($path);
        $path = strtolower(Inflector::transliterate($path));
        $path = preg_replace('/[^a-z0-9_]+/', '-', $path);
        $path = trim($path, '-');

        return $path;
    }

    public function rules()
    {
        $rules = $this->nameRules();

        $uniqueRules = $this->nameUniqueRule();
        if ($uniqueRules) {
            $rules = array_merge($rules, $uniqueRules);
        }

        $pathRules = $this->pathRules();
        if ($pathRules) {
            $rules = array_merge($rules, $pathRules);
        }

//        $rules[] = [[static::ORDER_ATTR], 'integer']; ???

        return $rules;
    }

    protected function pathRules()
    {
        if (!($this instanceof WithPathInterface)) {
            return [];
        }

        $rules = [
            'pathIsString' => ['path', 'string', 'max' => 255],
            'pathTrim' => ['path', 'trim'],
        ];

        // path by unique
        $attrs = ['path'];
        $parentAttr = ParentModel::getParentModelAttr($this);
        if ($parentAttr) {
            $attrs[] = $parentAttr;
        }

        $rules['pathUnique'] = ['path', 'unique', 'targetAttribute' => $attrs];

        return $rules;
    }

    protected function nameRules()
    {
        $nameAttr = ModelName::getNameAttr($this);
        if (!$nameAttr) {
            return [];
        }

        $rules = [
            'nameIsString' => [$nameAttr, 'string', 'max' => 255],
            'nameRequired' => [$nameAttr, 'required'],
            'nameTrim' => [$nameAttr, 'trim'],
        ];

        return $rules;
    }

    protected function nameUniqueRule()
    {
        $nameAttr = ModelName::getNameAttr($this);
        if (!$nameAttr) {
            return [];
        }

        $parentAttr = ParentModel::getParentModelAttr($this);
        if (!$parentAttr) {
            return [];
        }

        return [
            'nameUnique' => [$nameAttr, 'unique', 'targetAttribute' => [$nameAttr, $parentAttr]]
        ];
    }
}