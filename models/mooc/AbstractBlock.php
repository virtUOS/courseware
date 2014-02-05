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
        $this->default_values['json_data'] = '{}';

        $this->additional_fields['fields'] = array(
            'get' => function ($block, $field) {
                return studip_utf8decode(json_decode($block->json_data));
            },
            'set' => function ($block, $field, $value) {
                return $block->json_data = json_encode(studip_utf8encode($block->json_data));
            }
        );

        parent::__construct($id);
    }

    public static function findByParent_id($id)
    {
        return static::findBySQL('parent_id = ? ORDER BY position ASC', array($id));
    }

}
