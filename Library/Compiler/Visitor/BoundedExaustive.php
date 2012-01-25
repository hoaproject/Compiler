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

-> import('Compiler.Visitor.Trace.RuleEntry')
-> import('Compiler.Visitor.Trace.RuleExit');

}

namespace Hoa\Compiler\Visitor {

/**
 * Class \Hoa\Compiler\Visitor\BoundedExaustive.
 *
 * Generate a data of size n that can be matched by a LL(k) grammar.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2012 Ivan Enderlin.
 * @license    New BSD License
 */

class BoundedExaustive implements \Hoa\Visitor\Visit {

    /**
     * Numeric-sampler.
     *
     * @var \Hoa\Test\Sampler object
     */
    protected $_sampler  = null;

    /**
     * Given size: n.
     *
     * @var \Hoa\Compiler\Visitor\BoundedExaustive int
     */
    protected $_n        = 0;

    protected $_todo  = null;
    protected $_trace = null;



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

    public function generate ( \Hoa\Visitor\Element $element,
                               &$handle = null, $eldnah = null ) {

        $this->_trace = array();
        $closeRule    = new Trace\RuleExit($element,  0, array());
        $openRule     = new Trace\RuleEntry($element, 0, array($closeRule));
        $this->_todo  = array($closeRule, $openRule);

        while(null !== $this->unfold()) {

            $this->printTrace();

            if(null === $lastCP = $this->backtrack($this->_trace))
                return;

            $this->_trace = $lastCP['trace'];
            $this->_todo  = $lastCP['todo'];
        }

        return;
    }

    public function unfold ( ) {

        while(0 < count($this->_todo)) {

            $rule = array_pop($this->_todo);

            if($rule instanceof Trace\RuleExit)
                $this->_trace[] = $rule;
            else {

                $next   = $rule->getData();
                $Rule   = $rule->getRule();
                $result = $Rule->accept($this, $handle, array(
                    $this->_trace,
                    $this->_todo,
                    $this->_n,
                    $next
                ));

                if(null === $result) {

                    $lastCP = $this->backtrack($this->_trace);

                    if(null === $lastCP)
                        return null;

                    $this->_trace = $lastCP['trace'];
                    $this->_todo  = $lastCP['todo'];
                }
                else {

                    $this->_trace = $result['trace'];
                    $this->_todo  = $result['todo'];
                }
            }
        }

        return true;
    }

    public function backtrack ( $trace ) {

        $found = false;

        do {

            $last = array_pop($trace);

            if($last instanceof Trace\RuleEntry)
                $found = '#alternation' == $last->getRule()->getId();
            elseif($last instanceof Trace\RuleExit)
                $found = '#quantification' == $last->getRule()->getId();

        } while(0 < count($trace) && false === $found);

        if(false === $found)
            return null;

        $next   = $last->getData() + 1;
        $todo   = $last->getTodo();
        $todo[] = new Trace\RuleEntry($last->getRule(), $next, $todo);

        return array('trace' => $trace, 'todo' => $todo);
    }

    public function printTrace ( ) {

        echo '>>> ';

        foreach($this->_trace as $trace)
            if(   $trace instanceof \Hoa\Visitor\Element
               && 'token' == $trace->getId())
                echo $this->sample($trace) . ' ';

        echo "\n";

        return;
    }

    public function sample ( \Hoa\Visitor\Element $element ) {

        $token = $this->getMeta()->getToken(
            $element->getValueValue()
        );

        if(null === $token)
            throw new Exception(
                'Something has failed somewhere. Good luck. ' .
                '(Clue: the token %s does not exist).',
                1, $element->getValueValue());

        return $token['ast']->accept($this->getMeta()->getTokenSampler());
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

        list($trace, $todo, $maxlength, $next) = $eldnah;

        switch($element->getId()) {

            case '#rule':
            case '#skipped':
            case '#kept':
                return $element->getChild(0)->accept($this, $handle, $eldnah);
              break;

            case '#alternation':
                if($next >= $element->getChildrenNumber())
                    return null;

                $trace[]  = new Trace\RuleEntry($element, $next, $todo);
                $nextRule = $element->getChild($next);
                $todo[]   = new Trace\RuleExit($nextRule, 0, $todo);
                $todo[]   = new Trace\RuleEntry($nextRule, 0, $todo);

                return array('trace' => $trace, 'todo' => $todo);
              break;

            case '#concatenation':
                $trace[] = new Trace\RuleEntry($element, $next, $todo);

                for($i = $element->getChildrenNumber() - 1; $i >= 0; --$i) {

                    $nextRule = $element->getChild($i);
                    $todo[]   = new Trace\RuleExit($nextRule, 0, $todo);
                    $todo[]   = new Trace\RuleEntry($nextRule, 0, $todo);
                }

                return array('trace' => $trace, 'todo' => $todo);
              break;

            case '#quantification':
                $xy = $element->getChild(1)->getValueValue();
                $x  = 0;
                $y  = 0;

                switch($element->getChild(1)->getValueToken()) {

                    case 'zero_or_one':
                        $y = 1;
                      break;

                    case 'zero_or_more':
                        $y = -1;
                      break;

                    case 'one_or_more':
                        $x =  1;
                        $y = -1;
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
                        $y  = -1;
                      break;
                }

                if(0 === $next) {

                    $trace[] = new Trace\RuleEntry($element, $next, $todo);
                    $child   = $element->getChild(0);

                    for($i = 0; $i < $x; ++$i) {

                        $todo[] = new Trace\RuleExit($child, 0, $todo);
                        $todo[] = new Trace\RuleEntry($child, 0, $todo);
                    }

                    return array('trace' => $trace, 'todo' => $todo);
                }

                $nbToken = 0;

                foreach($trace as $t)
                    if(   $t instanceof \Hoa\Visitor\Element
                       && 'token' == $t->getId())
                        ++$nbToken;

                $max = -1 == $y ? $maxlength : $y;

                if($nbToken + 1 > $max)
                    return null;

                $todo[] = new Trace\RuleExit($element, $next, $todo);
                $child  = $element->getChild(0);
                $todo[] = new Trace\RuleExit($child, 0, $todo);
                $todo[] = new Trace\RuleEntry($child, 0, $todo);

                return array('trace' => $trace, 'todo' => $todo);
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

                return $rule['ast']->accept($this, $handle, $eldnah);
              break;

            case 'token':
                $nbToken = 0;

                foreach($trace as $t)
                    if(   $t instanceof \Hoa\Visitor\Element
                       && 'token' == $t->getId())
                        ++$nbToken;

                if($nbToken >= $maxlength)
                    return null;

                $trace[] = $element;
                array_pop($todo);

                return array('trace' => $trace, 'todo' => $todo);
              break;
        }

        return '???';
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
