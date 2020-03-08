<?php
/**
 * Hoa
 *
 *
 * @license
 *
 * BSD 3-Clause License
 *
 * Copyright © 2007-2017, Hoa community. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this
 *    list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its
 *    contributors may be used to endorse or promote products derived from
 *    this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

declare(strict_types=1);

namespace Tests\Hoa\Compiler;

use Tests\Hoa\Compiler\Common\AtoumLikeTester;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    final protected function given(): AtoumLikeTester
    {
        return new AtoumLikeTester($this);
    }

    final protected function when($argument = null): AtoumLikeTester
    {
        if ($argument instanceof \Closure) {
            call_user_func($argument);
        }

        return new AtoumLikeTester($this);
    }

    /**
     * Returns a call proxying wrapper to let the caller invoke protected methods. We don't do that yet.
     *
     * @param object $object
     * @return object
     */
    final protected function invoke($object)
    {
        return $object;
    }

    /**
     * PHPUnit 6.5 / PHP 7.0 adapter.
     *
     * @param string $method
     * @param array $args
     */
    final public function __call($method, $args)
    {
        if (strpos($method, 'assertIs') === 0) {
            $this->assertInternalType(strtolower(substr($method, strlen('assertIs'))), ...$args);

            return;
        }

        $this->markTestIncomplete(sprintf("%s::%s() is not available in this version of PHPUnit", static::class, $method));
    }
}
