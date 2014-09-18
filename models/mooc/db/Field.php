<?php
namespace Mooc\DB;

/**
 * TODO
 *
 * @author  <mlunzena@uos.de>
 *
 * @property int    $block_id
 * @property Block  $block
 * @property string $user_id
 * @property \User  $user
 * @property string $name
 * @property string $json_data
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
            'class_name'  => 'Mooc\\DB\\Block',
            'foreign_key' => 'block_id');

        $this->belongs_to['user'] = array(
            'class_name'  => 'User',
            'foreign_key' => 'user_id');


        // TODO: this may not be named content
        $this->additional_fields['content'] = array(
            'get' => function (Field $self) {
                if (isset($self->json_data)) {
                    return studip_utf8decode(json_decode($self->json_data, true));
                }
                return $self->getDefault();
            },
            'set' => function ($self, $field, $value) {
                return $self->json_data = json_encode(studip_utf8encode($value));
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
