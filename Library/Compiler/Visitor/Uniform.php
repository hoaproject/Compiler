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
-> import('Visitor.Visit');

}

namespace Hoa\Compiler\Visitor {

/**
 * Class \Hoa\Compiler\Visitor\Uniform.
 *
 * Generate a data of size n that can be matched by a LL(k) grammar.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2012 Ivan Enderlin.
 * @license    New BSD License
 */

class Uniform implements \Hoa\Visitor\Visit {

    /**
     * Numeric-sampler.
     *
     * @var \Hoa\Test\Sampler object
     */
    protected $_sampler = null;

    /**
     * Given size: n.
     *
     * @var \Hoa\Compiler\Visitor\Uniform int
     */
    protected $_n       = 0;



    /**
     * Initialize numeric-sampler and the size.
     *
     * @access  public
     * @param   \Hoa\Test\Sampler  $sampler    Numeric-sampler.
     * @param   int                $n          Size.
     * @return  void
     */
    public function __construct ( \Hoa\Test\Sampler $sampler, $n = 0 ) {

        $this->_sampler = $sampler;
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
        $data = $element->getData();

        if(0 == $computed = $data['precompute'][$n]['n'])
            return null;

        switch($element->getId()) {

            case '#rule':
            case '#skipped':
            case '#kept':
                return $element->getChild(0)->accept($this, $handle, $n);
              break;

            case '#alternation':
                $stat = array();

                foreach($element->getChildren() as $c => $child) {

                    $foo      = $child->getData();
                    $stat[$c] = $foo['precompute'][$n]['n'];
                }

                $i = $this->_sampler->getInteger(1, $computed);

                for($e = 0, $b = $stat[$e], $max = count($stat);
                    $e < $max - 1 && $i > $b;
                    $b += $stat[++$e]);

                return $element->getChild($e)->accept($this, $handle, $n);
              break;

            case '#concatenation':
                $out = null;
                $Γ   = $data['precompute'][$n]['Γ'];
                $γ   = $Γ[$this->_sampler->getInteger(0, count($Γ) - 1)];

                foreach($element->getChildren() as $i => $child)
                    $out .= $child->accept($this, $handle, $γ[$i]);

                return $out;
              break;

            case '#quantification':
                $out  = null;
                $stat = $data['precompute'][$n]['xy'];
                $i    = $this->_sampler->getInteger(1, $computed);
                $b    = 0;
                $x    = key($stat);

                foreach($stat as $α => $st)
                    if($i <= $b += $st['n'])
                        break;

                for($j = 0; $j < $α; ++$j)
                    $out .= $element->getChild(0)->accept(
                        $this,
                        $handle,
                        $st['Γ'][$j]
                    );

                return $out;
              break;

            case '#named':
                $rule = $this->getMeta()->getRule(
                    $element->getChild(0)->getValueValue()
                );

                if(null === $rule)
                    throw new Exception(
                        'Something has failed somewhere. Good luck. ' .
                        '(Cue: the rule %s does not exist).',
                        0, $element->getChild(0)->getValueValue());

                return $rule['ast']->accept($this, $handle, $n);
              break;

            case 'token':
                $token = $this->getMeta()->getToken(
                    $element->getValueValue()
                );

                if(null === $token)
                    throw new Exception(
                        'Something has failed somewhere. Good luck. ' .
                        '(Clue: the token %s does not exist).',
                        1, $element->getValueValue());

                return $token['ast']->accept(
                    $this->getMeta()->getTokenSampler(),
                    $handle,
                    $n
                );
              break;
        }

        return;
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
