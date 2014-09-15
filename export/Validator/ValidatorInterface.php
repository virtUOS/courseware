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
     * @return string[] An array of validation error messages
     */
    public function validate($data);
}
