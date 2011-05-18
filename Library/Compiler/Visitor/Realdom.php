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
 * \Hoa\COmpiler\Visitor\Exception
 */
-> import('Compiler.Visitor.Exception')

/**
 * \Hoa\Visitor\Visit
 */
-> import('Visitor.Visit');

}

namespace Hoa\Compiler\Visitor {

/**
 * Class \Hoa\Compiler\Visitor\Realdom.
 *
 * Interprete AST as a realdom (with \Hoa\Test\Sampler\*).
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2011 Ivan Enderlin.
 * @license    New BSD License
 */

class Realdom implements \Hoa\Visitor\Visit {

    /**
     * Current element.
     *
     * @var \Hoa\Visitor\Element object
     */
    protected $_element = null;

    /**
     * Sampler (aka handle).
     *
     * @var \Hoa\Test\Sampler object
     */
    protected $_sampler = null;

    /**
     * Handle (reference).
     *
     * @var \Hoa\Compiler\Visitor\Realdom mixed
     */
    protected $_handle  = null;

    /**
     * Handle (no reference).
     *
     * @var \Hoa\Compiler\Visitor\Realdom mixed
     */
    protected $_eldnah  = null;



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

        $out            = null;
        $this->_element = $element;
        $this->_sampler = $handle;
        $this->_handle  = $handle;
        $this->_eldnah  = $eldnah;

        switch($element->getId()) {

            case '#expression':
                if(!($handle instanceof \Hoa\Test\Sampler))
                    throw new Exception(
                        'Sampler must be of type \Hoa\Test\Sampler.', 41);

                foreach($element->getChildren() as $child)
                    $out .= $child->accept($this, $handle, $eldnah);
              break;

            case '#alternation':
                $out .= $this->alternation();
              break;

            case '#concatenation':
                $out .= $this->concatenation();
              break;

            case '#quantification':
                $out .= $this->quantification();
              break;

            case '#capturing':
            case '#namedcapturing':
                foreach($element->getChildren() as $child)
                    $out .= $child->accept($this, $handle, $eldnah);
              break;

            case '#class':
                $out .= $this->class_();
              break;

            case '#range':
                $out .= $this->range();
              break;

            case 'token':
                $out .= str_replace('\\', '', $element->getValueValue());
              break;

            default:
                throw new Exception(
                    'I donnot understand %s.', 40, $element->getId());
        }

        return $out;
    }

    /**
     * Visit the #alternation token.
     *
     * @access  protected
     * @return  string
     */
    protected function alternation ( ) {

        $out = $this->_element->getChild(
            $this->_sampler->getInteger(
                0,
                $this->_element->getChildrenNumber() - 1
            )
        )->accept($this, $this->_handle, $this->_eldnah);

        return $out;
    }

    /**
     * Visit the #concatenation token.
     *
     * @access  protected
     * @return  string
     */
    protected function concatenation ( ) {

        $out = null;

        foreach($this->_element->getChildren() as $child)
            $out .= $child->accept(
                $this,
                $this->_handle,
                $this->_eldnah
            );

        return $out;
    }

    /**
     * Visit the #quantification token.
     *
     * @access  protected
     * @return  string
     */
    public function quantification ( ) {

        $lower = null;
        $upper = null;
        $value = $this->_element->getChild(1)->getValueValue();

        switch($this->_element->getChild(1)->getValueToken()) {

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

            case 'exactly_n':
                $lower = $upper = (int) substr($value, 1, -1);
              break;

            case 'at_least_n_or_more_m':
                $value = explode(',', substr($value, 1, -1));
                $lower = (int) trim($value[0]);
                $upper = (int) trim($value[1]);
              break;

            case 'n_or_more':
                $value = explode(',', substr($value, 1, -1));
                $lower = (int) trim($value[0]);
              break;
        }

        // Avoid to long repetition.
        if(null === $upper)
            $upper = 7;

        $child = $this->_element->getChild(0);

        // Optimisation.
        if('#range' != $child->getId())
            return str_repeat(
                $child->accept($this, $this->_handle, $this->_eldnah),
                $this->_sampler->getInteger($lower, $upper)
            );

        $out = null;

        for($i = 0, $m = $this->_sampler->getInteger($lower, $upper);
            $i < $m;
            ++$i)
            $out .= $child->accept($this, $this->_handle, $this->_eldnah);

        return $out;
    }

    /**
     * Visit the #class token.
     *
     * @access  protected
     * @return  string
     */
    protected function class_ ( ) {

        return $this->_element->getChild(
            $this->_sampler->getInteger(
                0,
                $this->_element->getChildrenNumber() - 1
            )
        )->accept($this, $this->_handle, $this->_eldnah);
    }

    /**
     * Visit the #range token.
     *
     * @access  protected
     * @return  string
     */
    protected function range ( ) {

        $value = $this->_element->getChild(0)->getValueValue();
        $value = explode('-', substr($value, 1, -1));

        return chr($this->_sampler->getInteger(
            ord($value[0]),
            ord($value[1])
        ));
    }
}

}
