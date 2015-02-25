<?php
/*
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      <mlunzena@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */
class Metrics {

    public static function count($stat, $increment, $sampleRate = 1) {}
    public static function increment($stat, $sampleRate = 1) {}
    public static function decrement($stat, $sampleRate = 1) {}
    public static function gauge($stat, $value, $sampleRate = 1) {}
    public static function timing($stat, $milliseconds, $sampleRate = 1) {}
    public static function startTimer()
    {
        return function ($stat, $sampleRate = 1) {};
    }
}
