<?php

namespace Mooc\Export\Validator;

/**
 * Validate import formats.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
interface ValidatorInterface
{
    /**
     * Checks if a string matches a particular import format.
     *
     * @param string $data The data to validate
     *
     * @return bool True if the data is of the proper format
     */
    public function validate($data);
}
