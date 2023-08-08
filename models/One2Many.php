<?php
namespace Crud\models;

use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

use Exception;

/**
 *
 */
class One2Many extends \yii\db\ActiveRecord
{
    protected static $fromAttr;
    protected static $toAttr;
    protected static $idAttr;

    protected static $table;

    public static function changeLink(ActiveQuery $queryLink, $new)
    {
        if (!$new && !is_array($new)) {
            $new = [];
        }

        static::initByQueryLink($queryLink);
        $id = static::getModelId($queryLink);

        $exist = ArrayHelper::getColumn(self::findAll([self::$fromAttr => $id]),
                self::$toAttr);

        $delete = array_diff($exist, $new);
        if ($delete) {
            self::deleteAll([self::$fromAttr => $id, self::$toAttr => $delete]);
        }

        $add = array_diff($new, $exist);
        if ($add) {
            foreach ($add as $addId) {
                $tmp = new self([self::$fromAttr => $id,
                                 self::$toAttr => $addId]);

                $tmp->save();
            }
        }
    }

    public static function getExist(ActiveQuery $queryLink)
    {
        static::initByQueryLink($queryLink);
        $id = static::getModelId($queryLink);

        return ArrayHelper::getColumn(self::findAll([self::$fromAttr => $id]),
                                      self::$toAttr);
    }

    public static function getFromAttr()
    {
        return self::$fromAttr;
    }

    public static function getToAttr()
    {
        return self::$toAttr;
    }

    protected static function getModelId($queryLink)
    {
        return $queryLink->primaryModel->{self::$idAttr};
    }

    public static function initByQueryLink($queryLink)
    {
        $linkDesc = $queryLink->link;
        if (count($linkDesc) > 1) {
            throw new Exception('Unsupport db link description');
        }
        self::$toAttr = current($linkDesc);

        $viaLinkDesc = $queryLink->via->link;
        if (count($viaLinkDesc) > 1) {
            throw new Exception('Unsupport via link description');
        }
        self::$fromAttr = key($viaLinkDesc);

        self::$idAttr = current($viaLinkDesc);

        self::$table = current($queryLink->via->from);
    }

    public static function tableName()
    {
        return static::$table;
    }
}
