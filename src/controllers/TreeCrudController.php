<?php
namespace Crud\controllers;

use Yii;

use Crud\grid\column\tree_node\ActionLinkWithTypeColumn;
use Crud\helpers\ModelName;
use Crud\models\tree_node\Folder;
use Crud\models\Type;
use Crud\widgets\TreeMenu;

use Exception;

/**
 * TreeCrudController class
 *
 * This class extends the `CrudController` to add functionality for handling hierarchical (tree-like) structures.
 * It provides methods to manage tree menus and to operate in tree mode, where entities can have a parent-child relationship.
 */
class TreeCrudController extends CrudController
{
    /**
     * @var string The class name of the root entity in the tree. Default is Folder::class.
     */
    public $rootClass = Folder::class;

    /**
     * @var bool|null Indicates whether the tree menu is active.
     * Initialized to null and set in the `beforeAction` method if the controller operates in tree mode.
     */
    public $hasTreeMenu;

    /**
     * @var array add TreeMenu to Latte template global use class
     */
    public $treeCrudUseClass = [
        TreeMenu::class,
    ];

    /**
     * @var string The class name for the edit column used to handle two actions - 'index' and 'update'
     */
    public $editColumnClass = ActionLinkWithTypeColumn::class;

    /**
     * @var array Attributes for configuring action links in the 'index' action's link column
     * is necessary for the second link icon to appear in ActionLink
     *
     * 'secondAction' specifies the action to be taken on the second icon
     * 'backUrl' indicates whether to use a back URL
     * 'secondActionBackUrl' determines if the back URL should be used for the second action
     */
    public $indexActionLinkColumnAttrs = [
        'secondAction' => 'update',
        'backUrl' => false,
        'secondActionBackUrl' => true,
    ];

    /**
     * Indicates whether the controller is operating in tree mode.
     * This variable is used to determine if tree mode is active.
     *
     * @var bool|null
     */
    public $isTreeMode;

    /**
     * Initializes the controller.
     * Merges tree-specific classes into the global use classes and calls the parent's init method.
     */
    public function init()
    {
        $this->globalUseClass = array_merge($this->globalUseClass, $this->treeCrudUseClass);

        parent::init();
    }

    /**
     * Executes before any action is performed.
     * Show tree menus only in tree mode
     *
     * @param \yii\base\Action $action The action to be executed.
     * @return bool Whether the action should continue to be executed.
     */
    public function beforeAction($action): bool
    {
        $r = parent::beforeAction($action);
        if (!$r || !$this->isTreeMode()) {
            return $r;
        }

        if (null === $this->hasTreeMenu) {
            $this->hasTreeMenu = $this->isTreeMode();
        }

        return $r;
    }

    /**
     * Fills the model class property based on the request parameters.
     *
     * If not in tree mode, the parent's `fillModelClass` method is called.
     * Otherwise, determines the appropriate model class based on the 'type' and 'id' request parameters.
     *
     * @throws Exception
     */
    public function fillModelClass()
    {
        // Not in tree mode
        if (!$this->isTreeMode()) {
            return parent::fillModelClass();
        }

        // Retrieve parameters from the request
        $type = $this->request->get('type');
        $id = $this->request->get('id');

        // If there is no id or type - this is the root entity
        if (!$id && !$type) {
            $this->modelClass = $this->rootClass;
            return;
        }

        // Just an id without type is not enough
        if ($id && !$type) {
            throw new Exception('Type must be defined');
        }

        // Set the model class based on the type
        $this->modelClass = Type::getClassByType($type);
    }

    /**
     * Executes before the grid is built.
     *
     * Adjusts the grid builder's settings based on whether the controller is in tree mode.
     *
     * @param \yii\base\Event $event The event triggered before the grid is built.
     */
    protected function beforeGridBuild(\yii\base\Event $event)
    {
        parent::beforeGridBuild($event);

        if (!$this->isTreeMode()) {
            return;
        }

        /* @var $gridBuilder \Crud\builder\GridBuilder */
        $gridBuilder = $event->sender;

        // Set the edit column class for tree mode
        $gridBuilder->editColumnClass = $this->editColumnClass;

        $modelClass = $this->getModelClass();
        $type = Type::getTypeByClass($modelClass);
        $action = Type::isFolderByType($type)? 'index' : 'update';
        $nameAttr = ModelName::getNameAttr($modelClass);

        // Set the default action for the name attribute column if not already set
        if (!isset($gridBuilder->columnOptions[$nameAttr]['action'])) {
            $gridBuilder->columnOptions[$nameAttr]['action'] = $action;
        }

        // If the action is 'index', update the column options from $indexActionLinkColumnAttrs property
        if ('index' != $action) {
            return;
        }

        // is necessary for the second icon to appear in ActionLink on name column
        $options = $gridBuilder->columnOptions[$nameAttr]?? [];
        foreach ($this->indexActionLinkColumnAttrs as $attr => $value) {
            // Skip setting the attribute if it already exists in the options array
            if (array_key_exists($attr, $options)) {
                continue;
            }

            $options[$attr] = $value;
        }

        $gridBuilder->columnOptions[$nameAttr] = $options;
    }

    /**
     * Determines if the controller operates in tree mode.
     * First, we look at the value of the isTreeMode variable, if it is filled we use it.
     * Otherwise, tree mode is active when the request does not have a model name in the GET parameters.
     *
     * @return bool Whether the controller is in tree mode.
     */
    public function isTreeMode()
    {
        if (null !== $this->isTreeMode) {
            return $this->isTreeMode;
        }

        return !$this->hasModelNameInGet();
    }

    /**
     * Creates the breadcrumbs for the current view.
     *
     * Adds a 'withType' entry to the breadcrumbs array based on the tree mode status and calls the parent's method.
     */
    protected function createBreadcrumbs()
    {
        $this->breadcrumbs += ['withType' => $this->isTreeMode()];

        parent::createBreadcrumbs();
    }
}