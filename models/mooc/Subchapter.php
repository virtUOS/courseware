<?php

namespace Mooc;

/**
 * Item on the 2nd level navigation of a MOOC course.
 *
 * @author Christian Flothmann <cflothma@uos.de>
 */
class Subchapter extends AbstractBlock
{
    public function __construct($id = null)
    {
        $this->has_many['sections'] = array(
            'class_name' => 'Mooc\Section',
            'assoc_foreign_key' => 'parent_id',
            'assoc_func' => 'findByParent_id',
        );

        parent::__construct($id);
    }
}
