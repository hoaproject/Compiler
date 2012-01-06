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
 * \Hoa\Compiler\Visitor\Exception
 */
-> import('Compiler.Visitor.Exception')

/**
 * \Hoa\Visitor\Visit
 */
-> import('Visitor.Visit')

/**
 * \Hoa\Math\Util
 */
-> import('Math.Util')

/**
 * \Hoa\Math\Combinatorics\Combination
 */
-> import('Math.Combinatorics.Combination');

}

namespace Hoa\Compiler\Visitor {

/**
 * Class \Hoa\Compiler\Visitor\UniformPreCompute.
 *
 * Pre-compute the AST.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2011 Ivan Enderlin.
 * @license    New BSD License
 */

class UniformPreCompute implements \Hoa\Visitor\Visit {

    /**
     * Given size: n.
     *
     * @var \Hoa\Compiler\Visitor\UniformPreCompute int
     */
    protected $_n    = 0;

    protected $_meta = null;



    /**
     * Initialize the size.
     *
     * @access  public
     * @param   int  $n    Size.
     * @return  void
     */
    public function __construct ( $n = 0 ) {

        $this->setSize($n);

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

        $n    = null === $eldnah ? $this->_n : $eldnah;
        $data = &$element->getData();

        if(!isset($data['precompute']))
            $data['precompute'] = array($n => array());

        if(isset($data['precompute'][$n]['n']))
            return $data['precompute'][$n]['n'];

        $data['precompute'][$n]['n'] = 0;

        if(0 === $n)
            return 0;

        $out = &$data['precompute'][$n]['n'];

        switch($element->getId()) {

            case '#rule':
            case '#capturing':
            case '#skipped':
            case '#kept':
                return $out = $element->getChild(0)->accept($this, $handle, $n);
              break;

            case '#alternation':
                foreach($element->getChildren() as $child)
                    $out += $child->accept($this, $handle, $n);

                return $out;
              break;

            case '#concatenation':
                $Γ = \Hoa\Math\Combinatorics\Combination::Γ(
                    $element->getChildrenNumber(),
                    $n,
                    true
                );

                if(!isset($data['precompute'][$n]['Γ']))
                    $data['precompute'][$n]['Γ'] = array();

                foreach($Γ as $γ) {

                    if(true === in_array(0, $γ))
                        continue;

                    $oout = 1;

                    foreach($γ as $α => $_γ)
                        $oout *= $element->getChild($α)->accept(
                            $this,
                            $handle,
                            $_γ
                        );

                    if(0 !== $oout)
                        $data['precompute'][$n]['Γ'][] = $γ;

                    $out += $oout;
                }

                return $out;
              break;

            case '#quantification':
                if(!isset($data['precompute'][$n]['xy']))
                    $data['precompute'][$n]['xy'] = array();

                $xy = $element->getChild(1)->getValueValue();
                $x  = 0;
                $y  = 0;

                switch($element->getChild(1)->getValueToken()) {

                    case 'zero_or_one':
                        $y = 1;
                      break;

                    case 'zero_or_more':
                        $y = $this->getSize();
                      break;

                    case 'one_or_more':
                        $x = 1;
                        $y = $this->getSize();
                      break;

                    case 'exactly_n':
                        $x = $y = (int) substr($xy, 1, -1);
                      break;

                    case 'n_to_m':
                        $xy = explode(',', substr($xy, 1, -1));
                        $x  = (int) trim($xy[0]);
                        $y  = (int) trim($xy[1]);
                      break;

                    case 'n_or_more':
                        $xy = explode(',', substr($xy, 1, -1));
                        $x  = (int) trim($xy[0]);
                        $y  = $this->getSize();
                      break;
                }

                for($α = $x; $α <= $y; ++$α) {

                    $data['precompute'][$n]['xy'][$α] = array();
                    $Γ  = \Hoa\Math\Combinatorics\Combination::Γ($α, $n, true);
                    $ut = 0;

                    foreach($Γ as $γ) {

                        if(true === in_array(0, $γ))
                            continue;

                        $oout = 1;

                        foreach($γ as $β => $_γ)
                            $oout *= $element->getChild(0)->accept(
                                $this,
                                $handle,
                                $_γ
                            );

                        if(0 !== $oout)
                            $data['precompute'][$n]['xy'][$α]['Γ'] = $γ;

                        $ut += $oout;
                    }

                    $data['precompute'][$n]['xy'][$α]['n'] = $ut;
                    $out += $ut;
                }

                return $out;
              break;

            case '#named':
                $rule = $this->getMeta()->getRule(
                    $element->getChild(0)->getValueValue()
                );

                if(null === $rule)
                    throw new Exception(
                        'Something has failed somewhere. Good luck. ' .
                        '(Clue: the rule %s does not exist).',
                        0, $element->getChild(0)->getValueValue());

                return $out = $rule['ast']->accept($this, $handle, $n);
              break;

            case 'token':
                return $out = \Hoa\Math\Util::δ($n, 1);
              break;
        }

        return -1;
    }

    /**
     * Set size.
     *
     * @access  public
     * @param   int  $n    Size.
     * @return  int
     */
    public function setSize ( $n ) {

        $old      = $this->_n;
        $this->_n = $n;

        return $old;
    }

    /**
     * Get size.
     *
     * @access  public
     * @return  int
     */
    public function getSize ( ) {

        return $this->_n;
    }

    /**
     * Set meta visitor.
     *
     * @access  public
     * @param   \Hoa\Compiler\Visitor\Meta  $meta    Meta visitor.
     * @return  \Hoa\Compiler\Visitor\Meta
     */
    public function setMeta ( Meta $meta ) {

        $old         = $meta;
        $this->_meta = $meta;

        return $old;
    }

    /**
     * Get meta visitor.
     *
     * @access  public
     * @return  \Hoa\Compiler\Visitor\Meta
     */
    public function getMeta ( ) {

        return $this->_meta;
    }
}

}
