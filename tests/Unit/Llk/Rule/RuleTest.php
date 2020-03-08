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

namespace Tests\Hoa\Compiler\Unit\Llk\Rule;

use Hoa\Compiler\Llk\Rule as SUT;
use Tests\Hoa\Compiler\TestCase;

/**
 * Test suite of a rule.
 *
 * @covers \Hoa\Compiler\Llk\Rule
 */
class RuleTest extends TestCase
{
    private function makeRule(...$args): SUT
    {
        return new class(...$args) extends SUT {
            public function __call($method_name, $args)
            {
                if (method_exists($this, $method_name)) {
                    return $this->$method_name(...$args);
                }
            }
        };
    }


    /**
     * @test
     */
    public function case_constructor()
    {
        $this
            ->given(
                $name     = 'foo',
                $children = ['bar']
            )
            ->when($result = $this->makeRule($name, $children))
            ->then
                ->string($result->getName())
                    ->isEqualTo($name)
                ->array($result->getChildren())
                    ->isEqualTo($children)
                ->variable($result->getNodeId())
                    ->isNull()
                ->boolean($result->isTransitional())
                    ->isTrue();
    }

    /**
     * @test
     */
    public function case_constructor_with_node_id()
    {
        $this
            ->given(
                $name     = 'foo',
                $children = ['bar'],
                $nodeId   = 'baz'
            )
            ->when($result = $this->makeRule($name, $children, $nodeId))
            ->then
                ->string($result->getName())
                    ->isEqualTo($name)
                ->array($result->getChildren())
                    ->isEqualTo($children)
                ->string($result->getNodeId())
                    ->isEqualTo($nodeId)
                ->boolean($result->isTransitional())
                    ->isTrue();
    }

    /**
     * @test
     */
    public function case_set_name()
    {
        $this
            ->given(
                $name     = 'foo',
                $children = ['bar'],
                $rule     = $this->makeRule($name, $children)
            )
            ->when($result = $rule->setName('baz'))
            ->then
                ->string($result)
                    ->isEqualTo($name);
    }

    /**
     * @test
     */
    public function case_get_name()
    {
        $this
            ->given(
                $name     = 'baz',
                $children = ['bar'],
                $rule     = $this->makeRule('foo', $children),
                $rule->setName($name)
            )
            ->when($result = $rule->getName())
            ->then
                ->string($result)
                    ->isEqualTo($name);
    }

    /**
     * @test
     */
    public function case_set_children()
    {
        $this
            ->given(
                $name     = 'foo',
                $children = ['bar'],
                $rule     = $this->makeRule($name, $children)
            )
            ->when($result = $this->invoke($rule)->setChildren(['baz']))
            ->then
                ->array($result)
                    ->isEqualTo($children);
    }

    /**
     * @test
     */
    public function case_get_children()
    {
        $this
            ->given(
                $name     = 'foo',
                $children = ['baz'],
                $rule     = $this->makeRule($name, ['bar']),
                $this->invoke($rule)->setChildren($children)
            )
            ->when($result = $rule->getChildren())
            ->then
                ->array($result)
                    ->isEqualTo($children);
    }

    /**
     * @test
     */
    public function case_set_node_id()
    {
        $this
            ->given(
                $name        = 'foo',
                $children    = ['bar'],
                $nodeId      = 'id',
                $rule        = $this->makeRule($name, $children, $nodeId)
            )
            ->when($result = $rule->setNodeId('baz:qux'))
            ->then
                ->string($result)
                    ->isEqualTo($nodeId);
    }

    /**
     * @test
     */
    public function case_get_node_id()
    {
        $this
            ->given(
                $name     = 'foo',
                $children = ['bar'],
                $rule     = $this->makeRule($name, $children),
                $rule->setNodeId('baz')
            )
            ->when($result = $rule->getNodeId())
            ->then
                ->string($result)
                    ->isEqualTo('baz');
    }

    /**
     * @test
     */
    public function case_get_node_id_with_options()
    {
        $this
            ->given(
                $name     = 'foo',
                $children = ['bar'],
                $rule     = $this->makeRule($name, $children),
                $rule->setNodeId('baz:qux')
            )
            ->when($result = $rule->getNodeId())
            ->then
                ->string($result)
                    ->isEqualTo('baz');
    }

    /**
     * @test
     */
    public function case_get_node_options_empty()
    {
        $this
            ->given(
                $name     = 'foo',
                $children = ['bar'],
                $rule     = $this->makeRule($name, $children),
                $rule->setNodeId('baz')
            )
            ->when($result = $rule->getNodeOptions())
            ->then
                ->array($result)
                    ->isEmpty();
    }

    /**
     * @test
     */
    public function case_get_node_options()
    {
        $this
            ->given(
                $name     = 'foo',
                $children = ['bar'],
                $rule     = $this->makeRule($name, $children),
                $rule->setNodeId('baz:qux')
            )
            ->when($result = $rule->getNodeOptions())
            ->then
                ->array($result)
                    ->isEqualTo(['q', 'u', 'x']);
    }

    /**
     * @test
     */
    public function case_set_default_id()
    {
        $this
            ->given(
                $name        = 'foo',
                $children    = ['bar'],
                $nodeId      = 'id',
                $rule        = $this->makeRule($name, $children, $nodeId)
            )
            ->when($result = $rule->setDefaultId('baz:qux'))
            ->then
                ->variable($result)
                    ->isNull();
    }

    /**
     * @test
     */
    public function case_get_default_id()
    {
        $this
            ->given(
                $name     = 'foo',
                $children = ['bar'],
                $rule     = $this->makeRule($name, $children),
                $rule->setDefaultId('baz')
            )
            ->when($result = $rule->getDefaultId())
            ->then
                ->string($result)
                    ->isEqualTo('baz');
    }

    /**
     * @test
     */
    public function case_get_default_id_with_options()
    {
        $this
            ->given(
                $name     = 'foo',
                $children = ['bar'],
                $rule     = $this->makeRule($name, $children),
                $rule->setDefaultId('baz:qux')
            )
            ->when($result = $rule->getDefaultId())
            ->then
                ->string($result)
                    ->isEqualTo('baz');
    }

    /**
     * @test
     */
    public function case_get_default_options_empty()
    {
        $this
            ->given(
                $name     = 'foo',
                $children = ['bar'],
                $rule     = $this->makeRule($name, $children),
                $rule->setDefaultId('baz')
            )
            ->when($result = $rule->getDefaultOptions())
            ->then
                ->array($result)
                    ->isEmpty();
    }

    /**
     * @test
     */
    public function case_get_default_options()
    {
        $this
            ->given(
                $name     = 'foo',
                $children = ['bar'],
                $rule     = $this->makeRule($name, $children),
                $rule->setDefaultId('baz:qux')
            )
            ->when($result = $rule->getDefaultOptions())
            ->then
                ->array($result)
                    ->isEqualTo(['q', 'u', 'x']);
    }

    /**
     * @test
     */
    public function case_set_pp_representation()
    {
        $this
            ->given(
                $name              = 'foo',
                $children          = ['bar'],
                $pp                = '<a> ::b:: c()?',
                $rule              = $this->makeRule($name, $children),
                $oldIsTransitional = $rule->isTransitional()
            )
            ->when($result = $rule->setPPRepresentation($pp))
            ->then
                ->variable($result)
                    ->isNull()
                ->boolean($oldIsTransitional)
                    ->isTrue()
                ->boolean($rule->isTransitional())
                    ->isFalse();
    }

    /**
     * @test
     */
    public function case_get_pp_representation()
    {
        $this
            ->given(
                $name     = 'foo',
                $children = ['bar'],
                $pp       = '<a> ::b:: c()?',
                $rule     = $this->makeRule($name, $children),
                $rule->setPPRepresentation($pp)
            )
            ->when($result = $rule->getPPRepresentation())
            ->then
                ->string($result)
                    ->isEqualTo($pp);
    }

    /**
     * @test
     */
    public function case_is_transitional()
    {
        $this
            ->given(
                $name              = 'foo',
                $children          = ['bar'],
                $pp                = '<a> ::b:: c()?',
                $rule              = $this->makeRule($name, $children),
                $oldIsTransitional = $rule->isTransitional(),
                $rule->setPPRepresentation($pp)
            )
            ->when($result = $rule->isTransitional())
            ->then
                ->boolean($oldIsTransitional)
                    ->isTrue()
                ->boolean($result)
                    ->isFalse();
    }
}
