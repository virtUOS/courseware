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
     * returns array of instances of given class filtered by given sql
     * @param string sql clause to use on the right side of WHERE
     * @param array parameters for query
     * @return array array of "self" objects
     */
    public static function findBySQL($where, $params = array())
    {
        $class = get_called_class();
        $record = new $class();

        $db = \DBManager::get();
        $sql = "SELECT * FROM `" .  $record->db_table . "` WHERE " . $where;
        $st = $db->prepare($sql);
        $st->execute($params);

        $ret = array();
        while($row = $st->fetch(\PDO::FETCH_ASSOC)) {
            $class = self::typeToClass($row['type']);

            $ret[] = $obj = new $class();
            $obj->setData($row, true);
            $obj->setNew(false);
        }
        return $ret;
    }

    private static function typeToClass($type)
    {
        $class = 'Mooc\\' . $type;

        // if it exists, it's a structural type of block like Chapter
        // or Section
        if (!class_exists($class, true)) {
            $class = 'Mooc\\Block';
        }

        return $class;
    }
}
