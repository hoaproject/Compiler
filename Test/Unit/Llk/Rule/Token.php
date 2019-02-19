<?php

declare(strict_types=1);

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2017, Hoa community. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace Hoa\Compiler\Test\Unit\Llk\Rule;

use Hoa\Compiler as LUT;
use Hoa\Compiler\Llk\Rule\Token as SUT;
use Hoa\Test;

/**
 * Class \Hoa\Compiler\Test\Unit\Llk\Rule\Token.
 *
 * Test suite of a token.
 */
class Token extends Test\Unit\Suite
{
    public function case_is_a_rule()
    {
        $this
            ->when($result = new SUT('name', 'tokenName', 'nodeId', 0))
            ->then
                ->object($result)
                    ->isInstanceOf(LUT\Llk\Rule::class);
    }

    public function case_constructor()
    {
        $this
            ->given(
                $name        = 'foo',
                $tokenName   = 'bar',
                $nodeId      = 'baz',
                $unification = 0
            )
            ->when($result = new SUT($name, $tokenName, $nodeId, $unification))
            ->then
                ->string($result->getName())
                    ->isEqualTo($name)
                ->string($result->getTokenName())
                    ->isEqualTo($tokenName)
                ->string($result->getNodeId())
                    ->isEqualTo($nodeId)
                ->integer($result->getUnificationIndex())
                    ->isEqualTo($unification)
                ->boolean($result->isKept())
                    ->isFalse();
    }

    public function case_constructor_with_kept_flag()
    {
        $this
            ->given(
                $name        = 'foo',
                $tokenName   = 'bar',
                $nodeId      = 'baz',
                $unification = 0,
                $kept        = true
            )
            ->when($result = new SUT($name, $tokenName, $nodeId, $unification, $kept))
            ->then
                ->string($result->getName())
                    ->isEqualTo($name)
                ->string($result->getTokenName())
                    ->isEqualTo($tokenName)
                ->string($result->getNodeId())
                    ->isEqualTo($nodeId)
                ->integer($result->getUnificationIndex())
                    ->isEqualTo($unification)
                ->boolean($result->isKept())
                    ->isTrue();
    }

    public function case_get_token_name()
    {
        $this
            ->given(
                $name        = 'foo',
                $tokenName   = 'bar',
                $nodeId      = 'baz',
                $unification = 0,
                $token       = new SUT($name, $tokenName, $nodeId, $unification)
            )
            ->when($result = $token->getTokenName())
            ->then
                ->string($result)
                    ->isEqualTo($tokenName);
    }

    public function case_set_namespace()
    {
        $this
            ->given(
                $name        = 'foo',
                $tokenName   = 'bar',
                $nodeId      = 'baz',
                $unification = 0,
                $namespace   = 'qux',
                $token       = new SUT($name, $tokenName, $nodeId, $unification)
            )
            ->when($result = $token->setNamespace($namespace))
            ->then
                ->variable($result)
                    ->isNull();
    }

    public function case_get_namespace()
    {
        $this
            ->given(
                $name        = 'foo',
                $tokenName   = 'bar',
                $nodeId      = 'baz',
                $unification = 0,
                $namespace   = 'qux',
                $token       = new SUT($name, $tokenName, $nodeId, $unification),
                $token->setNamespace($namespace)
            )
            ->when($result = $token->getNamespace())
            ->then
                ->string($result)
                    ->isEqualTo($namespace);
    }

    public function case_set_representation()
    {
        $this
            ->given(
                $name           = 'foo',
                $tokenName      = 'bar',
                $nodeId         = 'baz',
                $unification    = 0,
                $representation = 'qux',
                $token          = new SUT($name, $tokenName, $nodeId, $unification)
            )
            ->when($result = $token->setRepresentation($representation))
            ->then
                ->variable($result)
                    ->isNull();
    }

    public function case_get_representation()
    {
        $this
            ->given(
                $name           = 'foo',
                $tokenName      = 'bar',
                $nodeId         = 'baz',
                $unification    = 0,
                $representation = 'qux',
                $token          = new SUT($name, $tokenName, $nodeId, $unification),
                $token->setRepresentation($representation)
            )
            ->when($result = $token->getRepresentation())
            ->then
                ->string($result)
                    ->isEqualTo($representation);
    }

    public function case_get_ast()
    {
        $this
            ->given(
                $name           = 'foo',
                $tokenName      = 'bar',
                $nodeId         = 'baz',
                $unification    = 0,
                $representation = 'qux',
                $token          = new SUT($name, $tokenName, $nodeId, $unification),
                $token->setRepresentation($representation)
            )
            ->when($result = $token->getAST())
            ->then
                ->object($result)
                    ->isInstanceOf(LUT\Llk\TreeNode::class)
                ->let($dumper = new LUT\Visitor\Dump())
                ->string($dumper->visit($result))
                    ->isEqualTo(
                        '>  #expression' . "\n" .
                        '>  >  #concatenation' . "\n" .
                        '>  >  >  token(literal, q)' . "\n" .
                        '>  >  >  token(literal, u)' . "\n" .
                        '>  >  >  token(literal, x)' . "\n"
                    );
    }

    public function case_set_value()
    {
        $this
            ->given(
                $name        = 'foo',
                $tokenName   = 'bar',
                $nodeId      = 'baz',
                $unification = 0,
                $value       = 'qux',
                $token       = new SUT($name, $tokenName, $nodeId, $unification)
            )
            ->when($result = $token->setValue($value))
            ->then
                ->variable($result)
                    ->isNull();
    }

    public function case_get_value()
    {
        $this
            ->given(
                $name        = 'foo',
                $tokenName   = 'bar',
                $nodeId      = 'baz',
                $unification = 0,
                $value       = 'qux',
                $token       = new SUT($name, $tokenName, $nodeId, $unification),
                $token->setValue($value)
            )
            ->when($result = $token->getValue())
            ->then
                ->string($result)
                    ->isEqualTo($value);
    }

    public function case_set_offset()
    {
        $this
            ->given(
                $name        = 'foo',
                $tokenName   = 'bar',
                $nodeId      = 'baz',
                $unification = 0,
                $offset      = 42,
                $token       = new SUT($name, $tokenName, $nodeId, $unification)
            )
            ->when($result = $token->setOffset($offset))
            ->then
                ->integer($result)
                    ->isZero();
    }

    public function case_get_offset()
    {
        $this
            ->given(
                $name        = 'foo',
                $tokenName   = 'bar',
                $nodeId      = 'baz',
                $unification = 0,
                $offset      = 42,
                $token       = new SUT($name, $tokenName, $nodeId, $unification),
                $token->setOffset($offset)
            )
            ->when($result = $token->getOffset())
            ->then
                ->integer($result)
                    ->isEqualTo($offset);
    }

    public function case_set_kept()
    {
        $this
            ->given(
                $name        = 'foo',
                $tokenName   = 'bar',
                $nodeId      = 'baz',
                $unification = 0,
                $kept        = true,
                $token       = new SUT($name, $tokenName, $nodeId, $unification)
            )
            ->when($result = $token->setKept($kept))
            ->then
                ->boolean($result)
                    ->isFalse();
    }

    public function case_is_kept()
    {
        $this
            ->given(
                $name        = 'foo',
                $tokenName   = 'bar',
                $nodeId      = 'baz',
                $unification = 0,
                $kept        = true,
                $token       = new SUT($name, $tokenName, $nodeId, $unification),
                $token->setKept($kept)
            )
            ->when($result = $token->isKept())
            ->then
                ->boolean($result)
                    ->isTrue();
    }

    public function case_get_unification_index()
    {
        $this
            ->given(
                $name        = 'foo',
                $tokenName   = 'bar',
                $nodeId      = 'baz',
                $unification = 42,
                $token       = new SUT($name, $tokenName, $nodeId, $unification)
            )
            ->when($result = $token->getUnificationIndex())
            ->then
                ->integer($result)
                    ->isEqualTo($unification);
    }
}
