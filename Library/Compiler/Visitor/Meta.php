<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2011, Ivan Enderlin. All rights reserved.
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

namespace {

from('Hoa')

/**
 * \Hoa\Visitor\Visit
 */
-> import('Visitor.Visit')

/**
 * \Hoa\Compiler\Llk
 */
-> import('Compiler.Llk')

/**
 * \Hoa\File\Read
 */
-> import('File.Read');

}

namespace Hoa\Compiler\Visitor {

/**
 * Class \Hoa\Compiler\Visitor\Meta.
 *
 * Visitor that exposes the LL(k) compiler compiler as a meta compiler compiler.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2011 Ivan Enderlin.
 * @license    New BSD License
 */

class Meta implements \Hoa\Visitor\Visit {

    /**
     * AST producer.
     * $meta = \Hoa\Compiler\Llk::load(…);
     * $ast->accept(new \Hoa\Compiler\Visitor\Meta($meta, …), …);
     *
     * @var \Hoa\Compiler\Llk object
     */
    protected $_self                = null;

    /**
     * Tokens.
     *
     * @var \Hoa\Compiler\Visitor\Meta array
     */
    protected $_tokens              = array();

    /**
     * Parsed tokens (AST cache).
     *
     * @var \Hoa\Compiler\Visitor\Meta array
     */
    protected $_parsedTokens        = array();

    /**
     * Rules.
     *
     * @var \Hoa\Compiler\Visitor\Meta array
     */
    protected $_rules               = array();

    /**
     * Compiler current context.
     *
     * @var \Hoa\Compiler\Visitor\Meta string
     */
    protected $_context             = 'default';

    /**
     * Compiler compiler of hoa://Library/Regex/Grammar.pp.
     *
     * @var \Hoa\Compiler\Llk object
     */
    protected static $_regex        = null;

    /**
     * Regex visitor.
     *
     * @var \Hoa\Regex\Visitor\Realdom object
     */
    protected static $_regexVisitor = null;

    /**
     * Unification.
     *
     * @var \Hoa\Compiler\Visitor\Meta array
     */
    protected $_unification         = array();

    /**
     * Current unification level.
     *
     * @var \Hoa\Compiler\Visitor\Meta int
     */
    protected $_u                   = 0;



    /**
     * Build a visitor that exposes the LL(k) compiler compiler as a meta
     * compiler compiler.
     *
     * @access  public
     * @param   \Hoa\Compiler\Llk           $self            AST producer.
     * @param   \Hoa\Compiler\Llk           $pp              Compiler compiler
     *                                                       to meta-ize (useful
     *                                                       to get tokens and
     *                                                       rules).
     * @param   \Hoa\Regex\Visitor\Realdom  $regexVisitor    Regex visitor (to
     *                                                       interprete tokens).
     * @return  void
     */
    public function __construct ( \Hoa\Compiler\Llk          $self,
                                  \Hoa\Compiler\Llk          $pp,
                                  \Hoa\Regex\Visitor\Realdom $regexVisitor ) {

        $this->_self = $self;

        foreach($pp->getTokens() as $_context => $element) {

            $out = array();

            foreach($element as $name => $_) {

                @list($realname, $context) = explode(':', $name);

                if(null === $context)
                    $context = $_context;

                $out[$realname] = array($_, $context);
            }

            $this->_tokens[$_context] = $out;
        }

        foreach($pp->getRules() as $name => $rule) {

            if('#' == $name[0])
                $name = substr($name, 1);

            $this->_rules[$name] = $rule;
        }

        if(null === self::$_regexVisitor)
            self::$_regexVisitor = $regexVisitor;

        if(null === self::$_regex)
            self::$_regex        = \Hoa\Compiler\Llk::load(
                new \Hoa\File\Read('hoa://Library/Regex/Grammar.pp')
            );

        return;
    }

    /**
     * Visit an element.
     *
     * @access  public
     * @param   \Hoa\Visitor\Element  $element    Element to visit.
     * @param   mixed                 &$handle    Handle (reference).
     * @param   mixed                 $eldnah     Handle (not reference).
     * @return  mixed
     */
    public function visit ( \Hoa\Visitor\Element $element,
                            &$handle = null, $eldnah = null ) {

        $out = null;

        switch($element->getId()) {

            case '#rule':
                foreach($element->getChildren() as $child)
                    $out .= $child->accept($this, $handle, $eldnah);
              break;

            case '#alternation':
                $out .= $element->getChild(
                    self::$_regexVisitor->getSampler()->getInteger(
                        0,
                        $element->getChildrenNumber() - 1
                    )
                )->accept($this, $handle, $eldnah);
              break;

            case '#concatenation':
            case '#capturing':
                foreach($element->getChildren() as $child)
                    $out .= $child->accept($this, $handle, $eldnah);
              break;

            case '#quantification':
                $lower = null;
                $upper = null;
                $value = $element->getChild(1)->getValueValue();

                switch($element->getChild(1)->getValueToken()) {

                    case 'zero_or_one':
                        $lower = 0;
                        $upper = 1;
                      break;

                    case 'zero_or_more':
                        $lower = 0;
                      break;

                    case 'one_or_more':
                        $lower = 1;
                      break;

                    case 'n_to_m':
                        $value = explode(',', substr($value, 1, -1));
                        $lower = (int) trim($value[0]);
                        $upper = (int) trim($value[1]);
                      break;

                    case 'n_or_more':
                        $value = explode(',', substr($value, 1, -1));
                        $lower = (int) trim($value[0]);
                      break;
                }

                if(null === $upper)
                    $upper = 7;

                $sampler = self::$_regexVisitor->getSampler();

                for($i = 0, $m = $sampler->getInteger($lower, $upper);
                    $i < $m;
                    ++$i)
                    $out .= $element->getChild(0)->accept(
                        $this,
                        $handle,
                        $eldnah
                    );
              break;

            case '#skipped':
            case '#kept':
                $value          = $element->getChild(0)->getValueValue();
                $context        = $this->_context;
                $this->_context = $this->_tokens[$context][$value][1];

                if(1 < $element->getChildrenNumber()) {

                    $i = (int) $element->getChild(1)->getValueValue();

                    if(!isset($this->_unification[$this->_u][$value]))
                        $this->_unification[$this->_u][$value] = array();

                    if(!isset($this->_unification[$this->_u][$value][$i]))
                        $this->_unification[$this->_u][$value][$i] =
                            $newValue = $this->token(
                                $this->_tokens[$context][$value][0]
                            );
                    else
                        $newValue = $this->_unification[$this->_u][$value][$i];
                }
                else
                    $newValue       = $this->token(
                        $this->_tokens[$context][$value][0]
                    );

                $out .= $newValue;
              break;

            case '#named':
                $this->_unification[]  = array();
                ++$this->_u;
                $out                  .= $this->_self->parse($this->_rules[
                    $element->getChild(0)->getValueValue()
                ])->accept($this, $handle, $eldnah);

                array_pop($this->_unification);
                --$this->_u;
              break;

            default:
                throw new \Hoa\Core\Exception(
                    'I donnot understand %s.', 1, $element->getId());
        }

        return $out;
    }

    /**
     * Generate token.
     *
     * @access  public
     * @param   string  $token    Token.
     * @return  string
     */
    protected function token ( $token ) {

        if(isset($this->_parsedTokens[$token]))
            $ast = $this->_parsedTokens[$token];
        else
            $ast = $this->_parsedTokens[$token] = self::$_regex->parse($token);

        return $ast->accept(self::$_regexVisitor);
    }
}

}
