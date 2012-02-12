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
 * \Hoa\Compiler\Exception\UnexpectedToken
 */
-> import('Compiler.Exception.UnexpectedToken')

/**
 * \Hoa\Compiler\Llk\Lexer
 */
-> import('Compiler.Llk.Lexer')

/**
 * \Hoa\Compiler\Llk\Rule\Entry
 */
-> import('Compiler.Llk.Rule.Entry')

/**
 * \Hoa\Compiler\Llk\Rule\Ekzit
 */
-> import('Compiler.Llk.Rule.Ekzit')

/**
 * \Hoa\Compiler\Llk\TreeNode
 */
-> import('Compiler.Llk.TreeNode');

}

namespace Hoa\Compiler\Llk {

/**
 * Class \Hoa\Compiler\Llk\Parser.
 *
 * PP parser.
 *
 * @author     Frédéric Dadeau <frederic.dadeau@femto-st.fr>
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2012 Frédéric Dadeau, Ivan Enderlin.
 * @license    New BSD License
 */

class Parser {

    /**
     * List of skipped tokens.
     *
     * @var \Hoa\Compiler\Llk\Parser array
     */
    protected $_skip          = null;

    /**
     * Associative array (token name => token regex), to be defined in
     * precedence order.
     *
     * @var \Hoa\Compiler\Llk\Parser array
     */
    protected $_tokens        = null;

    /**
     * Rules, to be defined as associative array, name => Rule object.
     *
     * @var \Hoa\Compiler\Llk\Parser array
     */
    protected $_rules         = null;

    /**
     * Current state of the analyzer.
     *
     * @var \Hoa\Compiler\Llk\Parser int
     */
    protected $_currentState  = 0;

    /**
     * Error state of the analyzer (when an error is encountered).
     *
     * @var \Hoa\Compiler\Llk\Parser int
     */
    protected $_errorState    = 0;

    /**
     * Current token sequence being analyzed.
     *
     * @var \Hoa\Compiler\Llk\Parser array
     */
    protected $_tokenSequence = null;

    /**
     * Trace of activated rules.
     *
     * @var \Hoa\Compiler\Llk\Parser array
     */
    protected $_trace         = null;

    /**
     * Stack of todo list.
     *
     * @var \Hoa\Compiler\Llk\Parser array
     */
    protected $_todo          = null;

    /**
     * AST.
     *
     * @var \Hoa\Compiler\Llk\TreeNode object
     */
    protected $_tree          = null;



    /**
     * Construct the parser.
     *
     * @access  public
     * @param   array  $tokens    Tokens.
     * @param   array  $rules     Rules.
     * @return  void
     */
    public function __construct ( Array $tokens = array(),
                                  Array $rules  = array() ) {

        $this->_tokens = $tokens;
        $this->_rules  = $rules;

        return;
    }

    /**
     * Parse :-).
     *
     * @access  public
     * @param   string  $text    Text to parse.
     * @param   string  $rule    Root rule.
     * @param   bool    $tree    Whether build tree or not.
     * @return  mixed
     * @throw   \Hoa\Compiler\Exception\UnexpectedToken
     */
    public function parse ( $text, $rule = null, $tree = true ) {

        $lexer                = new Lexer();
        $this->_tokenSequence = $lexer->lexMe($text, $this->_tokens);
        $this->_currentState  = 0;
        $this->_errorState    = 0;
        $this->_trace         = array();
        $this->_todo          = array();

        if(false === array_key_exists($rule, $this->_rules))
            foreach($this->_rules as $rule => $_)
                if(!is_int($rule))
                    break;

        $closeRule   = new Rule\Ekzit($rule, 0, array());
        $openRule    = new Rule\Entry($rule, 0, array($closeRule));
        $this->_todo = array($closeRule, $openRule);

        do {

            $out = $this->unfold();

            if(   null !== $out
               && 'EOF' == $this->getCurrentToken())
                break;

            if(false === $this->backtrack()) {

                $token  = $this->_tokenSequence[$this->_errorState];
                $offset = $token['offset'];

                throw new \Hoa\Compiler\Exception\UnexpectedToken(
                    'Unexpected token "%s" (%s) at line 1 and column %d:' .
                    "\n" . '%s' . "\n" . str_repeat(' ', $offset) . '↑',
                    0, array($token['value'], $token['token'], $offset, $text),
                    1, $offset
                );
            }

            var_dump($b);

        } while(true);

        if(false === $tree)
            return true;

        $this->_tree = $this->_buildTree();

        return $this->_tree;
    }

    /**
     * Unfold trace.
     *
     * @access  protected
     * @return  mixed
     */
    protected function unfold ( ) {

        while(0 < count($this->_todo)) {

            $rule = array_pop($this->_todo);

            if($rule instanceof Rule\Ekzit)
                $this->_trace[] = $rule;
            else {

                $ruleName = $rule->getRule();
                $next     = $rule->getData();
                $zeRule   = $this->_rules[$ruleName];

                $out      = $this->_parse($zeRule, $next);

                if(false === $out)
                    if(null === $this->backtrack())
                        return null;
            }
        }

        return true;
    }

    /**
     * Parse current rule.
     *
     * @access  protected
     * @param   \Hoa\Compiler\Llk\Rule  $zeRule    Current rule.
     * @param   int                     $next      Next rule index.
     * @return  bool
     */
    protected function _parse ( Rule $zeRule, $next ) {

        if($zeRule instanceof Rule\Token) {

            if($zeRule->getName() != $this->getCurrentToken())
                return false;

            $zzeRule = clone $zeRule;
            $zzeRule->setValue($this->getCurrentToken('value'));
            array_pop($this->_todo);
            $this->_trace[]    = $zzeRule;
            $this->_errorState = ++$this->_currentState;

            return true;
        }
        elseif($zeRule instanceof Rule\Concatenation) {

            $this->_trace[] = new Rule\Entry($zeRule->getName(), 0, null);
            $content        = $zeRule->getContent();

            for($i = count($content) - 1; $i >= 0; --$i) {

                $nextRule      = $content[$i];
                $this->_todo[] = new Rule\Ekzit($nextRule, 0, null);
                $this->_todo[] = new Rule\Entry($nextRule, 0, null);
            }

            return true;
        }
        elseif($zeRule instanceof Rule\Choice) {

            $content = $zeRule->getContent();

            if($next >= count($content))
                return false;

            $this->_trace[] = new Rule\Entry(
                $zeRule->getName(),
                $next,
                $this->_todo
            );
            $nextRule       = $content[$next];
            $this->_todo[]  = new Rule\Ekzit($nextRule, 0, null);
            $this->_todo[]  = new Rule\Entry($nextRule, 0, null);

            return true;
        }
        elseif($zeRule instanceof Rule\Repetition) {

            $nextRule = $zeRule->getContent();

            if(0 === $next) {

                $name           = $zeRule->getName();
                $min            = $zeRule->getMin();
                $this->_trace[] = new Rule\Entry($name, $min, null);
                array_pop($this->_todo);
                $this->_todo[]  = new Rule\Ekzit($name, $min, $this->_todo);

                for($i = 0; $i < $min; ++$i) {

                    $this->_todo[] = new Rule\Ekzit($nextRule, 0, null);
                    $this->_todo[] = new Rule\Entry($nextRule, 0, null);
                }

                return true;
            }
            else {

                $max = $zeRule->getMax();

                if(-1 != $max && $next > $max)
                    return false;

                $this->_todo[] = new Rule\Ekzit(
                    $zeRule->getName(),
                    $next,
                    $this->_todo
                );
                $this->_todo[] = new Rule\Ekzit($nextRule, 0, null);
                $this->_todo[] = new Rule\Entry($nextRule, 0, null);

                return true;
            }
        }

        return false;
    }

    /**
     * Backtrack the trace.
     *
     * @access  protected
     * @return  bool
     */
    protected function backtrack ( ) {

        $found = false;

        do {

            $last = array_pop($this->_trace);

            if($last instanceof Rule\Entry) {

                $zeRule = $this->_rules[$last->getRule()];
                $found  = $zeRule instanceof Rule\Choice;
            }
            elseif($last instanceof Rule\Ekzit) {

                $zeRule = $this->_rules[$last->getRule()];
                $found  = $zeRule instanceof Rule\Repetition;
            }
            elseif($last instanceof Rule\Token)
                --$this->_currentState;

        } while(0 < count($this->_trace) && false === $found);

        if(false === $found)
            return false;

        $rule          = $last->getRule();
        $next          = $last->getData() + 1;
        $this->_todo   = $last->getTodo();
        $this->_todo[] = new Rule\Entry($rule, $next, null);

        return true;
    }

    /**
     * Build AST from trace.
     * Walk through the trace iteratively and recursively. During the walk, we
     * collect children. When we exit a rule which has an ID, we build a new
     * node and push (from left or from right according to priority) missing
     * children thanks to computed arity. That's the main principle. Have fun
     * reading :-).
     *
     * @access  protected
     * @param   int                         $i           Current trace.
     * @param   array                       $children    Collected children.
     * @param   int                         $pArity      Current rule arity.
     * @param   \Hoa\Compiler\Llk\TreeNode  $pTree       Current tree.
     * @return  \Hoa\Compiler\Llk\TreeNode
     */
    protected function _buildTree ( $i = 0, &$children = array(), &$pArity = 0,
                                    &$pTree = null ) {

        static $__i = 0;
        $max = count($this->_trace);

        while($i < $max) {

            $trace = $this->_trace[$i];

            if($trace instanceof Rule\Entry) {


                $ruleName  = $trace->getRule();
                $rule      = $this->_rules[$ruleName];
                $nextTrace = $this->_trace[$i + 1];

                // Optmization: skip empty trace sequence.
                if(    $nextTrace instanceof Rule\Ekzit
                   && $ruleName == $nextTrace->getRule()) {

                    $i += 2;
                    continue;
                }

                ++$pArity;

                $nArity = 0;
                $i      = $this->_buildTree($i + 1, $children, $nArity, $pTree);

                if(null !== $id = $rule->getNodeId()) {

                    if(null !== $pTree)
                        $children[] = $pTree;

                    $pTree = new TreeNode($id);

                    continue;
                }
                elseif(null === $pTree)
                    continue;

                for($j = $pTree->getChildrenNumber(); $j < $nArity; ++$j) {

                    $pop = array_pop($children);
                    $pTree->prependChild($pop);
                }
            }
            elseif($trace instanceof Rule\Ekzit)
                return $i + 1;
            else {

                if(false === $trace->isKept()) {

                    ++$i;
                    continue;
                }

                if(null !== $pTree) {

                    $children[] = $pTree;
                    $pTree      = null;
                }

                $children[] = new TreeNode('token', array(
                    'token' => $trace->getName(),
                    'value' => $trace->getValue()
                ));
                ++$i;
            }
        }

        foreach($children as $child)
            $pTree->appendChild($child);

        return $pTree;
    }

    /**
     * Get current token.
     *
     * @access  public
     * @param   string  $kind    Token informations.
     * @return  mixed
     */
    public function getCurrentToken ( $kind = 'token' ) {

        return $this->_tokenSequence[$this->_currentState][$kind];
    }

    /**
     * Get AST.
     *
     * @access  public
     * @return  \Hoa\Compiler\Llk\TreeNode
     */
    public function getTree ( ) {

        return $this->_tree;
    }

    /**
     * Get trace.
     *
     * @access  public
     * @return  array
     */
    public function getTrace ( ) {

        return $this->_trace;
    }
}

}
