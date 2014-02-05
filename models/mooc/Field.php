<?php
namespace Mooc;

/**
 * TODO
 *
 * @author  <mlunzena@uos.de>
 */
class Field extends \SimpleORMap
{

    private $default = null;

    /**
     * Give primary key of record as param to fetch
     * corresponding record from db if available, if not preset primary key
     * with given value. Give null to create new record
     *
     * @param mixed $id primary key of table
     */
    public function __construct($id = null) {
        $this->db_table = 'mooc_fields';

        $this->belongs_to['block'] = array(
            'class_name'  => 'Mooc\\AbstractBlock',
            'foreign_key' => 'block_id');

        $this->belongs_to['user'] = array(
            'class_name'  => 'User',
            'foreign_key' => 'user_id');


        $this->additional_fields['value'] = array(
            'get' => function ($block, $field) {
                if (isset($block->json_data)) {
                    return studip_utf8decode(json_decode($block->json_data));
                }
                return $block->getDefault();
            },
            'set' => function ($block, $field, $value) {
                return $block->json_data = json_encode(studip_utf8encode($value));
            }
        );

        parent::__construct($id);
    }

    // TODO
    public function getDefault()
    {
        return $this->default;
    }

    // TODO
    public function setDefault($default)
    {
        $this->default = $default;
    }
}
