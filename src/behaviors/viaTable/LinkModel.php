<?php
namespace Crud\behaviors\viaTable;

/**
 *
 *
 */
class LinkModel extends \yii\db\ActiveRecord
{
    protected static $_table;

    /**
     * Table name
     */
    public static function tableName()
    {
        return self::$_table;
    }

    public static function setTableName($table)
    {
        self::$_table = $table;
    }
}
