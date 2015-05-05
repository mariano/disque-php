<?php
namespace Disque\Command\Argument;

trait ArrayChecker
{
    /**
     * Check that the exact specified $count arguments are defined,
     * in a numeric array
     *
     * @param mixed $elements Elements (should be an array)
     * @param int $count Number of elements expected
     * @param bool $atLeast Se to true to check array has at least $count elements
     * @return bool Success
     */
    protected function checkFixedArray($elements, $count, $atLeast = false)
    {
        if (
            empty($elements) ||
            !is_array($elements) ||
            (!$atLeast && count($elements) !== $count) ||
            ($atLeast && count($elements) < $count)
        ) {
            return false;
        }

        for ($i=0; $i < $count; $i++) {
            if (!isset($elements[$i])) {
                return false;
            }
        }

        return true;
    }
}