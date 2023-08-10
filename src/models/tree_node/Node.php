<?php
namespace Crud\models\tree_node;

use Yii;
use Crud\models as CrudInterface;

use yii\helpers\Inflector;
use Crud\widgets\PathInput;

/**
 *
 *
 * @property int $id
 * @property int|null $parent_id
 * @property int|null $sort
 * @property int $level
 * @property string $name Имя
 * @property string $path Имя в ссылке
 * @property int|null $type_id Тип
 *
 * @property Node $parent
 * @property Type $type
 */
class Node extends CrudInterface\ActiveRecord
implements CrudInterface\ModelWithNameAttrInterface,
           CrudInterface\ModelWithParentInterface,
           CrudInterface\ModelWithOrderInterface
{
    const NAME_ATTR = 'name';

    const PARENT_MODEL_ATTR = 'parent_id';

    const ORDER_ATTR = 'sort';

    protected static $fb_removeColumns = [
        'level', 'parent_id'
    ];

    protected static $fb_removeFields = [
        'level', 'parent_id', 'sort', 'type_id'
    ];

    protected $withPath = true;

    protected $image;

    protected $labels;

    protected static $nodeClass;

    /**
     * Factory method for creating or retrieving a node.
     *
     * @param int|null $id The ID of the node to retrieve, or null to create a new node.
     * @return Node The node object.
     */
    public static function factory($id = null)
    {
        $nodeClass = static::getClass();
        if (null === $id) {
            return new $nodeClass();
        }

        return $nodeClass::findOne($id);
    }

    public static function getClass()
    {
        if (static::$nodeClass) {
            return static::$nodeClass;
        }

        return static::$nodeClass = static::class;
    }

    protected function afterFormBuild(\yii\base\Event $event)
    {
        /* @var $formBuilder \Crud\builder\FormBuilder */
        $formBuilder = $event->sender;

        if ($this->withPath) {
            $formBuilder->fieldTypes['path'] = 'path';
            $formBuilder->fieldType2widget['path'] = PathInput::class;
        }
    }

    public function linkedModel($exception404 = true)
    {
        if (!$this->type_id) {
            throw new Exception();
        }

        $class = Type::getClassByType($this->type_id);
        if (!$class) {
            return;
        }

        $model = $class::findOne($this->id);
        if ($model) {
            return $model;
        }

        if ($exception404) {
            throw new NotFoundHttpException("The requested model '{$class}' with id '{$this->id}' does not exist.");
        }
    }

    public function beforeValidate()
    {
        $r = parent::beforeValidate();
        if (!$r) {
            return $r;
        }

        if ($this->withPath) {
            $path = $this->path? : $this->name;
            $path = trim($path);

            $this->path = Inflector::transliterate($path);
            $this->path = preg_replace('/[\s|\+|\-]+/', '-', strtolower($this->path));
        } else {
            $this->path = null;
        }

        return $r;
    }

    public function beforeDelete()
    {
        $r = parent::beforeDelete();
        if (!$r) {
            return $r;
        }

        $class = Type::getClassByType($this->type_id);
        if (!$class) {
            return $r;
        }

        $model = $this->linkedModel(false);
        if (!$model) {
            return $r;
        }

        if (!$model->delete()) {
            $error = implode("\n", $this->getErrorSummary(true));
            $this->addError('id', $error);
            return false;
        }

        return $r;
    }

    public function beforeSave($insert)
    {
        $r = parent::beforeSave($insert);
        if (!$r) {
            return $r;
        }

        if (!$this->level) {
            if ($this->parent_id) {
                $this->level = $this->parent->level + 1;
            } else {
                $this->level = 1;
            }
        }

        if (!$this->sort) {
            $where = ['parent_id' => $this->parent_id];
            $this->sort = self::find()->where($where)->max(self::ORDER_ATTR) + 1;
        }

        return $r;
    }

    /**
     * Правила для валидации
     */
    public function rules()
    {
        $rules =  [
            ['name', 'string', 'max' => 255],
            ['name', 'required'],
            ['name', 'trim'],
            ['name', 'unique', 'targetAttribute' => ['name', 'parent_id']],

            [['parent_id', 'sort', 'type_id', 'level'], 'integer'],
            [['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => self::getClass(), 'targetAttribute' => ['parent_id' => 'id']],

            ['sort', 'unique', 'targetAttribute' => ['sort', 'parent_id']],

            [['type_id'], 'exist', 'skipOnError' => true, 'targetClass' => Type::class, 'targetAttribute' => ['type_id' => 'id']],
        ];

        if ($this->withPath) {
            $rules[] = ['path', 'string', 'max' => 255];
            $rules[] = ['path', 'trim'];
            $rules[] = ['path', 'unique', 'targetAttribute' => ['path', 'parent_id']];
        }

        return $rules;
    }

    public function setWithPath($withPath)
    {
        $this->withPath = $withPath;
    }

    public function setimage($image)
    {
        $this->image = $image;
    }

    public function getimage()
    {
        return $this->image;
    }

    public function attributeLabels()
    {
        if (null !== $this->labels) {
            return $this->labels;
        }

        return $this->labels = parent::attributeLabels();
    }

    public function setAttributeLabels($labels)
    {
        $this->labels = array_merge($this->attributeLabels(), $labels);
    }

    /**
     * Gets query for [[Parent]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(self::getClass(), ['id' => 'parent_id']);
    }

    /**
     * Gets query for [[Type]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getType()
    {
        return $this->hasOne(Type::class, ['id' => 'type_id']);
    }

    /**
     * Имя таблицы в базе данных
     */
    public static function tableName()
    {
        return 'tree_node';
    }
}