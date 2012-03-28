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
 * \Hoa\Compiler\Visitor\Generic
 */
-> import('Compiler.Visitor.Generic')

/**
 * \Hoa\Compiler\Llk\Rule\Entry
 */
-> import('Compiler.Llk.Rule.Entry')

/**
 * \Hoa\Compiler\Llk\Rule\Ekzit
 */
-> import('Compiler.Llk.Rule.Ekzit')

/**
 * \Hoa\Visitor\Visit
 */
-> import('Visitor.Visit')

/**
 * \Hoa\Iterator
 */
-> import('Iterator.~')

/**
 * \Hoa\Test\Sampler\Random
 */
-> import('Test.Sampler.Random')

/**
 * \Hoa\Regex\Visitor\Isotropic
 */
-> import('Regex.Visitor.Isotropic');


}

namespace Hoa\Compiler\Visitor {

/**
 * Class \Hoa\Compiler\Visitor\BoundedExaustive.
 *
 * Generate data by walking in all branches, all combinations etc.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @author     Frédéric Dadeau <frederic.dadeau@femto-st.fr>
 * @copyright  Copyright © 2007-2012 Ivan Enderlin.
 * @license    New BSD License
 */

class          BoundedExaustive
    extends    Generic
    implements \Hoa\Visitor\Visit,
               \Hoa\Iterator {

    /**
     * Given size: n.
     *
     * @var \Hoa\Compiler\Visitor\BoundedExaustive int
     */
    protected $_n        = 0;

    /**
     * Root rule.
     *
     * @var \Hoa\Visitor\Element object
     */
    protected $_rootRule = null;

    /**
     * Todo trace.
     *
     * @var \Hoa\Compiler\Visitor\BoundedExaustive array
     */
    protected $_todo     = null;

    /**
     * Trace.
     *
     * @var \Hoa\Compiler\Visitor\BoundedExaustive array
     */
    protected $_trace    = null;

    /**
     * Current key of the iterator.
     *
     * @var \Hoa\Compiler\Visitor\BoundedExaustive int
     */
    protected $_key      = -1;

    /**
     * Current element of the iterator.
     *
     * @var \Hoa\Compiler\Visitor\BoundedExaustive string
     */
    protected $_current  = null;



    /**
     * Initialize numeric-sampler and the size.
     *
     * @access  public
     * @param   \Hoa\Compiler\Llk\Parser  $grammar         Grammar.
     * @param   string                    $rootRuleName    Root rule name.
     * @param   int                       $n               Token size.
     * @param   \Hoa\Test\Sampler         $sampler         Numeric-sampler.
     * @param   \Hoa\Regex\Visitor\Visit  $tokenSampler    Token sampler.
     * @return  void
     */
    public function __construct ( \Hoa\Compiler\Llk\Parser $grammar,
                                                           $rootRuleName = null,
                                                           $n            = 7,
                                  \Hoa\Test\Sampler        $sampler      = null,
                                  \Hoa\Regex\Visitor\Visit $tokenSampler = null ) {

        parent::__construct(
            $grammar,
            $rootRuleName,
            $sampler      ?: $sampler = new \Hoa\Test\Sampler\Random(),
            $tokenSampler ?: new \Hoa\Regex\Visitor\Isotropic($sampler)
        );
        $this->_rootRule = $this->getRuleAst($this->_rootRuleName);
        $this->setSize($n);

        return;
    }

    /**
     * Get the current value of the iterator.
     *
     * @access  public
     * @return  string
     */
    public function current ( ) {

        return $this->_current;
    }

    /**
     * Get the current key of the iterator.
     *
     * @access  public
     * @return  int
     */
    public function key ( ) {

        return $this->_key;
    }

    /**
     * Advance the pointer to the next position in the iterator.
     *
     * @access  public
     * @return  void
     */
    public function next ( ) {

        return;
    }

    /**
     * Rewind the pointer of the iterator.
     *
     * @access  public
     * @return  void
     */
    public function rewind ( ) {

        unset($this->_trace);
        unset($this->_todo);
        $this->_key     = -1;
        $this->_current = null;
        $this->_trace   = array();
        $closeRule      = new \Hoa\Compiler\Llk\Rule\Ekzit($this->_rootRule, 0);
        $openRule       = new \Hoa\Compiler\Llk\Rule\Entry(
            $this->_rootRule,
            0,
            array($closeRule)
        );
        $this->_todo    = array($closeRule, $openRule);

        return;
    }

    /**
     * Check if the current value is valid.
     *
     * @access  public
     * @return  bool
     */
    public function valid ( ) {

        if(   null !== $this->_current
           && true !== $this->backtrack())
            return false;

        if(true !== $this->unfold())
            return false;

        $this->_current = $this->generate();
        ++$this->_key;

        return true;
    }

    /**
     * Unfold a solution.
     *
     * @access  protected
     * @return  bool
     */
    protected function unfold ( ) {

        while(0 < count($this->_todo)) {

            $rule = array_pop($this->_todo);

            if($rule instanceof \Hoa\Compiler\Llk\Rule\Ekzit)
                $this->_trace[] = $rule;
            else {

                $next   = $rule->getData();
                $Rule   = $rule->getRule();
                $result = $Rule->accept($this, $handle, array(
                    $this->getSize(),
                    $next
                ));

                if(null === $result) {

                    if(true !== $this->backtrack())
                        return null;
                }
            }
        }

        return true;
    }

    /**
     * Backtrack the solution.
     *
     * @access  protected
     * @return  bool
     */
    protected function backtrack ( ) {

        $found = false;

        do {

            $last = array_pop($this->_trace);

            if($last instanceof \Hoa\Compiler\Llk\Rule\Entry)
                $found = '#choice' == $last->getRule()->getId();
            elseif($last instanceof \Hoa\Compiler\Llk\Rule\Ekzit)
                $found = '#repetition' == $last->getRule()->getId();

        } while(0 < count($this->_trace) && false === $found);

        if(false === $found)
            return false;

        $next          = $last->getData() + 1;
        $this->_todo   = $last->getTodo();
        $this->_todo[] = new \Hoa\Compiler\Llk\Rule\Entry(
            $last->getRule(),
            $next,
            $this->_todo
        );

        return true;
    }

    /**
     * Generate a token.
     *
     * @access  protected
     * @return  string
     */
    protected function generate ( ) {

        $out   = null;
        $_skip = $this->getToken('skip');
        $skip  = $_skip['ast'];

        foreach($this->_trace as $trace)
            if(   $trace instanceof \Hoa\Visitor\Element
               && 'token' == $trace->getId())
                $out .= $this->sample($trace) .
                        $skip->accept($this->_tokenSampler);

        return $out;
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

        list($maxlength, $next) = $eldnah;

        switch($element->getId()) {

            case '#rule':
            case '#skipped':
            case '#kept':
                return $element->getChild(0)->accept($this, $handle, $eldnah);
              break;

            case '#choice':
                if($next >= $element->getChildrenNumber())
                    return null;

                $this->_trace[] = new \Hoa\Compiler\Llk\Rule\Entry(
                    $element,
                    $next,
                    $this->_todo
                );
                $nextRule       = $element->getChild($next);
                $this->_todo[]  = new \Hoa\Compiler\Llk\Rule\Ekzit(
                    $nextRule,
                    0,
                    $this->_todo
                );
                $this->_todo[]  = new \Hoa\Compiler\Llk\Rule\Entry(
                    $nextRule,
                    0,
                    $this->_todo
                );

                return true;
              break;

            case '#concatenation':
                $this->_trace[] = new \Hoa\Compiler\Llk\Rule\Entry(
                    $element,
                    $next,
                    $this->_todo
                );

                for($i = $element->getChildrenNumber() - 1; $i >= 0; --$i) {

                    $nextRule      = $element->getChild($i);
                    $this->_todo[] = new \Hoa\Compiler\Llk\Rule\Ekzit(
                        $nextRule,
                        0,
                        $this->_todo
                    );
                    $this->_todo[] = new \Hoa\Compiler\Llk\Rule\Entry(
                        $nextRule,
                        0,
                        $this->_todo
                    );
                }

                return true;
              break;

            case '#repetition':
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

                    $this->_trace[] = new \Hoa\Compiler\Llk\Rule\Entry(
                        $element,
                        $next,
                        $this->_todo
                    );
                    $child          = $element->getChild(0);

                    for($i = 0; $i < $x; ++$i) {

                        $this->_todo[] = new \Hoa\Compiler\Llk\Rule\Ekzit(
                            $child,
                            0,
                            $this->_todo
                        );
                        $this->_todo[] = new \Hoa\Compiler\Llk\Rule\Entry(
                            $child,
                            0,
                            $this->_todo
                        );
                    }

                    return true;
                }

                $nbToken = 0;

                foreach($this->_trace as $t)
                    if(   $t instanceof \Hoa\Visitor\Element
                       && 'token' == $t->getId())
                        ++$nbToken;

                $max = -1 == $y ? $maxlength : $y;

                if($nbToken + 1 > $max)
                    return null;

                $this->_todo[] = new \Hoa\Compiler\Llk\Rule\Ekzit(
                    $element,
                    $next,
                    $this->_todo
                );
                $child         = $element->getChild(0);
                $this->_todo[] = new \Hoa\Compiler\Llk\Rule\Ekzit(
                    $child,
                    0,
                    $this->_todo
                );
                $this->_todo[] = new \Hoa\Compiler\Llk\Rule\Entry(
                    $child,
                    0,
                    $this->_todo
                );

                return true;
              break;

            case '#named':
                $rule = $this->getRule(
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

                foreach($this->_trace as $t)
                    if(   $t instanceof \Hoa\Visitor\Element
                       && 'token' == $t->getId())
                        ++$nbToken;

                if($nbToken >= $maxlength)
                    return null;

                $this->_trace[] = $element;
                array_pop($this->_todo);

                return true;
              break;
        }

        return false;
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
}

}
