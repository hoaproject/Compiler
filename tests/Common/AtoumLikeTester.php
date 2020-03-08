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

namespace Tests\Hoa\Compiler\Common;

use Tests\Hoa\Compiler\TestCase;

final class AtoumLikeTester
{
    /** @var AtoumLikeTester */
    public $then;

    /**
     * @var TestCase
     */
    private $testCase;

    private $context;

    public function __construct(TestCase $testCase)
    {
        $this->testCase = $testCase;
        $this->then     = $this;
    }

    public function let(): self
    {
        return $this;
    }

    public function given(): self
    {
        return $this;
    }

    public function when(): self
    {
        return $this;
    }

    public function variable($result): self
    {
        $this->context = $result;

        return $this;
    }

    public function string($result): self
    {
        $this->context = $result;

        $this->testCase->assertIsString($result);

        return $this;
    }

    public function integer($result): self
    {
        $this->context = $result;

        $this->testCase->assertIsInt($result);

        return $this;
    }

    public function boolean($result): self
    {
        $this->context = $result;

        $this->testCase->assertIsBool($result);

        return $this;
    }

    public function array($result): self
    {
        $this->context = $result;

        $this->testCase->assertIsArray($result);

        return $this;
    }

    public function object($result): self
    {
        $this->context = $result;

        $this->testCase->assertIsObject($result);

        return $this;
    }

    public function exception(callable $callback): self
    {
        try {
            $callback();

            $this->fail("No exception was thrown.");
        } catch (\Exception $e) {
            $this->context = $e;
        }

        return $this;
    }

    public function isEqualTo($value): self
    {
        $this->testCase->assertSame($value, $this->context);

        return $this;
    }

    public function isIdenticalTo($value): self
    {
        $this->testCase->assertSame($value, $this->context);

        return $this;
    }

    /**
     * @deprecated shall not be used in tests unless unavoidable
     */
    public function isRoughlyEqualTo($value): self
    {
        $this->testCase->assertEquals($value, $this->context);

        return $this;
    }


    public function isFalse(): self
    {
        $this->testCase->assertFalse($this->context);

        return $this;
    }

    public function isTrue(): self
    {
        $this->testCase->assertTrue($this->context);

        return $this;
    }

    public function isNull(): self
    {
        $this->testCase->assertNull($this->context);

        return $this;
    }

    public function isZero(): self
    {
        $this->testCase->assertEquals(0, $this->context);

        return $this;
    }

    public function isEmpty(): self
    {
        $this->testCase->assertEmpty($this->context);

        return $this;
    }

    public function isInstanceOf($expected): self
    {
        $this->testCase->assertInstanceOf($expected, $this->context);

        return $this;
    }

    public function hasMessage($expected): self
    {
        assert($this->context instanceof \Exception);
        $this->testCase->assertSame($expected, $this->context->getMessage());

        return $this;
    }

    public function executeOnFailure(callable $callback): self
    {
        return $this;
    }

    public function __call($method_name, $args)
    {
        if (method_exists($this->testCase, $method_name)) {
            return call_user_func_array([$this->testCase, $method_name], $args);
        }

        throw new \Error(sprintf("Call to undefined method %s::%s()", static::class, $method_name));
    }
}
