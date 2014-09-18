<?php
namespace Mooc\DB;

/**
 * @author  <mlunzena@uos.de>
 *
 * @property int     $id
 * @property string  $type
 * @property string  $sub_type
 * @property int     $parent_id
 * @property Block   $parent
 * @property Block[] $children
 * @property string  $seminar_id
 * @property \Course $course
 * @property string  $title
 * @property int     $position
 * @property int     $publication_date
 * @property int     $chdate
 * @property int     $mkdate
 */
class Block extends \SimpleORMap
{

    public $errors = array();

    /**
     * Give primary key of record as param to fetch
     * corresponding record from db if available, if not preset primary key
     * with given value. Give null to create new record
     *
     * @param mixed $id primary key of table
     */
    public function __construct($id = null) {
        $this->db_table = 'mooc_blocks';

        $this->belongs_to['course'] = array(
            'class_name'  => 'Course',
            'foreign_key' => 'seminar_id');


        $this->belongs_to['parent'] = array(
            'class_name'  => 'Mooc\\DB\\Block',
            'foreign_key' => 'parent_id');

        $this->has_many['children'] = array(
            'class_name'        => 'Mooc\\DB\\Block',
            'assoc_foreign_key' => 'parent_id',
            'assoc_func'        => 'findByParent_id',
            'on_delete'         => 'delete',
            'on_store'          => 'store'
        );


        # TODO: $this->default_values['type'] = array_pop(explode('\\', get_called_class()));

        $this->registerCallback('before_create', 'ensureSeminarId');
        $this->registerCallback('before_create', 'ensurePositionId');
        $this->registerCallback('before_store',  'validate');

        $this->registerCallback('after_delete',  'destroyFields');
        $this->registerCallback('after_delete',  'destroyUserProgress');
        $this->registerCallback('after_delete',  'updatePositionsAfterDelete');

        parent::__construct($id);
    }

    /**
     * Sets the seminar id of the block just before storing it to the database
     * if the current course information is stored in the session.
     */
    protected function ensureSeminarId()
    {
        // TODO: (mlunzena) we cannot be sure to have a
        // SessionSeminar, so we must get that value somewhere else
        if ($this->seminar_id === null && isset($GLOBALS['SessionSeminar'])) {
            $this->seminar_id = $GLOBALS['SessionSeminar'];
        }
    }

    /**
     * Calculates the position of a new block by counting the already existing
     * blocks on the same level.
     */
    protected function ensurePositionId()
    {
        if ($this->parent_id !== null && $this->position === null) {
            $this->position = static::countBySQL(
                'parent_id = ? ORDER BY position ASC',
                array($this->parent_id)
            );
        }
    }

    public function getAncestors()
    {
        $ancestors = array();
        $cursor = $this;
        while ($cursor->parent) {
            $ancestors[] = $cursor->parent;
            $cursor = $cursor->parent;
        }

        return array_reverse($ancestors);
    }

    public static function findByParent_id($id)
    {
        return static::findBySQL('parent_id = ? ORDER BY position ASC', array($id));
    }


    public static function findCourseware($cid)
    {
        return current(self::findBySQL('seminar_id = ? AND parent_id IS NULL LIMIT 1', array($cid)));
    }


    // enumerate all known structural block classes
    private static $structural_block_classes = array('Courseware', 'Chapter', 'Subchapter', 'Section');

    /**
     * Return all known structural block classes.
     *
     * @return array  all known structure classes
     */
    public static function getStructuralBlockClasses()
    {
        return self::$structural_block_classes;
    }

    /**
     * Returns whether this block is a structural block.
     *
     * @return bool  `true` if it is a structural block, `false` otherwise
     */
    public function isStructuralBlock()
    {
        return in_array($this->type, self::$structural_block_classes);
    }

    /**
     * checks, if block is valid
     *
     * @return boolean true or false
     */
    function validate() {
        if (!strlen(trim($this->title))) {
            $this->errors[] = "Title may not be empty.";
            return false;
        }
        return true;
    }

    /**
     * Remove associated Fields on delete.
     */
    function destroyFields()
    {
        Field::deleteBySQL('block_id = ?', array($this->id));
    }

    /**
     * Remove associated UserProgress on delete.
     */
    function destroyUserProgress()
    {
        UserProgress::deleteBySQL('block_id = ?', array($this->id));
    }

    /**
     * Reflects changes in position if a block on one level is deleted.
     */
    public function updatePositionsAfterDelete()
    {
        if (!$this->parent) {
            return;
        }

        $db = \DBManager::get();
        $stmt = $db->prepare(sprintf(
            'UPDATE
              %s
            SET
              position = position - 1
            WHERE
              parent_id = :parent_id AND
              position > :position',
            $this->db_table
        ));
        $stmt->bindValue(':parent_id', $this->parent->id);
        $stmt->bindValue(':position', $this->position);
        $stmt->execute();
    }

    /**
     * Update child sorting
     *
     * @param array $positions the new sort order
     */
    function updateChildPositions($positions)
    {
        $query = sprintf(
            'UPDATE %s SET position = FIND_IN_SET(id, ?) WHERE parent_id = ?',
            $this->db_table);
        $args = array(join(',', $positions), $this->id);

        $db = \DBManager::get();
        $st = $db->prepare($query);
        $st->execute($args);
    }

    /**
     *
     * @param int $timestamp
     * @return boolean
     */
    function isPublished($timestamp = null)
    {
        if (is_null($timestamp)) {
            $timestamp = time();
        }

        // check if parent blocks are published
        if ($this->parent && !$this->parent->isPublished($timestamp)) {
            return false;
        }

        // check if block is published
        return $this->publication_date <= $timestamp;
    }
}
