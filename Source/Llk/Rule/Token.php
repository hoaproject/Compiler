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

use Hoa\Compiler;
use Hoa\File;

/**
 * Class \Hoa\Compiler\Llk\Rule\Token.
 *
 * The token rule.
 */
class Token extends Rule
{
    /**
     * LL(k) compiler of hoa://Library/Regex/Grammar.pp.
     *
     * @var ?Compiler\Llk\Parser
     */
    protected static $_regexCompiler = null;

    /**
     * Token name.
     *
     * @var string
     */
    protected $_tokenName            = null;

    /**
     * Namespace.
     *
     * @var ?string
     */
    protected $_namespace            = null;

    /**
     * Token representation.
     *
     * @var string
     */
    protected $_regex                = null;

    /**
     * AST of the regex.
     *
     * @var ?Compiler\Llk\TreeNode
     */
    protected $_ast                  = null;

    /**
     * Token value.
     *
     * @var ?string
     */
    protected $_value                = null;

    /**
     * Whether the token is kept or not in the AST.
     *
     * @var bool
     */
    protected $_kept                 = false;

    /**
     * Unification index.
     *
     * @var int
     */
    protected $_unification          = -1;

    /**
     * Token offset.
     *
     * @var int
     */
    protected $_offset               = 0;

    /**
     * Constructor.
     */
    public function __construct(
        $name,
        string $tokenName,
        ?string $nodeId,
        int $unification,
        bool $kept = false
    ) {
        parent::__construct($name, null, $nodeId);

        $this->_tokenName   = $tokenName;
        $this->_unification = $unification;
        $this->setKept($kept);

        return;
    }

    /**
     * Get token name.
     */
    public function getTokenName(): string
    {
        return $this->_tokenName;
    }

    /**
     * Set token namespace.
     */
    public function setNamespace(string $namespace): ?string
    {
        $old              = $this->_namespace;
        $this->_namespace = $namespace;

        return $old;
    }

    /**
     * Get token namespace.
     */
    public function getNamespace(): ?string
    {
        return $this->_namespace;
    }

    /**
     * Set representation.
     */
    public function setRepresentation(string $regex): ?string
    {
        $old          = $this->_regex;
        $this->_regex = $regex;

        return $old;
    }

    /**
     * Get token representation.
     */
    public function getRepresentation(): ?string
    {
        return $this->_regex;
    }

    /**
     * Get AST of the token representation.
     */
    public function getAST(): Compiler\Llk\TreeNode
    {
        if (null === static::$_regexCompiler) {
            $stream = new File\Read('hoa://Library/Regex/Grammar.pp');
            $stream->rewind();

            static::$_regexCompiler = Compiler\Llk::load($stream);
        }

        if (null === $this->_ast) {
            $this->_ast = static::$_regexCompiler->parse(
                $this->getRepresentation()
            );
        }

        return $this->_ast;
    }

    /**
     * Set token value.
     */
    public function setValue(string $value): ?string
    {
        $old          = $this->_value;
        $this->_value = $value;

        return $old;
    }

    /**
     * Get token value.
     */
    public function getValue(): ?string
    {
        return $this->_value;
    }

    /**
     * Set token offset.
     */
    public function setOffset(int $offset): int
    {
        $old           = $this->_offset;
        $this->_offset = $offset;

        return $old;
    }

    /**
     * Get token offset.
     */
    public function getOffset(): int
    {
        return $this->_offset;
    }

    /**
     * Set whether the token is kept or not in the AST.
     */
    public function setKept(bool $kept): bool
    {
        $old         = $this->_kept;
        $this->_kept = $kept;

        return $old;
    }

    /**
     * Check whether the token is kept in the AST or not.
     */
    public function isKept(): bool
    {
        return $this->_kept;
    }

    /**
     * Get unification index.
     */
    public function getUnificationIndex(): int
    {
        return $this->_unification;
    }
}
