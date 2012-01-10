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
 * \Hoa\Visitor\Visit
 */
-> import('Visitor.Visit')

/**
 * \Hoa\Compiler\Llk
 */
-> import('Compiler.Llk')

/**
 * \Hoa\Compiler\Visitor\Uniform
 */
-> import('Compiler.Visitor.Uniform')

/**
 * \Hoa\Compiler\Visitor\UniformPreCompute
 */
-> import('Compiler.Visitor.UniformPreCompute')

/**
 * \Hoa\Regex\Visitor\Uniform
 */
-> import('Regex.Visitor.Uniform')

/**
 * \Hoa\Regex\Visitor\UniformPreCompute
 */
-> import('Regex.Visitor.UniformPreCompute')

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
 * @copyright  Copyright © 2007-2012 Ivan Enderlin.
 * @license    New BSD License
 */

class Meta implements \Hoa\Visitor\Visit {

    /**
     * Grammar tokens.
     *
     * @var \Hoa\Compiler\Visitor\Meta array
     */
    protected $_tokens          = array();

    /**
     * Grammar rules.
     *
     * @var \Hoa\Compiler\Visitor\Meta array
     */
    protected $_rules           = array();

    /**
     * Visitor of tokens (uniform).
     *
     * @var \Hoa\Regex\Visitor\Uniform object
     */
    protected $_tokenSampler    = null;

    /**
     * Visitor of tokens (uniform pre-compute).
     *
     * @var \Hoa\Regex\Visitor\UniformPreCompute object
     */
    protected $_tokenPreCompute = null;

    /**
     * Visitor of rules (uniform).
     *
     * @var \Hoa\Compiler\Visitor\Uniform object
     */
    protected $_ruleSampler     = null;

    /**
     * Visitor of rules (uniform pre-compute).
     *
     * @var \Hoa\Compiler\Visitor\UniformPrecompute object
     */
    protected $_rulePreCompute  = null;



    /**
     * @access  public
     * @param   \Hoa\Compiler\Llk  $grammar    Grammar.
     * @param   \Hoa\Test\Sampler  $sampler    Numeric-sampler.
     * @param   int                $n          Size of data to generate.
     *                                         This is the number of tokens.
     * @return  void
     */
    public function __construct ( \Hoa\Compiler\Llk $grammar,
                                  \Hoa\Test\Sampler $sampler,
                                  $n = 1 ) {

        // Initialize.
        $llk   = \Hoa\Compiler\Llk::load(new \Hoa\File\Read(
            'hoa://Library/Compiler/Llk.pp'
        ));
        $regex = \Hoa\Compiler\Llk::load(new \Hoa\File\Read(
            'hoa://Library/Regex/Grammar.pp'
        ));

        $this->_tokenSampler    = new \Hoa\Regex\Visitor\Uniform($sampler, $n);
        $this->_ruleSampler     = new \Hoa\Compiler\Visitor\Uniform($sampler, $n);
        $this->_tokenPreCompute = new \Hoa\Regex\Visitor\UniformPreCompute($n);
        $this->_rulePreCompute  = new \Hoa\Compiler\Visitor\UniformPreCompute($n);

        $this->_ruleSampler->setMeta($this);
        $this->_rulePreCompute->setMeta($this);

        // Collect.
        foreach($grammar->getTokens() as $namespace => $tokens) {

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

        foreach($grammar->getRules() as $name => $rule) {

            if('#' == $name[0])
                $name = substr($name, 1);

            $this->_rules[$name] = array(
                'value' => $rule,
                'ast'   => $llk->parse($rule)
            );
        }

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

        foreach($this->_tokens as $token)
            $this->_tokenPreCompute->visit($token['ast']);

        foreach($this->_rules as $rule)
            $this->_rulePreCompute->visit($rule['ast']);

        return $out = $this->getRuleSampler()->visit(
            $element,
            $handle,
            $eldnah
        );
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
     * Get the token visitor (uniform).
     *
     * @access  public
     * @return  \Hoa\Regex\Visitor\Uniform
     */
    public function getTokenSampler ( ) {

        return $this->_tokenSampler;
    }

    /**
     * Get the token visitor (uniform pre-compute).
     *
     * @access  public
     * @return  \Hoa\Regex\Visitor\UniformPreCompute
     */
    public function getTokenPreCompute ( ) {

        return $this->_tokenPreCompute;
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

    /**
     * Get the rule visitor (uniform).
     *
     * @access  public
     * @return  \Hoa\Compiler\Visitor\Uniform
     */
    public function getRuleSampler ( ) {

        return $this->_ruleSampler;
    }

    /**
     * Set size.
     *
     * @access  public
     * @param   int  $n    Size.
     * @return  void
     */
    public function setSize ( $n ) {

        $this->_tokenSampler->setSize($n);
        $this->_ruleSampler->setSize($n);
        $this->_tokenPreCompute->setSize($n);
        $this->_rulePreCompute->setSize($n);

        return;
    }
}

}
