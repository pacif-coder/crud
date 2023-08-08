<?php
namespace Crud\behaviors;

use Yii;
use yii\db\ActiveRecord;
use yii\base\ModelEvent;

use Crud\helpers\ClassI18N;
use Crud\helpers\Lang;
use Crud\helpers\ModelName;
use Crud\models\tree_node\Node;


/**
 *
 */
class NotDeleteLindedModel extends \yii\base\Behavior
{
    public $linkAttr;

    public $messageCategory;

    public $defMessageCategory;

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }

    public function init()
    {
        parent::init();

        $this->defMessageCategory = ClassI18N::class2messagesPath(Node::class);
    }

    public function beforeDelete(ModelEvent $event)
    {
        if (!$event->isValid) {
            return;
        }

        if (!$this->messageCategory) {
            $this->messageCategory = ClassI18N::class2messagesPath(get_class($this->owner));
        }

        foreach ((array) $this->linkAttr as $attr) {
            $method = "get{$attr}";
            $model = $this->owner->{$method}()->one();
            if (!$model) {
                continue;
            }

            $params = [
                'ownerName' => ModelName::getName($this->owner),
                'modelName' => ModelName::getName($model),
            ];

            $message = "The object '{ownerName}' is used for '{modelName}' and cannot be deleted";
            $categorys = [$this->messageCategory, $this->defMessageCategory];
            $tMessage = Lang::t($categorys, $message, $params);
            $this->owner->addError($attr, $tMessage);
            $event->isValid = false;
        }
    }
}