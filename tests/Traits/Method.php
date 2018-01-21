<?php

declare(strict_types=1);

/*
 * This file is part of the Mall Digital Ecosystem (MDE) project.
 *
 * (c) <SCCD> <office@sccd.lu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scc\Tests\Gaufrette\Cloudinary\Traits;

trait Method
{
    /**
     * Property caller.
     *
     * @param $object
     * @param string $name
     * @param $value
     *
     * @throws \ReflectionException
     *
     * @return \ReflectionProperty
     */
    protected static function getMethod($object, string $name, $value = [])
    {
        $class = new \ReflectionClass($object);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $value);

//        return $method->invoke($object);
    }

    /**
     * @param $object
     * @param string $name
     * @param null   $value
     *
     * @throws \ReflectionException
     *
     * @return mixed
     */
    protected static function setMethod($object, string $name, $value = null)
    {
        $class = new \ReflectionClass($object);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        $method->invokeArgs($object, [$value]);

        return $object;
    }
}
