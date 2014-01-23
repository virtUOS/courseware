<?php
namespace Mooc;

/**
 * @author  <mlunzena@uos.de>
 */
class Block extends AbstractBlock
{

    /**
     * Give primary key of record as param to fetch
     * corresponding record from db if available, if not preset primary key
     * with given value. Give null to create new record
     *
     * @param mixed $id primary key of table
     */
    public function __construct($id = null) {

        $this->belongs_to['section'] = array(
            'class_name' => 'Mooc\\Section',
            'foreign_key' => 'parent_id');

        parent::__construct($id);
    }
}
