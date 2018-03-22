<?php

namespace Mooc\Import;

use Mooc\UI\Courseware\Courseware;

/**
 * Interface definition for Mooc.IP Courseware import drivers.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
interface ImportInterface
{
    /**
     * Import formatted courseware data into a course.
     *
     * @param string     $path    Path to where the files to be imported have
     *                            been extracted
     * @param Courseware $context The courseware context in which to import
     *                            the given data
     */
    public function import($path, Courseware $context, $folder);
}
