<?php
namespace Crud\models\tree_node;

use Yii;

use Crud\models as CrudInterface;
use Crud\helpers\ClassI18N;
use Crud\helpers\Lang;

use Crud\widgets\PathInput;

/**
 *
 *
 * @property int $id
 */
class Base extends CrudInterface\ActiveRecord
implements CrudInterface\ModelWithNameAttrInterface,
           CrudInterface\ModelWithParentInterface
{
    const NAME_ATTR = 'name';

    const PARENT_MODEL_ATTR = 'parent_id';

    protected $node;

    protected $_labels;

    protected function _node()
    {
        if (null !== $this->node) {
            return $this->node;
        }

        $this->node = Node::factory($this->id);
        $this->node->type_id = Type::getTypeByClass($this);
        $this->node->setWithPath($this instanceof WithPathInterface);

        $this->_initLabels();
        $this->node->setAttributeLabels($this->_labels);

        return $this->node;
    }

    public function beforeSave($insert)
    {
        $r = parent::beforeSave($insert);
        if (!$r) {
            return $r;
        }

        $node = $this->_node();
        if (!$node->save()) {
            throw new \Exception(implode("\n", $node->getErrorSummary(true)));
        }

        if ($insert) {
            $this->id = $node->id;
        }

        return $r;
    }

    public function delete1()
    {
        return parent::delete() && $this->node->delete();
    }

    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        return parent::validate($attributeNames, $clearErrors) && $this->_node()->validate(null, $clearErrors);
    }

    public function hasErrors($attribute = null): bool
    {
        return parent::hasErrors($attribute) || $this->_node()->hasErrors($attribute);
    }

    public function getErrors($attribute = null): array
    {
        return array_merge(parent::getErrors($attribute), $this->_node()->getErrors($attribute));
    }

//    public function beforeValidate()
//    {
//        $r = parent::beforeValidate() && $this->node;
//        if (!$r) {
//            return $r;
//        }
//
//        return $r;
//    }

    /**
     *
     */
    public function rules()
    {
        $rules = [
            [['parent_id', 'sort', 'type_id', 'level'], 'integer'],
            [['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => static::class, 'targetAttribute' => ['parent_id' => 'id']],

            [['type_id'], 'exist', 'skipOnError' => true, 'targetClass' => Type::class, 'targetAttribute' => ['type_id' => 'id']],
        ];

        return array_merge($rules, $this->baseRules());
    }

    public function baseRules()
    {
        $rules = [];

        if (!($this instanceof NoNameInterface)) {
            $rules[] = ['name', 'required'];
        }

        if ($this instanceof WithPathInterface) {
            $rules[] = ['path', 'safe'];
        }

        return $rules;
    }

    public function linkedNode()
    {
        return $this->_node();
    }

    public function getParent_id()
    {
        return $this->_node()->parent_id;
    }

    public function setParent_id($id)
    {
        $this->_node()->parent_id = $id;
    }

    public function setName($name)
    {
        $this->_node()->name = $name;
    }

    public function setPath($path)
    {
        $this->_node()->path = $path;
    }

    public function getName()
    {
        return $this->_node()->name;
    }

    public function getPath()
    {
        return $this->_node()->path;
    }

    public function attributeLabels()
    {
        $this->_initLabels();

        return array_merge($this->_node()->attributeLabels(), $this->_labels);
    }

    protected function _initLabels()
    {
        if (null !== $this->_labels) {
            return;
        }

        $labels = parent::attributeLabels();

        $category = ClassI18N::class2messagesPath(static::class);
        if (Lang::isCategoryExist($category)) {
            foreach (['name', 'path'] as $attr) {
                $trans = Yii::t($category, $attr);
                if ($trans != $attr) {
                    $labels[$attr] = $trans;
                }
            }
        }

        $this->_labels = $labels;
    }

    protected function afterFormBuild(\yii\base\Event $event)
    {
        /* @var $formBuilder \Crud\builder\FormBuilder */
        $formBuilder = $event->sender;

        $fields = [];
        if (!($this instanceof NoNameInterface)) {
            $fields[] = 'name';
        }

        if ($this instanceof WithPathInterface) {
            $fields[] = 'path';

            $formBuilder->fieldTypes['path'] = 'path';
            $formBuilder->fieldType2widget['path'] = PathInput::class;
        }

        $formBuilder->fields = array_merge($fields, $formBuilder->fields);
//
//        $formBuilder->fieldset2fields['fdsfs'] = $fields;
//        $formBuilder->fieldHint['path'] = 'ytrbq ntrcn';
    }

    public function fields()
    {
        $fields = parent::fields();

        if (!($this instanceof NoNameInterface)) {
            $fields[] = 'name';
        }

        if ($this instanceof WithPathInterface) {
            $fields[] = 'path';
        }

        return $fields;
    }
}