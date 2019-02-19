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

namespace Hoa\Compiler\Llk;

use Hoa\Visitor;

/**
 * Class \Hoa\Compiler\Llk\TreeNode.
 *
 * Provide a generic node for the AST produced by LL(k) parser.
 */
class TreeNode implements Visitor\Element
{
    /**
     * ID (should be something like #ruleName or token).
     *
     * @var string
     */
    protected $_id       = null;

    /**
     * Value of the node (non-null for token nodes).
     *
     * @var array
     */
    protected $_value    = null;

    /**
     * Children.
     *
     * @var array
     */
    protected $_children = [];

    /**
     * Parent.
     *
     * @var \Hoa\Compiler\Llk\TreeNode
     */
    protected $_parent   = null;

    /**
     * Attached data.
     *
     * @var array
     */
    protected $_data     = [];



    /**
     * Constructor.
     *
     * @param   string                      $id          ID.
     * @param   array                       $value       Value.
     * @param   array                       $children    Children.
     * @param   \Hoa\Compiler\Llk\TreeNode  $parent    Parent.
     */
    public function __construct(
        string $id,
        array $value    = null,
        array $children = [],
        self  $parent   = null
    ) {
        $this->setId($id);

        if (!empty($value)) {
            $this->setValue($value);
        }

        $this->setChildren($children);

        if (null !== $parent) {
            $this->setParent($parent);
        }

        return;
    }

    /**
     * Set ID.
     */
    public function setId(string $id): ?string
    {
        $old       = $this->_id;
        $this->_id = $id;

        return $old;
    }

    /**
     * Get ID.
     */
    public function getId(): string
    {
        return $this->_id;
    }

    /**
     * Set value.
     */
    public function setValue(array $value): ?array
    {
        $old          = $this->_value;
        $this->_value = $value;

        return $old;
    }

    /**
     * Get value.
     */
    public function getValue(): ?array
    {
        return $this->_value;
    }

    /**
     * Get value token.
     */
    public function getValueToken(): ?string
    {
        return
            isset($this->_value['token'])
                ? $this->_value['token']
                : null;
    }

    /**
     * Get value value.
     */
    public function getValueValue(): ?string
    {
        return
            isset($this->_value['value'])
                ? $this->_value['value']
                : null;
    }

    /**
     * Get token offset.
     */
    public function getOffset(): int
    {
        return
            isset($this->_value['offset'])
                ? $this->_value['offset']
                : 0;
    }

    /**
     * Check if the node represents a token or not.
     */
    public function isToken(): bool
    {
        return !empty($this->_value);
    }

    /**
     * Prepend a child.
     */
    public function prependChild(self $child): self
    {
        array_unshift($this->_children, $child);

        return $this;
    }

    /**
     * Append a child.
     */
    public function appendChild(self $child): self
    {
        $this->_children[] = $child;

        return $this;
    }

    /**
     * Set children.
     */
    public function setChildren(array $children): array
    {
        $old             = $this->_children;
        $this->_children = $children;

        return $old;
    }

    /**
     * Get child.
     */
    public function getChild(int $i): ?TreeNode
    {
        return
            true === $this->childExists($i)
                ? $this->_children[$i]
                : null;
    }

    /**
     * Get children.
     */
    public function getChildren(): array
    {
        return $this->_children;
    }

    /**
     * Get number of children.
     */
    public function getChildrenNumber(): int
    {
        return count($this->_children);
    }

    /**
     * Check if a child exists.
     */
    public function childExists(int $i): bool
    {
        return array_key_exists($i, $this->_children);
    }

    /**
     * Set parent.
     */
    public function setParent(self $parent): ?self
    {
        $old           = $this->_parent;
        $this->_parent = $parent;

        return $old;
    }

    /**
     * Get parent.
     */
    public function getParent(): ?self
    {
        return $this->_parent;
    }

    /**
     * Get data.
     */
    public function &getData(): array
    {
        return $this->_data;
    }

    /**
     * Accept a visitor.
     */
    public function accept(
        Visitor\Visit $visitor,
        &$handle = null,
        $eldnah  = null
    ) {
        return $visitor->visit($this, $handle, $eldnah);
    }

    /**
     * Remove circular reference to the parent (help the garbage collector).
     */
    public function __destruct()
    {
        unset($this->_parent);

        return;
    }
}
