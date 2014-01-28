<?php
namespace Mooc;

/**
 * @author  <mlunzena@uos.de>
 */
class Section extends AbstractBlock
{
    public function __construct($id = null) {

        $this->belongs_to['chapter'] = array(
            'class_name' => 'Mooc\\Chapter',
            'foreign_key' => 'parent_id');

        $this->has_many['blocks'] = array(
            'class_name' => 'Mooc\\Block',
            'assoc_foreign_key' => 'parent_id',
            'assoc_func' => 'findByParent_id');

        parent::__construct($id);
    }
}
