<?php
namespace Crud\models;

use yii\web\NotFoundHttpException;

/**
 * This is the model class for table "class_type".
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $class
 * @property bool $is_folder
 */
class ClassType extends \yii\db\ActiveRecord
implements \Crud\models\ModelWithNameAttrInterface
{
    const NAME_ATTR = 'name';

    protected static $type2class;

    protected static $type2code;

    protected static $folderTypes;

    protected static $type2name;

    public static function getTypeByClass($objectOrClass, $exception404 = true)
    {
        if (is_object($objectOrClass)) {
            $objectOrClass = get_class($objectOrClass);
        }

        self::_load();
        $type = array_search($objectOrClass, self::$type2class);
        if (false === $type && $exception404) {
            throw new NotFoundHttpException("Class '{$objectOrClass}' does not exist in type list");
        }

        return $type;
    }

    public static function getTypeByCode($code, $exception404 = true)
    {
        self::_load();
        $type = array_search($code, self::$type2code);

        if ($exception404 && false === $type) {
            throw new NotFoundHttpException("Class with code '{$code}' does not exist in type list");
        }

        return $type;
    }

    public static function isFolderByType($type, $exception404 = true)
    {
        self::_load();
        if (in_array($type, self::$folderTypes)) {
            return true;
        }

        if ($exception404 && !isset(self::$type2name[$type])) {
            throw new NotFoundHttpException("Type '{$type}' does not exist in type list");
        }
    }

    public static function getNameByType($type, $exception404 = true)
    {
        self::_load();
        if ($type && isset(self::$type2name[$type])) {
            return self::$type2name[$type];
        }

        if ($exception404 && !isset(self::$type2name[$type])) {
            throw new NotFoundHttpException("Type '{$type}' does not exist in type list");
        }
    }

    public static function getClassByType($type, $exception404 = true)
    {
        self::_load();
        if ($type && isset(self::$type2class[$type])) {
            return self::$type2class[$type];
        }

        if ($exception404 && !isset(self::$type2name[$type])) {
            throw new NotFoundHttpException("Type '{$type}' does not exist in type list");
        }
    }

    public static function getCodeByType($type, $exception404 = true)
    {
        self::_load();
        if ($type && isset(self::$type2code[$type])) {
            return self::$type2code[$type];
        }

        if ($exception404 && !isset(self::$type2name[$type])) {
            throw new NotFoundHttpException("Type '{$type}' does not exist in type list");
        }
    }

    protected static function _load()
    {
        if (null !== self::$type2class) {
            return;
        }

        self::$type2class = self::$folderTypes = [];
        self::$type2name = self::$type2code = [];

        foreach (self::find()->all() as $type) {
            /* @var $type Type */
            self::$type2name[$type->id] = $type->name;

            if ($type->is_folder) {
                self::$folderTypes[] = $type->id;
            }

            if ($type->class) {
                self::$type2class[$type->id] = $type->class;
            }

            if ($type->code) {
                self::$type2code[$type->id] = $type->code;
            }
        }
    }

    /**
     * Правила для валидации
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
        ];
    }

    /**
     * Имя таблицы в базе данных
     */
    public static function tableName()
    {
        return 'class_type';
    }
}