<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2013, Ivan Enderlin. All rights reserved.
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
 * \Hoa\Compiler\Llk\Sampler
 */
-> import('Compiler.Llk.Sampler.~')

/**
 * \Hoa\Compiler\Llk\Sampler\Exception
 */
-> import('Compiler.Llk.Sampler.Exception')

/**
 * \Hoa\Math\Sampler\Random
 */
-> import('Math.Sampler.Random')

/**
 * \Hoa\Math\Combinatorics\Combination\Gamma
 */
-> import('Math.Combinatorics.Combination.Gamma')

/**
 * \Hoa\Math\Util
 */
-> import('Math.Util');

}

namespace Hoa\Compiler\Llk\Sampler {

/**
 * Class \Hoa\Compiler\Llk\Sampler\Uniform.
 *
 * This generator aims at producing random and uniform a sequence of a fixed
 * size. We use the recursive method to count all possible sub-structures of
 * size n. The counting helps to compute cumulative distribution functions,
 * which guide the exploration.
 * Repetition unfolding: upper bound of + and * is set to n.
 *
 * @author     Frédéric Dadeau <frederic.dadeau@femto-st.fr>
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2013 Frédéric Dadeau, Ivan Enderlin.
 * @license    New BSD License
 */
class Uniform extends Sampler {

    /**
     * Data (pre-computing).
     *
     * @var \Hoa\Compiler\Llk\Sampler\Uniform array
     */
    protected $_data   = array();

    /**
     * Bound.
     *
     * @var \Hoa\Compiler\Llk\Sampler\Uniform int
     */
    protected $_length = 5;



    /**
     * Construct a generator.
     *
     * @access  public
     * @param   \Hoa\Compiler\Llk\Parser  $compiler        Compiler/parser.
     * @param   \Hoa\Visitor\Visit        $tokenSampler    Token sampler.
     * @return  void
     */
    public function __construct ( \Hoa\Compiler\Llk\Parser $compiler,
                                  \Hoa\Visitor\Visit       $tokenSampler,
                                  $length = 5 ) {

        parent::__construct($compiler, $tokenSampler);

        foreach($this->_rules as $name => $_)
            $this->_data[$name] = array();

        $this->setLength($length);
        $this->_sampler = new \Hoa\Math\Sampler\Random();

        return;
    }

    /**
     * The random and uniform algorithm.
     *
     * @access  public
     * @param   \Hoa\Compiler\Llk\Rule  $rule    Rule to start.
     * @param   int                     $n       Size.
     * @return  string
     */
    public function uniform ( \Hoa\Compiler\Llk\Rule $rule = null, $n = -1 ) {

        if(null === $rule && -1 === $n) {

            $rule = $this->_rules[$this->_rootRuleName];
            $n    = $this->getLength();
        }

        $data     = &$this->_data[$rule->getName()][$n];
        $computed = $data['n'];

        if(0 === $n || 0 === $computed)
            return null;

        if($rule instanceof \Hoa\Compiler\Llk\Rule\Choice) {

            $children = $rule->getContent();
            $stat     = array();

            foreach($children as $c => $child)
                $stat[$c] = $this->_data[$child][$n]['n'];

            $i = $this->_sampler->getInteger(1, $computed);

            for($e = 0, $b = $stat[$e], $max = count($stat) - 1;
                $e < $max && $i > $b;
                $b += $stat[++$e]);

            return $this->uniform($this->_rules[$children[$e]], $n);
        }
        elseif($rule instanceof \Hoa\Compiler\Llk\Rule\Concatenation) {

            $children = $rule->getContent();
            $out      = null;
            $Γ        = $data['Γ'];
            $γ        = $Γ[$this->_sampler->getInteger(0, count($Γ) - 1)];

            foreach($children as $i => $child)
                $out .= $this->uniform($this->_rules[$child], $γ[$i]);

            return $out;
        }
        elseif($rule instanceof \Hoa\Compiler\Llk\Rule\Repetition){

            $out   =  null;
            $stat  = &$data['xy'];
            $child =  $this->_rules[$rule->getContent()];
            $b     =  0;
            $i     =  $this->_sampler->getInteger(1, $computed);

            foreach($stat as $α => $st)
                if($i <= $b += $st['n'])
                    break;

            $Γ = &$st['Γ'];
            $γ = &$Γ[$this->_sampler->getInteger(0, count($Γ) - 1)];

            for($j = 0; $j < $α; ++$j)
                $out .= $this->uniform($child, $γ[$j]);

            return $out;
        }
        elseif($rule instanceof \Hoa\Compiler\Llk\Rule\Token) {

            return $this->generateToken($rule);
        }

        return null;
    }

    /**
     * Recursive method applied to our problematic.
     *
     * @access  public
     * @param   \Hoa\Compiler\Llk\Rule  $rule    Rule to start.
     * @param   int                     $n       Size.
     * @return  int
     */
    public function count ( \Hoa\Compiler\Llk\Rule $rule = null, $n = -1 ) {

        if(null === $rule || -1 === $n)
            return 0;

        $ruleName = $rule->getName();

        if(isset($this->_data[$ruleName][$n]))
            return $this->_data[$ruleName][$n]['n'];

        $this->_data[$ruleName][$n] =  array('n' => 0);
        $out                        = &$this->_data[$ruleName][$n]['n'];
        $rule                       =  $this->_rules[$ruleName];

        if($rule instanceof \Hoa\Compiler\Llk\Rule\Choice) {

            foreach($rule->getContent() as $child)
                $out += $this->count($this->_rules[$child], $n);
        }
        elseif($rule instanceof \Hoa\Compiler\Llk\Rule\Concatenation) {

            $children = $rule->getContent();
            $Γ        = new \Hoa\Math\Combinatorics\Combination\Gamma(
                count($children),
                $n
            );
            $this->_data[$ruleName][$n]['Γ'] = array();
            $handle = &$this->_data[$ruleName][$n]['Γ'];

            foreach($Γ as $γ) {

                $oout = 1;

                foreach($γ as $α => $_γ)
                    $oout *= $this->count($this->_rules[$children[$α]], $_γ);

                if(0 !== $oout)
                    $handle[] = $γ;

                $out += $oout;
            }
        }
        elseif($rule instanceof \Hoa\Compiler\Llk\Rule\Repetition) {

            $this->_data[$ruleName][$n]['xy'] = array();
            $handle = &$this->_data[$ruleName][$n]['xy'];
            $child  =  $this->_rules[$rule->getContent()];
            $x      =  $rule->getMin();
            $y      =  (-1 === $rule->getMax() ? $n : $rule->getMax());

            if(0 === $x && $x === $y)
                $out = 1;
            else
                for($α = $x; $α <= $y; ++$α) {

                    $ut         = 0;
                    $handle[$α] = array('n' => 0, 'Γ' => array());
                    $Γ          = new \Hoa\Math\Combinatorics\Combination\Gamma(
                        $α,
                        $n
                    );

                    foreach($Γ as $γ) {

                        $oout = 1;

                        foreach($γ as $β => $_γ)
                            $oout *= $this->count($child, $_γ);

                        if(0 !== $oout)
                            $handle[$α]['Γ'][] = $γ;

                        $ut += $oout;
                    }

                    $handle[$α]['n']  = $ut;
                    $out             += $ut;
                }
        }
        elseif($rule instanceof \Hoa\Compiler\Llk\Rule\Token) {

            $out = \Hoa\Math\Util::δ($n, 1);
        }

        return $out;
    }

    /**
     * Set upper-bound, the maximum data length.
     *
     * @access  public
     * @param   int  $length    Length.
     * @return  int
     */
    public function setLength ( $length ) {

        if(0 >= $length)
            throw new Exception(
                'Length must be greater than 0, given %d.', 0, $length);

        $old           = $this->_length;
        $this->_length = $length;
        $this->count(
            $this->_compiler->getRule($this->_rootRuleName),
            $length
        );

        return $old;
    }

    /**
     * Get upper-bound.
     *
     * @access  public
     * @return  int
     */
    public function getLength ( ) {

        return $this->_length;
    }
}

}
