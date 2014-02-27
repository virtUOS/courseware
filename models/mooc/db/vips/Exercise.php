<?php

namespace Mooc\DB\Vips;

/**
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class Exercise extends \SimpleORMap
{
    public function __construct($id = null)
    {
        $this->db_table = 'vips_aufgabe';

        parent::__construct($id);
    }
}
 