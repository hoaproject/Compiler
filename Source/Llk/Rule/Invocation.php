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

namespace Hoa\Compiler\Llk\Rule;

/**
 * Class \Hoa\Compiler\Llk\Rule\Invocation.
 *
 * Parent of entry and ekzit rules.
 */
abstract class Invocation
{
    /**
     * Rule.
     *
     * @var mixed
     */
    protected $_rule         = null;

    /**
     * Data.
     *
     * @var mixed
     */
    protected $_data         = null;

    /**
     * Piece of todo sequence.
     *
     * @var array
     */
    protected $_todo         = [];

    /**
     * Depth in the trace.
     *
     * @var int
     */
    protected $_depth        = -1;

    /**
     * Whether the rule is transitional or not (i.e. not declared in the grammar
     * but created by the analyzer).
     *
     * @var bool
     */
    protected $_transitional = false;



    /**
     * Constructor.
     */
    public function __construct(
        $rule,
        $data,
        ?array $todo = null,
        int $depth  = -1
    ) {
        $this->_rule         = $rule;
        $this->_data         = $data;
        $this->_todo         = $todo ?? [];
        $this->_depth        = $depth;
        $this->_transitional = is_int($rule);

        return;
    }

    /**
     * Get rule name.
     */
    public function getRule()
    {
        return $this->_rule;
    }

    /**
     * Get data.
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Get todo sequence.
     */
    public function getTodo(): array
    {
        return $this->_todo;
    }

    /**
     * Set depth in trace.
     */
    public function setDepth(int $depth): int
    {
        $old          = $this->_depth;
        $this->_depth = $depth;

        return $old;
    }

    /**
     * Get depth in trace.
     */
    public function getDepth(): int
    {
        return $this->_depth;
    }

    /**
     * Check whether the rule is transitional or not.
     */
    public function isTransitional(): bool
    {
        return $this->_transitional;
    }
}
