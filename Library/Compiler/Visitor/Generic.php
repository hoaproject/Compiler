<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2012, Ivan Enderlin. All rights reserved.
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
 * \Hoa\Compiler\Visitor\Exception
 */
-> import('Compiler.Visitor.Exception')

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
 * Class \Hoa\Compiler\Visitor\Generic.
 *
 *
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2012 Ivan Enderlin.
 * @license    New BSD License
 */

class Generic {

    /**
     * Grammar tokens.
     *
     * @var \Hoa\Compiler\Visitor\Generic array
     */
    protected $_tokens          = array();

    /**
     * Grammar rules.
     *
     * @var \Hoa\Compiler\Visitor\Generic array
     */
    protected $_rules           = array();

    protected $_grammar      = null;
    protected $_rootRuleName = null;

    /**
     * Numeric-sampler.
     *
     * @var \Hoa\Test\Sampler object
     */
    protected $_sampler      = null;
    protected $_tokenSampler = null;



    /**
     * @access  public
     * @param   \Hoa\Compiler\Llk  $grammar    Grammar.
     * @return  void
     */
    public function __construct ( \Hoa\Compiler\Llk        $grammar,
                                                           $rootRuleName = null,
                                  \Hoa\Test\Sampler        $sampler      = null,
                                  \Hoa\Regex\Visitor\Visit $tokenSampler = null ) {

        $this->_grammar      = $grammar;
        $this->_rootRuleName = $rootRuleName ?: $grammar->getRootRule();
        $this->_sampler      = $sampler;
        $this->_tokenSampler = $tokenSampler;

        $llk   = \Hoa\Compiler\Llk::load(new \Hoa\File\Read(
            'hoa://Library/Compiler/Llk.pp'
        ));
        $regex = \Hoa\Compiler\Llk::load(new \Hoa\File\Read(
            'hoa://Library/Regex/Grammar.pp'
        ));

        foreach($this->_grammar->getTokens() as $namespace => $tokens) {

            foreach($tokens as $name => $value) {

                if(null === $value)
                    continue;

                if(false !== $pos = strpos($name, ':'))
                    $name = substr($name, 0, $pos);

                $this->_tokens[$name] = array(
                    'value' => $value,
                    'ast'   => $regex->parse($value)
                );
            }
        }

        foreach($this->_grammar->getRules() as $name => $rule) {

            if('#' == $name[0])
                $name = substr($name, 1);

            $this->_rules[$name] = array(
                'value' => $rule,
                'ast'   => $llk->parse($rule)
            );
        }

        return;
    }

    public function &getTokens ( ) {

        return $this->_tokens;
    }

    /**
     * Get a specific token bucket.
     *
     * @access  public
     * @param   string  $token    Token name.
     * @return  array
     */
    public function &getToken ( $token ) {

        if(!isset($this->_tokens[$token])) {

            $out = null;

            return $out;
        }

        return $this->_tokens[$token];
    }

    /**
     * Get the AST of a specific rule.
     *
     * @access  public
     * @param   string  $rule    Rule name (without any leading #).
     * @return  \Hoa\Compiler\TreeNode
     */
    public function getRuleAst ( $rule ) {

        if(!isset($this->_rules[$rule]))
            return null;

        return $this->_rules[$rule]['ast'];
    }

    public function getRules ( ) {

        return $this->_rules;
    }

    /**
     * Get a specific rule bucket.
     *
     * @access  public
     * @param   string  $rule    Rule name (without any leading #).
     * @return  array
     */
    public function &getRule ( $rule ) {

        if(!isset($this->_rules[$rule])) {

            $out = null;

            return $out;
        }

        return $this->_rules[$rule];
    }
}

}
