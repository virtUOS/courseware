<?php
namespace Mooc;

/**
 * @author  <mlunzena@uos.de>
 */
class AbstractBlock extends \SimpleORMap
{

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


        // this should not be used
        $this->belongs_to['parent'] = array(
            'class_name'  => 'Mooc\\AbstractBlock',
            'foreign_key' => 'parent_id');

        // this should not be used
        $this->has_many['children'] = array(
            'class_name' => 'Mooc\\AbstractBlock',
            'assoc_foreign_key' => 'parent_id',
            'assoc_func' => 'findByParent_id');


        $this->default_values['type'] = array_pop(explode('\\', get_called_class()));

        $this->registerCallback('before_create', 'ensureSeminarId');
        $this->registerCallback('before_create', 'ensurePositionId');

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

    public static function findByParent_id($id)
    {
        return static::findBySQL('parent_id = ? ORDER BY position ASC', array($id));
    }

    /**
     * @param string primary key
     * @return SimpleORMap|NULL
     */
    public static function find($id)
    {
        $record = parent::find($id);

        if (!$record) {
            return null;
        }

        $class = 'Mooc\\' . $record->type;

        // if it exists, it's a structural type of block like Chapter
        // or Section
        if (!class_exists($class, true)) {
            $class = 'Mooc\\Block';
        }

        $data = $record->toArray();
        $record = new $class();
        $record->setData($data, true);

        return $record;
    }
}
