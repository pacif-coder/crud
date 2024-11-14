<?php
namespace Crud\models\tree_node;

/**
 *
 *
 */
class Folder extends ActiveRecord
implements WithPathInterface
{
    const ORDER_ATTR = 'sort';

    const CHILD_CLASS = null;

    /**
     * Имя таблицы в базе данных
     */
    public static function tableName()
    {
        return 'folder';
    }
}