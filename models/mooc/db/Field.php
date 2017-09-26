<?php

namespace Mooc\DB;

/**
 * TODO.
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

    protected static function configure($config = array())
    {
        $config['db_table'] = 'mooc_fields';

        $config['belongs_to']['block'] = array(
            'class_name' => 'Mooc\\DB\\Block',
            'foreign_key' => 'block_id', );

        $config['belongs_to']['user'] = array(
            'class_name' => '\\User',
            'foreign_key' => 'user_id', );

        // TODO: this may not be named content
        $config['additional_fields']['content'] = array(
            'get' => function (Field $self) {
                if (isset($self->json_data)) {
                    return studip_utf8decode(json_decode($self->json_data, true));
                }

                return $self->getDefault();
            },
            'set' => function ($self, $field, $value) {
                if ($self->isNew() && is_null($value)) {
                    return null;
                }

                return $self->json_data = json_encode(studip_utf8encode($value));
            },
        );

        parent::configure($config);
    }

    /**
     * Give primary key of record as param to fetch
     * corresponding record from db if available, if not preset primary key
     * with given value. Give null to create new record.
     *
     * @param mixed $id primary key of table
     */
    public function __construct($id = null)
    {
        parent::__construct($id);

        $this->registerCallback('before_store', 'failForNobody');
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

    public function failForNobody()
    {
        if (is_null($this->json_data)) {
            return false;
        }

        if ($this->content['user_id'] == 'nobody') {
            throw new \RuntimeException('Cannot store user field for nobody:'.json_encode($this->content));
        }
    }
}
