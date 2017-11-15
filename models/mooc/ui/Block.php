<?php
namespace Mooc\UI;

use Courseware\Container;
use Mooc\DB\Field;
use Mooc\DB\UserProgress;
use Mooc\UI\Section\Section;
use Mooc\UI\Courseware\Courseware;

/**
 * Objects of this class represent a UI component bundling model, view
 * and controller responsibilities in a single package.
 *
 * TODO: (mlunzena) Write more about the general usage or link to a
 * URI!?.
 *  - creation
 *  - initialize
 *  - fields
 *  - views
 *  - handlers
 *  - saving
 *
 * @author  <mlunzena@uos.de>
 *
 * @property int     $id
 * @property string  $title
 * @property Field[] $_fields
 */
abstract class Block {

    /**
     * Link to the dependency injection container used in Mooc.IP
     *
     * @var Container
     */
    protected $container;

    /**
     * This attribute holds the reference to the model (or more
     * exactly to a SimpleORMap instance). Use this in your derived
     * class, if you need to access the model directly, instead of
     * using the fields.
     *
     * @var \Mooc\DB\Block
     */
    protected $_model;

    /**
     * A collection of all fields that this Block instance has access
     * to scoped by the currently logged in User. You may get all
     * Block scoped fields (that is as field that is associated to
     * this block but not to a user) and you may get all User scoped
     * fields (which are associated to this block and to the currently
     * logged in user).
     * Usually you will access these fields by name as an (pseudo)
     * instance attribute:
     *
     * \code
     * // access the 'votes' field like this:
     * $this->votes++;
     *
     * // not like this:
     * $this->_fields['votes']++;
     *
     * \endcode
     *
     * Fields are not automatically created, you have to define them
     * using Block::defineField
     *
     * @var Field[]
     */
    protected $_fields;

    /**
     * @var UserProgress
     */
    private $_progress;

    /**
     * This constructor should not be called. If you need an instance
     * of a derived class of class Block, you should use the
     * BlockFactory.
     *
     * @param Container    $container  the dependency injection container
     * @param \SimpleORMap $model      the model associated to this Block
     *
     * @see BlockFactory::makeBlock
     */
    public function __construct(Container $container, \SimpleORMap $model)
    {
        $this->container = $container;
        $this->_model    = $model;
        $this->_fields   = array();
        $this->initialize();
    }

    /**
     * To define the fields of a block or to setup the required
     * environment of a block, you may define a `initialize` method to
     * do so. Do not override the constructor, initialize is much easier.
     */
    public function initialize()
    {
    }

    /**
     * Returns the underlying model object.
     *
     * @return \Mooc\DB\Block The model
     */
    public function getModel()
    {
        return $this->_model;
    }

    // TODO
    function __get($name)
    {
        // `id` und `title` werden direkt aus dem SORM-Objekt genommen,
        // siehe \Mooc\AbstractBlock
        if ('id' === $name or 'title' === $name) {
            return $this->_model->$name;
        }

        // field must be previously defined
        if (!isset($this->_fields[$name])) {
            throw new \InvalidArgumentException(sprintf("Field '%s' was not defined.", $name));
        }

        return $this->_fields[$name]->content;
    }

    // TODO
    function __set($name, $value)
    {
        // `id` darf nicht geändert werden
        if ('id' === $name) {
            throw new \InvalidArgumentException("Cannot mutate attribute 'id'.");
        }

        // `title` wird direkt im SORM-Objekt geändert
        if ('title' === $name) {
            $this->_model->title = $value;
            $this->_model->store();
            return;
        }

        // field must be previously defined
        if (!isset($this->_fields[$name])) {
            throw new \InvalidArgumentException(sprintf("Field '%s' was not defined.", $name));
        }

        $this->_fields[$name]->content = $value;
    }


    /**
     * @return Field[]
     */
    public function getFields()
    {
        return array_reduce(
            $this->_fields,
            function ($memo, $field) {
                $memo[$field->name] = $field->content;
                return $memo;
            },
            array());
    }

    /**
     * Returns the block's sub types.
     *
     * A block can (optionally) define sub types. For each sub type, the user
     * can create a separate block. Array keys are treated as identifiers,
     * values will be treated as human-readable strings presented to the end-user.
     *
     * @return array The allowed sub types
     */
    public static function getSubTypes()
    {
        return array();
    }

    /**
     * This function is called by the framework. You should not have
     * to call it yourself.
     *
     * If you want to define a view for a derived Block, you have to
     * implement a public instance method called '{name of the
     * view}_view'.
     *
     * These views are then called without parameters. Its return
     * value is then used for output. Therefore it should return some
     * HTML or plain text.
     *
     * An example of such a view:
     *
     * \code
     * class ExampleBlock extends Mooc\UI\Block {
     *
     *   public function student_view()
     *   {
     *     return '<h1>I am a view.</h1>';
     *   }
     * }
     * \endcode
     *
     * This function, Block::render gets the name of a view (for
     * example "student", calls that method ("student_view"), saves
     * the fields of this block, and returns the return value of the
     * called method.
     *
     * To report an error just throw one of the \Mooc\UI\Errors.
     *
     * @param string $view_name the name of the view to call
     * @param array  $context   The execution context
     *
     * @return String  the response to send back
     */
    public function render($view_name = 'student', $context = array())
    {
        $view_method = $view_name . '_view';

        // checken, dass es die View auch gibt!
        if (!is_callable(array($this, $view_method))) {
            throw new Errors\BadRequest('No such view.');
        }

        $timer = \Metrics::startTimer();

        $data = $this->$view_method($context);
        $this->save();
        $result = $this->container['block_renderer']($this, $view_name, $data);

        $key = sprintf('moocip.block.%s.render.%s', strtolower($this->getModel()->type), strtolower($view_name));
        $timer($key, 0.1);

        return $result;
    }

    public function traverseChildren($callback) {
        $results = array();

        foreach ($this->_model->getContentChildren() as $child_model) {
            $child = $this->getBlockFactory()->makeBlock($child_model);
            if ($child) {
                $results[] = $callback($child, $this->container);
            }
        }

        return $results;
    }

    // TODO
    public function handle($name, $data = array())
    {
        $handler = array($this, "{$name}_handler");

        if (!is_callable($handler)) {
            throw new Errors\BadRequest("No such handler");
        }

        $timer = \Metrics::startTimer();

        $result = call_user_func_array($handler, array_slice(func_get_args(), 1));
        $this->save();

        $key = sprintf('moocip.block.%s.handle.%s', strtolower($this->getModel()->type), strtolower($name));
        $timer($key);

        return $result;
    }

    // TODO
    public function getBlockDir()
    {
        $class = new \ReflectionClass(get_called_class());
        return dirname($class->getFileName());
    }

    /**
     * Returns the readable name of a concrete block.
     *
     * @return string
     */
    public function getReadableName()
    {
        $readableName = get_class($this);

        if (defined($readableName.'::NAME')) {
            $readableName = constant($readableName.'::NAME');
        }

        if ($this->_model->sub_type !== null && $this->_model->sub_type !== '') {
            $subTypes = $this->getSubTypes();
            $readableName .= ' ('.$subTypes[$this->_model->sub_type].')';
        }

        return _cw($readableName);
    }

    /**
     * Checks whether the block is editable.
     *
     * By default, all blocks can be modified.
     *
     * @return bool True, if the block is editable, false otherwise
     */
    public function isEditable()
    {
        return true;
    }

    // TODO
    public function toJSON()
    {
        $json = $this->_model->toArray();
        $json['fields'] = $this->getFields();
        $json['readable_name'] = $this->getReadableName();
        $json['editable'] = $this->isEditable();

        return $json;
    }

    /**
     * Exposes properties to be exported.
     *
     * @return array The properties to export
     */
    public function exportProperties()
    {
        return array();
    }

    /**
     * Exposes the block contents that should be exported.
     *
     * @return string The block contents
     */
    public function exportContents()
    {
        return null;
    }

    /**
     * Returns the files referenced by a block or its child blocks.
     *
     * @return array The referenced files
     */
    public function getFiles()
    {
        return array();
    }

    /**
     * An optional additional XML namespace which is used in XML file exports
     * for the attributes exposed by the export() method.
     *
     * @return string The XML namespace
     */
    public function getXmlNamespace()
    {
        return null;
    }

    /**
     * Returns the url of an optional additional XML schema definition file
     * for a particular block type.
     *
     * @return string|null The url to the XSD file
     */
    public function getXmlSchemaLocation()
    {
        return null;
    }

    /**
     * Handle properties being imported.
     *
     * @param array $properties The properties to import
     */
    public function importProperties(array $properties)
    {
    }

    /**
     * Handle the block contents that should be imported.
     *
     * @param string $contents The block contents to import
     * @param array  $files    Mapping of original file ids to imported
     *                         document instances
     */
    public function importContents($contents, array $files)
    {
    }

    /**
     * Return the current user's progress.
     *
     * @return object  the user's progress as a UserProgress object
     */
    public function getProgress()
    {
        if (!isset($this->_progress)) {
            // get it from the DB
            $this->_progress = new UserProgress(
                array(
                    $this->_model->id,
                    $this->container['current_user_id']));
        }

        return $this->_progress;
    }

    public function setGrade($grade)
    {
        // only students of this course get grades
        if (!$this->getCurrentUser()->canUpdate($this->_model)) {
            $this->getProgress()->grade = $grade;
        }
    }

    /**
     * Checks whether a new instance of a block type can be created for a given
     * section.
     *
     * @return bool True if a new block instance is allowed, false otherwise
     */
    public static function additionalInstanceAllowed($container, Section $section, $subType = null)
    {
        return true;
    }

    /**
     * You have to use this method in Block::initialize to define a
     * field for this block specified by its name, scope and a default
     * value. Fields are containers for values, you may store
     * some value in it and you may retrieve that value again.
     *
     *
     * If that field does not exist yet, it will be created when
     * saving this block. If that field exists, it will contain its
     * persisted value.
     *
     * You access the value of a field by its name:
     *
     * \code
     * // define a field named 'foo'
     * $this->defineField('foo', ...);
     *
     * // access that field
     * echo $this->foo;
     * \endcode
     *
     * The scope of a field defines the type of relation to its
     * block. You may choose from either Mooc\SCOPE_BLOCK or
     * Mooc\SCOPE_USER.
     *
     * Choosing Mooc\SCOPE_BLOCK associates that field just to this
     * block. Everytime that you access that field in the context of
     * the same block (same ID), you get the same field.
     *
     * Choosing Mooc\SCOPE_USER associates that field to this block
     * and to a user, the currently logged in user. (So there are
     * actually several such fields, one for each user.) Everytime
     * that field is access in the context of the same block (same ID)
     * and the same user, you get the same field.
     *
     * You setup a default value for that field by providing that
     * value as the 3rd parameter to this method. If you do not store
     * something in that field, its value is that default value.
     *
     * @param string $name     the name of that field
     * @param mixed  $scope    the scope of that field, see above
     * @param mixed  $default  the default value of that field.
     *
     * @throws \InvalidArgumentException if the scope is neither \Mooc\SCOPE_USER
     *                                   nor \Mooc\SCOPE_BLOCK
     */
    protected function defineField($name, $scope, $default)
    {
        // TODO: darf $name alle Zeichen enthalten und beliebig lang sein?

        if (\Mooc\SCOPE_USER === $scope) {
            $user_id = $this->container['current_user_id'];
            $field = new Field(array($this->id, $user_id, $name));
        }

        elseif (\Mooc\SCOPE_BLOCK === $scope) {
            $field = new Field(array($this->id, '', $name));
        }

        else {
            throw new \InvalidArgumentException(sprintf('No such scope "%s"', $scope));
        }

        $field->setDefault($default);
        $this->_fields[$name] = $field;
    }

    // TODO
    public function save()
    {
        if ($this->container['current_user']->isNobody()) {
            return;
        }
        foreach ($this->_fields as $field) {
            $field->store();
        }

        // save the progress if there is one
        if (isset($this->_progress)) {
            $this->_progress->store();
        }
    }

    // enforce current user with 'canUpdate' permission of this block
    protected function authorizeUpdate()
    {
        if (!$this->container['current_user']->canUpdate($this)) {
            throw new Errors\AccessDenied(_cw("Sie sind nicht berechtigt diesen Block zu editieren."));
        }
    }

    protected function requireUpdatableParent($data)
    {
        // we need a valid parent
        if (!isset($data['parent'])) {
            throw new Errors\BadRequest("Parent required.");
        }

        /** @var \Mooc\DB\Block $parent */
        $parent = \Mooc\DB\Block::find($data['parent']);
        if (!$parent || !$parent->isStructuralBlock()) {
            throw new Errors\BadRequest("Invalid parent.");
        }

        if (!$this->getCurrentUser()->canUpdate($parent)) {
            throw new Errors\AccessDenied();
        }

        return $parent;
    }

    /**
     * Returns the block factory.
     *
     * @return BlockFactory
     */
    protected function getBlockFactory()
    {
        return $this->container['block_factory'];
    }

    /**
     * Retrieves the current user from the container.
     *
     * @return \Mooc\User The user
     */
    protected function getCurrentUser()
    {
        return $this->container['current_user'];
    }

    protected function isAuthorized()
    {

        // on sequential courseware progression a student may only
        // access this section if he completed this or the previous
        // sub/chapter
        if (!$this->container['current_user']->canUpdate($this)) {
            $courseware = $this->container['current_courseware'];
            if ($courseware->getProgressionType() === Courseware::PROGRESSION_SEQ && !$this->checkSeqCompletion()) {
                return false;
            }
        }

        // else user may access this section
        return true;
    }

    protected function checkSeqCompletion()
    {
        $uid = $this->container['current_user_id'];
        if ($this->_model->type == "Section") {
            $sub = $this->_model->parent;
        } else {
            $sub = $this->_model->parent->parent;
        }
        // TODO: solve in a more elegant way
        if (!$sub) {
            return true;
        }
        // proceed if this subchapter has been completed by this user
        if ($sub->hasUserCompleted($uid)) {
            return true;
        }
        // else check the previous (sub)chapter for completion
        else {

            // get previous subchapter
            $previous = $sub->previousSibling();

            // if this section's subchapter is the first of the
            // chapter, there is no previous subchapter. Get the
            // previous chapter instead.
            if (!$previous) {
                $previous = $sub->parent->previousSibling();
            }

            // if there is no previous chapter, this section is
            // the very first
            if (!$previous) {
                return true;
            }

            // else check the previous (sub)chapter for completion
            return $previous->hasUserCompleted($uid);
        }
    }
}
