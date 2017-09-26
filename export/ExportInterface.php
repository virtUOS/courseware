<?php

namespace Mooc\Export;

use Mooc\UI\Courseware\Courseware;

/**
 * Interface definition for Mooc.IP Courseware export drivers.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
interface ExportInterface
{
    /**
     * Exports the given Courseware into a driver specific format.
     *
     * @param Courseware $courseware
     *
     * @return mixed The exported Courseware, the actual format depends on the
     *               concrete driver implementation
     */
    public function export(Courseware $courseware);
}
