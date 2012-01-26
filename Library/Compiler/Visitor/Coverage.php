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
 * Class \Hoa\Compiler\Visitor\Coverage.
 *
 * Generate a data of size n that can be matched by a LL(k) grammar.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2012 Ivan Enderlin.
 * @license    New BSD License
 */

class Coverage implements \Hoa\Visitor\Visit {

    /**
     * Numeric-sampler.
     *
     * @var \Hoa\Test\Sampler object
     */
    protected $_sampler  = null;

    /**
     * Given size: n.
     *
     * @var \Hoa\Compiler\Visitor\Coverage int
     */
    protected $_n        = 0;

    protected $_todo         = null;
    protected $_trace        = null;
    protected $_tests        = null;
    protected $_coveredRules = null;



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

        $element      = $this->flatNode($element);
        $this->_tests = array();
        $this->initializeRuleCoverage($element, $eldnah);
        $data = $element->getData();
        $id   = $data['cov']['id'];

        do {

            $this->_trace = array();
            $this->_todo  = array(
                new Trace\RuleEntry($element, $this->_coveredRules, 0)
            );
            $out          = $this->unfold();

            if(null !== $out) {

                $this->printTrace();
                $this->_tests[] = $this->_trace;
                $this->resetRuleCoverage();
            }
        } while(   null !== $out
                && in_array(0, $this->_coveredRules[$id]));
    }

    public function initializeRuleCoverage ( \Hoa\Visitor\Element $element,
                                             $eldnah ) {

        $element->accept($this, $handle, $eldnah);

        return;
    }

    public function resetRuleCoverage ( ) {

        foreach($this->_coveredRules as $key => $value)
            foreach($value as $k => $v)
                if(-1 === $v)
                    $this->_coveredRules[$key][$k] = 0;
    }

    public function unfold ( ) {

        while(0 < count($this->_todo)) {

            //var_dump($this->_todo);
            $rule = array_pop($this->_todo);

            if($rule instanceof Trace\RuleExit) {

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

                if(null == $out) {

                    $lastCP = $this->backtrack($this->_trace);

                    if(null == $lastCP)
                        return null;

                    $this->_trace        = $lastCP['trace'];
                    $this->_todo         = $lastCP['todo'];
                    $this->_coveredRules = $lastCP['covered'];
                }
                else {

                    $this->_trace        = $out['trace'];
                    $this->_todo         = $out['todo'];
                    $this->_coveredRules = $out['covered'];
                }
            }
        }

        return true;
    }

    public function backtrack ( $trace ) {

        $found = false;

        do {

            $last = array_pop($trace);

            if($last instanceof Trace\RuleEntry) {

                $id = $last->getRule()->getId();

                if(   '#alternation'    == $id
                   && '#quantification' == $id)
                    $found = true;
            }
        } while(0 < count($trace) && false === $found);

        if(false === $found)
            return null;

        $covered = $last->getData();
        $todo    = $last->getTodo();
        $todo[]  = new Trace\RuleEntry($last->getRule(), $covered, $todo);

        return array(
            'trace'   => $trace,
            'todo'    => $todo,
            'covered' => $covered
        );
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

    public function extract ( $rules, $tests, $trace ) {

        $out = array();

        foreach($rules as $rule) {

            $rule = $this->flatNode($rule);

            foreach($tests as $test) {

                $nbOpen = 0;

                for($i = 0, $length = count($test); $i < $length; ++$i) {

                    $test_i = $test[$i];

                    if(    $test_i instanceof Trace\RuleEntry
                       &&  $test_i->getRule() == $rule)
                        ++$nbOpen;

                    if(0 < $nbOpen)
                        $out[] = $test_i;

                    if(   $test_i instanceof Trace\RuleExit
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

                if(   $test_i instanceof Trace\RuleExit
                   && $test_i->getRule() == $rule)
                    ++$nbClosed;

                if(0 < $nbClosed)
                    $out[] = $test_i;

                if(   $test_i instanceof Trace\RuleEntry
                   && $test_i->getRule() == $rule) {

                    --$nbClosed;

                    if(0 === $nbClosed)
                        return array_reverse($out);
                }
            }
        }

        return null;
    }

    public function sample ( \Hoa\Visitor\Element $element ) {

        $token = $this->getMeta()->getToken(
            $element->getValueValue()
        );

        if(null === $token)
            throw new Exception(
                'Something has failed somewhere. Good luck. ' .
                '(Clue: the token %s does not exist).',
                0, $element->getValueValue());

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

                    $trace[]  = new Trace\RuleEntry($element, $covered, $todo);
                    $sequence = $this->extract(
                        $element->getChildren(),
                        $tests,
                        $trace
                    );

                    if(empty($sequence))
                        return null;

                    foreach($sequence as $seq) {

                        $trace[] = $seq;

                        if($seq instanceof Trace\RuleExit)
                            $rand = $seq->getData();
                    }

                    $todo[] = new Trace\RuleExit($element, $rand, null);
                }
                else {

                    $r       = $this->_sampler->getInteger(0, count($uncovered) - 1);
                    $rand    = $uncovered[$r];
                    $covered[$uid][$rand] = -1;
                    $trace[] = new Trace\RuleEntry($element, $covered, $todo);
                    $todo[]  = new Trace\RuleExit($element, $rand, null);
                    $todo[]  = new Trace\RuleEntry(
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
                $trace[]          = new Trace\RuleEntry($element, 0, null);
                $todo[]           = new Trace\RuleExit($element,  0, null);

                for($i = $element->getChildrenNumber() - 1; $i >= 0; --$i)
                    $todo[] = new Trace\RuleEntry(
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

                    $trace[]  = new Trace\RuleEntry($element, $covered, $todo);
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

                    $todo[] = new Trace\RuleExit($element, $rand, null);
                }
                else {

                    $rand = $uncovered[$this->_sampler->getInteger(
                        0,
                        count($uncovered) - 1
                    )];
                    $covered[$uid][$rand] = -1;
                    $trace[] = new Trace\RuleEntry($element, $covered, $todo);
                    $todo[]  = new Trace\RuleExit($element, $rand, null);
                    $child   = $this->flatNode($element->getChild(0));

                    for($i = 0; $i < $rand; ++$i)
                        $todo[] = new Trace\RuleEntry($child, $covered, $todo);
                }

                return array(
                    'trace'   => $trace,
                    'todo'    => $todo,
                    'covered' => $covered
                );
              break;

            case 'token':
                $trace[] = new Trace\RuleEntry($element, 0, null);
                $trace[] = $element;
                $todo[]  = new Trace\RuleExit($element, 0, null);

                return array(
                    'trace'   => $trace,
                    'todo'    => $todo,
                    'covered' => $covered
                );
              break;
        }

        throw new Exception('Damned…', 42);
    }

    public function _visitInit ( \Hoa\Visitor\Element $element,
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

    public function updateCoverage ( Trace\RuleExit $rule ) {

        $Rule  = $rule->getRule();
        $child = $rule->getData();
        $this->_updateCoverage(
            $Rule,
            $child
        );

        return;
    }

    public function _updateCoverage ( \Hoa\Visitor\Element $element, $child ) {

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

    public function flatNode ( \Hoa\Visitor\Element $element ) {

        switch($element->getId()) {

            case '#rule':
                return $this->flatNode($element->getChild(0));
              break;

            case '#skipped':
            case '#kept':
                return $element->getChild(0);
              break;

            case '#named':
                $rule = $this->getMeta()->getRule(
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
