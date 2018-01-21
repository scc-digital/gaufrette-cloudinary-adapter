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

trait Property
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
    protected static function setProperty($object, string $name, $value)
    {
        $class = new \ReflectionClass($object);
        $property = $class->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($object, $value);

        return $property;
    }

    /**
     * Property getter.
     *
     * @param mixed  $object
     * @param string $name
     *
     * @throws \ReflectionException
     *
     * @return mixed
     */
    protected static function getProperty($object, string $name)
    {
        $class = new \ReflectionClass($object);
        $property = $class->getProperty($name);
        $property->setAccessible(true);

        return $property->getValue($object);
    }
}
