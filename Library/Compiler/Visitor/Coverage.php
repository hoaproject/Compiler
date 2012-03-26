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
 * Class \Hoa\Compiler\Visitor\Coverage.
 *
 * Generate data by covering all branches.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @author     Frédéric Dadeau <frederic.dadeau@femto-st.fr>
 * @copyright  Copyright © 2007-2012 Ivan Enderlin.
 * @license    New BSD License
 */

class          Coverage
    extends    Generic
    implements \Hoa\Visitor\Visit,
               \Hoa\Iterator {

    /**
     * Root rule.
     *
     * @var \Hoa\Visitor\Element object
     */
    protected $_rootRule     = null;

    /**
     * Todo trace.
     *
     * @var \Hoa\Compiler\Visitor\Coverage array
     */
    protected $_todo         = null;

    /**
     * Trace.
     *
     * @var \Hoa\Compiler\Visitor\Coverage array
     */
    protected $_trace        = null;

    /**
     * Tests.
     *
     * @var \Hoa\Compiler\Visitor\Coverage array
     */
    protected $_tests        = null;

    /**
     * Covered rules matrix.
     *
     * @var \Hoa\Compiler\Visitor\Coverage array
     */
    protected $_coveredRules = null;

    /**
     * Current key of the iterator.
     *
     * @var \Hoa\Compiler\Visitor\Coverage array
     */
    protected $_key          = -1;

    /**
     * Current value of the iterator.
     *
     * @var \Hoa\Compiler\Visitor\Coverage array
     */
    protected $_current      = null;

    /**
     * Current ID of the covered rules.
     *
     * @var \Hoa\Compiler\Visitor\Coverage int
     */
    protected $_id           = null;



    /**
     * Initialize numeric-sampler and the size.
     *
     * @access  public
     * @param   \Hoa\Compiler\Llk         $grammar         Grammar.
     * @param   string                    $rootRuleName    Root rule name.
     * @param   \Hoa\Test\Sampler         $sampler         Numeric-sampler.
     * @param   \Hoa\Regex\Visitor\Visit  $tokenSampler    Token sampler.
     * @return  void
     */
    public function __construct ( \Hoa\Compiler\Llk        $grammar,
                                                           $rootRuleName = null,
                                  \Hoa\Test\Sampler        $sampler      = null,
                                  \Hoa\Regex\Visitor\Visit $tokenSampler = null ) {

        parent::__construct(
            $grammar,
            $rootRuleName,
            $sampler      ?: $sampler = new \Hoa\Test\Sampler\Random(),
            $tokenSampler ?: new \Hoa\Regex\Visitor\Isotropic($sampler)
        );
        $this->_rootRule = $this->flatNode(
            $this->getRuleAst($this->_rootRuleName)
        );

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

        if(!empty($this->_coveredRules)) {

            foreach($this->_coveredRules as $i => $node)
                foreach($node as $j => $sub)
                    $this->_coveredRules[$i][$j] = 0;

            $this->_current = null;
            $this->_key     = -1;

            return;
        }

        $this->_tests        = array();
        $this->_coveredRules = array();
        // Initialize rule coverage.
        $this->_rootRule->accept($this, $handle, $this->_rootRuleName);
        $data                = $this->_rootRule->getData();
        $this->_id           = $data['cov']['id'];

        return;
    }

    /**
     * Check if the current value is valid.
     *
     * @access  public
     * @return  bool
     */
    public function valid ( ) {

        if(!in_array(0, $this->_coveredRules[$this->_id]))
            return false;

        $this->_trace = array();
        $this->_todo  = array(new \Hoa\Compiler\Llk\Rule\Entry(
            $this->_rootRule,
            $this->_coveredRules
        ));
        $out          = $this->unfold();

        if(null !== $out) {

            $this->_current = $this->generate();
            ++$this->_key;
            $this->_tests[] = $this->_trace;
            // Reset rule coverage.
            foreach($this->_coveredRules as $key => $value)
                foreach($value as $k => $v)
                    if(-1 === $v)
                        $this->_coveredRules[$key][$k] = 0;

            return true;
        }

        return false;
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

            if($rule instanceof \Hoa\Compiler\Llk\Rule\Ekzit) {

                $this->_trace[] = $rule;
                $this->updateCoverage($rule);
            }
            else {

                $Rule = $rule->getRule();
                $out  = $Rule->accept($this, $handle, array(
                    $this->_trace,
                    $this->_todo,
                    $this->_coveredRules,
                    $this->_tests
                ));

                if(   null === $out
                   && true !== $this->backtrack())
                        return null;
                else {

                    $this->_trace        = $out['trace'];
                    $this->_todo         = $out['todo'];
                    $this->_coveredRules = $out['covered'];
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

            if($last instanceof \Hoa\Compiler\Llk\Rule\Entry) {

                $id    = $last->getRule()->getId();
                $found =    '#alternation'    == $id
                         || '#quantification' == $id;
            }
        } while(0 < count($this->_trace) && false === $found);

        if(false === $found)
            return false;

        $this->_coveredRules = $last->getData();
        $this->_todo         = $last->getTodo();
        $this->_todo[]       = new \Hoa\Compiler\Llk\Rule\Entry(
            $last->getRule(),
            $this->_coveredRules,
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
     * Extract test from trace.
     *
     * @access  protected
     * @param   array  $rules    Rules.
     * @param   array  $tests    Tests.
     * @param   array  $trace    Trace.
     * @return  array
     */
    protected function extract ( Array $rules, Array $tests, Array $trace ) {

        $out = array();

        foreach($rules as $rule) {

            $rule = $this->flatNode($rule);

            foreach($tests as $test) {

                $nbOpen = 0;

                for($i = 0, $length = count($test); $i < $length; ++$i) {

                    $test_i = $test[$i];

                    if(    $test_i instanceof \Hoa\Compiler\Llk\Rule\Entry
                       &&  $test_i->getRule() == $rule)
                        ++$nbOpen;

                    if(0 < $nbOpen)
                        $out[] = $test_i;

                    if(   $test_i instanceof \Hoa\Compiler\Llk\Rule\Ekzit
                       && $test_i->getRule() == $rule) {

                        --$nbOpen;

                        if(0 === $nbOpen)
                            return $out;
                    }
                }
            }
        }

        foreach($rules as $rule) {

            $rule     = $this->flatNode($rule);
            $out      = array();
            $nbClosed = 0;

            for($i = count($trace) - 1; $i >= 0; --$i) {

                $test_i = $trace[$i];

                if(   $test_i instanceof \Hoa\Compiler\Llk\Rule\Ekzit
                   && $test_i->getRule() == $rule)
                    ++$nbClosed;

                if(0 < $nbClosed)
                    $out[] = $test_i;

                if(   $test_i instanceof \Hoa\Compiler\Llk\Rule\Entry
                   && $test_i->getRule() == $rule) {

                    --$nbClosed;

                    if(0 === $nbClosed)
                        return array_reverse($out);
                }
            }
        }

        return null;
    }

    /**
     * Visit an element.
     *
     * @access  public
     * @param   \Hoa\Visitor\Element  $element    Element to visit.
     * @param   mixed                 &$handle    Handle (reference).
     * @param   mixed                 $eldnah     Handle (not reference).
     * @return  mixed
     * @throw   \Hoa\Compiler\Visitor\Exception
     */
    public function visit ( \Hoa\Visitor\Element $element,
                            &$handle = null, $eldnah = null ) {

        if(is_string($eldnah))
            return $this->_visitInit($element, $handle, $eldnah);

        $data = $element->getData();
        $uid  = $data['cov']['id'];
        list($trace, $todo, $covered, $tests) = $eldnah;

        switch($element->getId()) {

            case '#alternation':
                $uncovered  = array();
                $inProgress = array();
                $already    = array();

                foreach($covered[$uid] as $k => $c)
                    if(0 === $c)
                        $uncovered[]  = $k;
                    elseif(-1 === $c)
                        $inProgress[] = $k;
                    else
                        $already[]    = $k;

                if(empty($uncovered)) {

                    $trace[]  = new \Hoa\Compiler\Llk\Rule\Entry(
                        $element,
                        $covered,
                        $todo
                    );
                    $sequence = $this->extract(
                        $element->getChildren(),
                        $tests,
                        $trace
                    );

                    if(empty($sequence))
                        return null;

                    foreach($sequence as $seq) {

                        $trace[] = $seq;

                        if($seq instanceof \Hoa\Compiler\Llk\Rule\Ekzit)
                            $rand = $seq->getData();
                    }

                    $todo[] = new \Hoa\Compiler\Llk\Rule\Ekzit($element, $rand);
                }
                else {

                    $r       = $this->_sampler->getInteger(0, count($uncovered) - 1);
                    $rand    = $uncovered[$r];
                    $covered[$uid][$rand] = -1;
                    $trace[] = new \Hoa\Compiler\Llk\Rule\Entry(
                        $element,
                        $covered,
                        $todo
                    );
                    $todo[]  = new \Hoa\Compiler\Llk\Rule\Ekzit(
                        $element,
                        $rand
                    );
                    $todo[]  = new \Hoa\Compiler\Llk\Rule\Entry(
                        $this->flatNode($element->getChild($rand)),
                        $covered,
                        $todo
                    );
                }

                return array(
                    'trace'   => $trace,
                    'todo'    => $todo,
                    'covered' => $covered
                );
              break;

            case '#concatenation':
                $covered[$uid][0] = -1;
                $trace[] = new \Hoa\Compiler\Llk\Rule\Entry($element, 0);
                $todo[]  = new \Hoa\Compiler\Llk\Rule\Ekzit($element, 0);

                for($i = $element->getChildrenNumber() - 1; $i >= 0; --$i)
                    $todo[] = new \Hoa\Compiler\Llk\Rule\Entry(
                        $this->flatNode($element->getChild($i)),
                        0,
                        $todo
                    );

                return array(
                    'trace'   => $trace,
                    'todo'    => $todo,
                    'covered' => $covered
                );
              break;

            case '#quantification':
                $uncovered  = array();
                $inProgress = array();
                $already    = array();

                foreach($covered[$uid] as $c => $k)
                    if(0 === $k)
                        $uncovered[]  = $c;
                    elseif(-1 === $k)
                        $inProgress[] = $c;
                    else
                        $already[]    = $c;

                if(empty($uncovered)) {

                    if(empty($already))
                        $rand = $inProgress[$this->_sampler->getInteger(
                            0,
                            count($inProgress) - 1
                        )];
                    else
                        $rand = $already[$this->_sampler->getInteger(
                            0,
                            count($already) - 1
                        )];

                    $trace[]  = new \Hoa\Compiler\Llk\Rule\Entry(
                        $element,
                        $covered,
                        $todo
                    );
                    $sequence = $this->extract(
                        $element->getChildren(),
                        $tests,
                        $trace
                    );

                    if(empty($sequence))
                        return null;

                    for($i = 0; $i < $rand; ++$i)
                        foreach($sequence as $seq)
                            $trace[] = $seq;

                    $todo[] = new \Hoa\Compiler\Llk\Rule\Ekzit($element, $rand);
                }
                else {

                    $rand = $uncovered[$this->_sampler->getInteger(
                        0,
                        count($uncovered) - 1
                    )];
                    $covered[$uid][$rand] = -1;
                    $trace[] = new \Hoa\Compiler\Llk\Rule\Entry(
                        $element,
                        $covered,
                        $todo
                    );
                    $todo[]  = new \Hoa\Compiler\Llk\Rule\Ekzit(
                        $element,
                        $rand
                    );
                    $child   = $this->flatNode($element->getChild(0));

                    for($i = 0; $i < $rand; ++$i)
                        $todo[] = new \Hoa\Compiler\Llk\Rule\Entry(
                            $child,
                            $covered,
                            $todo
                        );
                }

                return array(
                    'trace'   => $trace,
                    'todo'    => $todo,
                    'covered' => $covered
                );
              break;

            case 'token':
                $trace[] = new \Hoa\Compiler\Llk\Rule\Entry($element, 0);
                $trace[] = $element;
                $todo[]  = new \Hoa\Compiler\Llk\Rule\Ekzit($element, 0);

                return array(
                    'trace'   => $trace,
                    'todo'    => $todo,
                    'covered' => $covered
                );
              break;
        }

        throw new Exception('Damned…', 42);
    }

    /**
     * Initialize before visiting.
     *
     * @access  protected
     * @param   \Hoa\Visitor\Element  $element    Element to visit.
     * @param   mixed                 &$handle    Handle (reference).
     * @param   mixed                 $eldnah     Handle (not reference).
     * @return  void
     */
    protected function _visitInit ( \Hoa\Visitor\Element $element,
                                    &$handle = null, $eldnah = null ) {

        $data              = &$element->getData();

        if(isset($data['cov']['id']))
            return;

        $data['cov']['id'] = $id  = $element->getHash();

        switch($element->getId()) {

            case '#quantification':
                $xy = $element->getChild(1)->getValueValue();
                $x  = 0;
                $y  = 0;

                switch($element->getChild(1)->getValueToken()) {

                    case 'zero_or_one':
                        $y = 1;
                      break;

                    case 'zero_or_more':
                        $y = 2;
                      break;

                    case 'one_or_more':
                        $x = 1;
                        $y = 2;
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
                        $y  = 2;
                      break;
                }

                $x1 = $x + 1;
                $y1 = $y - 1;

                if($x == $y)
                    $this->_coveredRules[$id][$x]  = 0;
                else {

                    $this->_coveredRules[$id][$x ] = 0;
                    $this->_coveredRules[$id][$x1] = 0;
                    $this->_coveredRules[$id][$y1] = 0;
                    $this->_coveredRules[$id][$y ] = 0;
                }

                $this->flatNode($element->getChild(0))->accept(
                    $this,
                    $handle,
                    $eldnah
                );
              break;

            case '#alternation':
                foreach($element->getChildren() as $i => $child) {

                    $this->_coveredRules[$id][$i] = 0;
                    $this->flatNode($child)->accept($this, $handle, $eldnah);
                }

                $this->_coveredRules[$id][0] = 0;
              break;

            case '#concatenation':
                $this->_coveredRules[$id][0] = 0;

                foreach($element->getChildren() as $child)
                    $this->flatNode($child)->accept($this, $handle, $eldnah);
              break;

            default:
                $this->_coveredRules[$id][0] = 0;
        }

        return;
    }

    /**
     * Update coverage of a rule.
     *
     * @access  protected
     * @param   \Hoa\Compiler\Llk\Rule\Ekzit  $rule    Rule.
     * @return  void
     */
    protected function updateCoverage ( \Hoa\Compiler\Llk\Rule\Ekzit $rule ) {

        $Rule  = $rule->getRule();
        $child = $rule->getData();
        $this->_updateCoverage(
            $Rule,
            $child
        );

        return;
    }

    /**
     * Real update coverage method.
     *
     * @access  protected
     * @param   \Hoa\Visitor\Element  $element    Element.
     * @param   int                   $child      Child number.
     * @return  void
     */
    protected function _updateCoverage ( \Hoa\Visitor\Element $element, $child ) {

        $data = $element->getData();
        $uid  = $data['cov']['id'];

        switch($element->getId()) {

            case '#alternation':
                $childData = $this->flatNode($element->getChild($child))
                                  ->getData();

                if(!in_array(0, $this->_coveredRules[$childData['cov']['id']]))
                    $this->_coveredRules[$uid][$child] = 1;
              break;

            case '#quantification':
                $childData = $this->flatNode($element->getChild(0))->getData();

                if(   0 === $child
                   || !in_array(0, $this->_coveredRules[$childData['cov']['id']]))
                    $this->_coveredRules[$uid][$child] = 1;
              break;

            case '#concatenation':
                $isCovered = true;

                for($i = $element->getChildrenNumber() - 1;
                    $i >= 0 && true === $isCovered;
                    --$i) {

                    $childData = $this->flatNode($element->getChild($i))
                                      ->getData();

                    if(in_array(0, $this->_coveredRules[$childData['cov']['id']]))
                        $isCovered = false;
                }

                if(true === $isCovered)
                    $this->_coveredRules[$uid][0] = 1;
              break;

            case 'token':
                $this->_coveredRules[$uid][0] = 1;
              break;
        }

        return;
    }

    /**
     * Flat node (to simplify computing).
     *
     * @access  protected
     * @param   \Hoa\Visitor\Element  $element    Element to flat.
     * @return  \Hoa\Visitor\Element
     * @throw   \Hoa\Compiler\Visitor\Exception
     */
    protected function flatNode ( \Hoa\Visitor\Element $element ) {

        switch($element->getId()) {

            case '#rule':
                return $this->flatNode($element->getChild(0));
              break;

            case '#skipped':
            case '#kept':
                return $element->getChild(0);
              break;

            case '#named':
                $rule = $this->getRule(
                    $element->getChild(0)->getValueValue()
                );

                if(null === $rule)
                    throw new Exception(
                        'Something has failed somewhere. Good luck. ' .
                        '(Clue: the rule %s does not exist).',
                        42, $element->getChild(0)->getValueValue());

                return $this->flatNode($rule['ast']);

            default:
                return $element;
        }
    }
}

}
