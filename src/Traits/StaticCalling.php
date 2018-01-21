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

namespace Scc\Gaufrette\Cloudinary\Traits;

trait StaticCalling
{
    /**
     * Performs a static method call.
     *
     * @param string $className  Name of the class
     * @param string $methodName Name of the method
     *
     * @return mixed
     */
    protected function callStatic(string $className, string $methodName)
    {
        $parameters = \func_get_args();
        $parameters = \array_slice($parameters, 2); // Remove $className and $methodName

        return \call_user_func_array($className . '::' . $methodName, $parameters);
    }
}
