<?php
namespace Mooc;

/**
 * @author  <mlunzena@uos.de>
 */
class Courseware extends AbstractBlock
{
    public function __construct($id = null) {

        $this->has_many['chapters'] = array(
            'class_name' => 'Mooc\\Chapter',
            'assoc_foreign_key' => 'parent_id',
            'assoc_func' => 'findByParent_id');

        parent::__construct($id);
    }

    public static function findByCourse($cid)
    {
        return current(static::findBySQL('seminar_id = ? AND parent_id IS NULL LIMIT 1', array($cid)));
    }

}
