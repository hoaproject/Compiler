<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2014, Ivan Enderlin. All rights reserved.
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
 * \Hoa\Compiler\Exception
 */
-> import('Compiler.Exception.~')

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
 * @copyright  Copyright © 2007-2014 Frédéric Dadeau, Ivan Enderlin.
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
    protected $_tokenSequence = array();

    /**
     * Trace of activated rules.
     *
     * @var \Hoa\Compiler\Llk\Parser array
     */
    protected $_trace         = array();

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
     * Current depth while building the trace.
     *
     * @var \Hoa\Compiler\Llk\Parser int
     */
    protected $_depth         = -1;

    /**
     * Setting to prevent the rule paths algorithm from running out of memory.
     *
     * # Usage #
     * This variable will prevent the algorithm from finding too many
     * possibilities. Please note that while working on a possibility it does
     * not suddenly abort. Instead it will finish up the current path
     * without traversing more nodes on that path. After this it will continue
     * to work on the remaining paths.
     *
     * It's important to note that you should not set this setting to the
     * limit if your PHP configuration. Because the algorithm need memory to
     * continue (see previous paragraph). While testing with the default PHP
     * setting `memory_limit = 128M;`, it turned out that 9MB worked and it
     * crashed at 10MB. Therfor it's advised to set the memory value at about
     * 7% of your max memory limit. Also note that this recommendation was
     * tested on one specific grammar. The actual value depends on your
     * grammar and with that the variables created in memory.
     *
     * In case you need to inspect a part of your grammar that was not included
     * in the output because of the memory limit, use `getRulePaths()` and seed
     * it with the proper start node for that part of your grammar.
     *
     * @var int in bytes
     */
    protected $pathFindingMemoryLimit;

    /**
     * Displays the tokens by their name in the getTokenPaths() output.
     */
    const TOKEN_NAME = 1;

    /**
     * Displays the tokens by their regular expression in the getTokenPaths()
     * output.
     */
    const TOKEN_REGEX = 2;

    /**
     * Displays the tokens by an example value based on the regular expression
     * in the getTokenPaths() output.
     */
    const TOKEN_VALUE = 3;


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
     * @param   string  $rule    The axiom, i.e. root rule.
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
            $rule = $this->getRootRule();

        $closeRule   = new Rule\Ekzit($rule, 0);
        $openRule    = new Rule\Entry($rule, 0, array($closeRule));
        $this->_todo = array($closeRule, $openRule);

        do {

            $out = $this->unfold();

            if(   null  !== $out
               && 'EOF'  == $this->getCurrentToken())
                break;

            if(false === $this->backtrack()) {

                $token  = $this->_tokenSequence[$this->_errorState];
                $offset = $token['offset'];
                $line   = 1;
                $column = 1;

                if(!empty($text)) {

                    if(0 === $offset)
                        $leftnl = 0;
                    else
                        $leftnl = strrpos($text, "\n", -(strlen($text) - $offset) - 1) ?: 0;

                    $rightnl = strpos($text, "\n", $offset);
                    $line    = substr_count($text, "\n", 0, $leftnl + 1) + 1;
                    $column  = $offset - $leftnl + (0 === $leftnl);

                    if(false !== $rightnl)
                        $text = trim(substr($text, $leftnl, $rightnl - $leftnl), "\n");
                }

                throw new \Hoa\Compiler\Exception\UnexpectedToken(
                    'Unexpected token "%s" (%s) at line %d and column %d:' .
                    "\n" . '%s' . "\n" . str_repeat(' ', $column - 1) . '↑',
                    0, array($token['value'], $token['token'], $line, $column, $text),
                    $line, $column
                );
            }

        } while(true);

        if(false === $tree)
            return true;

        $tree = $this->_buildTree();

        if(!($tree instanceof TreeNode))
            throw new \Hoa\Compiler\Exception(
                'Parsing error: cannot build AST, the trace is corrupted.', 0);

        return $this->_tree = $tree;
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

            if($rule instanceof Rule\Ekzit) {

                $rule->setDepth($this->_depth);
                $this->_trace[] = $rule;

                if(false === $rule->isTransitional())
                    --$this->_depth;
            }
            else {

                $ruleName = $rule->getRule();
                $next     = $rule->getData();
                $zeRule   = $this->_rules[$ruleName];
                $out      = $this->_parse($zeRule, $next);

                if(false === $out)
                    if(false === $this->backtrack())
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

            $name = $this->getCurrentToken();

            if($zeRule->getTokenName() !== $name)
                return false;

            $value = $this->getCurrentToken('value');

            if(0 <= $unification = $zeRule->getUnificationIndex())
                for($skip = 0, $i = count($this->_trace) - 1; $i >= 0; --$i) {

                    $trace = $this->_trace[$i];

                    if($trace instanceof Rule\Entry) {

                        if(false === $trace->isTransitional()) {

                            if($trace->getDepth() <= $this->_depth)
                                break;

                            --$skip;
                        }
                    }
                    elseif($trace instanceof Rule\Ekzit)
                        $skip += $trace->getDepth() > $this->_depth;

                    if(0 < $skip)
                        continue;

                    if(   $trace instanceof Rule\Token
                       && $unification === $trace->getUnificationIndex()
                       && $value       !=  $trace->getValue())
                        return false;
                }

            $namespace = $this->getCurrentToken('namespace');
            $zzeRule   = clone $zeRule;
            $zzeRule->setValue($value);
            $zzeRule->setNamespace($namespace);

            if(isset($this->_tokens[$namespace][$name]))
                $zzeRule->setRepresentation($this->_tokens[$namespace][$name]);
            else {

                foreach($this->_tokens[$namespace] as $_name => $regex) {

                    if(false === $pos = strpos($_name, ':'))
                        continue;

                    $_name = substr($_name, 0, $pos);

                    if($_name === $name)
                        break;
                }

                $zzeRule->setRepresentation($regex);
            }

            array_pop($this->_todo);
            $this->_trace[]    = $zzeRule;
            $this->_errorState = ++$this->_currentState;

            return true;
        }
        elseif($zeRule instanceof Rule\Concatenation) {

            if(false === $zeRule->isTransitional())
                ++$this->_depth;

            $this->_trace[] = new Rule\Entry(
                $zeRule->getName(),
                0,
                null,
                $this->_depth
            );
            $content        = $zeRule->getContent();

            for($i = count($content) - 1; $i >= 0; --$i) {

                $nextRule      = $content[$i];
                $this->_todo[] = new Rule\Ekzit($nextRule, 0);
                $this->_todo[] = new Rule\Entry($nextRule, 0);
            }

            return true;
        }
        elseif($zeRule instanceof Rule\Choice) {

            $content = $zeRule->getContent();

            if($next >= count($content))
                return false;

            if(false === $zeRule->isTransitional())
                ++$this->_depth;

            $this->_trace[] = new Rule\Entry(
                $zeRule->getName(),
                $next,
                $this->_todo,
                $this->_depth
            );
            $nextRule       = $content[$next];
            $this->_todo[]  = new Rule\Ekzit($nextRule, 0);
            $this->_todo[]  = new Rule\Entry($nextRule, 0);

            return true;
        }
        elseif($zeRule instanceof Rule\Repetition) {

            $nextRule = $zeRule->getContent();

            if(0 === $next) {

                $name = $zeRule->getName();
                $min  = $zeRule->getMin();

                if(false === $zeRule->isTransitional())
                    ++$this->_depth;

                $this->_trace[] = new Rule\Entry(
                    $name,
                    $min,
                    null,
                    $this->_depth
                );
                array_pop($this->_todo);
                $this->_todo[]  = new Rule\Ekzit(
                    $name,
                    $min,
                    $this->_todo
                );

                for($i = 0; $i < $min; ++$i) {

                    $this->_todo[] = new Rule\Ekzit($nextRule, 0);
                    $this->_todo[] = new Rule\Entry($nextRule, 0);
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
                $this->_todo[] = new Rule\Ekzit($nextRule, 0);
                $this->_todo[] = new Rule\Entry($nextRule, 0);

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
        $this->_depth  = $last->getDepth();
        $this->_todo   = $last->getTodo();
        $this->_todo[] = new Rule\Entry($rule, $next);

        return true;
    }

    /**
     * Build AST from trace.
     * Walk through the trace iteratively and recursively.
     *
     * @access  protected
     * @param   int      $i            Current trace index.
     * @param   array    &$children    Collected children.
     * @return  \Hoa\Compiler\Llk\TreeNode
     */
    protected function _buildTree ( $i = 0, &$children = array() ) {

        $max = count($this->_trace);

        while($i < $max) {

            $trace = $this->_trace[$i];

            if($trace instanceof Rule\Entry) {

                $ruleName  = $trace->getRule();
                $rule      = $this->_rules[$ruleName];
                $isRule    = false === $trace->isTransitional();
                $nextTrace = $this->_trace[$i + 1];
                $id        = $rule->getNodeId();

                // Optimization: skip empty trace sequence.
                if(   $nextTrace instanceof Rule\Ekzit
                   && $ruleName == $nextTrace->getRule()) {

                    $i += 2;
                    continue;
                }

                if(true === $isRule)
                    $children[] = $ruleName;

                if(null !== $id)
                    $children[] = array(
                        'id'      => $id,
                        'options' => $rule->getNodeOptions()
                    );

                $i = $this->_buildTree($i + 1, $children);

                if(false === $isRule)
                    continue;

                $handle   = array();
                $cId      = null;
                $cOptions = array();

                do {

                    $pop = array_pop($children);

                    if(true === is_object($pop))
                        $handle[] = $pop;
                    elseif(true === is_array($pop) && null === $cId) {

                        $cId      = $pop['id'];
                        $cOptions = $pop['options'];
                    }
                    elseif($ruleName == $pop)
                        break;

                } while(null !== $pop);

                if(null === $cId) {

                    $cId      = $rule->getDefaultId();
                    $cOptions = $rule->getDefaultOptions();
                }

                if(null === $cId) {

                    for($j = count($handle) - 1; $j >= 0; --$j)
                        $children[] = $handle[$j];

                    continue;
                }

                if(   true === in_array('M', $cOptions)
                   && true === $this->mergeTree($children, $handle, $cId))
                    continue;

                if(   true === in_array('m', $cOptions)
                   && true === $this->mergeTree($children, $handle, $cId, true))
                    continue;

                $cTree = new TreeNode($id ?: $cId);

                foreach($handle as $child) {

                    $child->setParent($cTree);
                    $cTree->prependChild($child);
                }

                $children[] = $cTree;
            }
            elseif($trace instanceof Rule\Ekzit)
                return $i + 1;
            else {

                if(false === $trace->isKept()) {

                    ++$i;

                    continue;
                }

                $child      = new TreeNode('token', array(
                    'token'     => $trace->getTokenName(),
                    'value'     => $trace->getValue(),
                    'namespace' => $trace->getNamespace(),
                ));
                $children[] = $child;
                ++$i;
            }
        }

        return $children[0];
    }

    /**
     * Try to merge directly children into an existing node.
     *
     * @access  protected
     * @param   array   &$children    Current children being gathering.
     * @param   array   &$handle      Children of the new node.
     * @param   string  $cId          Node ID.
     * @param   bool    $recursive    Whether we should merge recursively or
     *                                not.
     * @return  bool
     */
    protected function mergeTree ( &$children, &$handle, $cId,
                                   $recursive = false ) {

        end($children);
        $last = current($children);

        if(!is_object($last))
            return false;

        if($cId !== $last->getId())
            return false;

        if(true === $recursive) {

            foreach($handle as $child)
                $this->mergeTreeRecursive($last, $child);

            return true;
        }

        foreach($handle as $child) {

            $last->appendChild($child);
            $child->setParent($last);
        }

        return true;
    }

    /**
     * Merge recursively.
     * Please, see self::mergeTree() to know the context.
     *
     * @access  protected
     * @param   \Hoa\Compiler\Llk\TreeNode  $node       Node that receives.
     * @param   \Hoa\Compiler\Llk\TreeNode  $newNode    Node to merge.
     * @return  void
     */
    protected function mergeTreeRecursive ( TreeNode $node, TreeNode $newNode ) {

        $nNId = $newNode->getId();

        if('token' === $nNId) {

            $node->appendChild($newNode);
            $newNode->setParent($node);

            return;
        }

        $children = $node->getChildren();
        end($children);
        $last     = current($children);

        if($last->getId() !== $nNId) {

            $node->appendChild($newNode);
            $newNode->setParent($node);

            return;
        }

        foreach($newNode->getChildren() as $child)
            $this->mergeTreeRecursive($last, $child);

        return;
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

    /**
     * Get tokens.
     *
     * @access  public
     * @return  array
     */
    public function getTokens ( ) {

        return $this->_tokens;
    }

    /**
     * Get token sequence.
     *
     * @access  public
     * @return  array
     */
    public function getTokenSequence ( ) {

        return $this->_tokenSequence;
    }

    /**
     * Get rule by name.
     *
     * @access  public
     * @return  \Hoa\Compiler\Llk\Rule
     */
    public function getRule ( $name ) {

        if(!isset($this->_rules[$name]))
            return null;

        return $this->_rules[$name];
    }

    /**
     * Get rules.
     *
     * @access  public
     * @return  array
     */
    public function getRules ( ) {

        return $this->_rules;
    }

    /**
     * Get root rule.
     *
     * @access  public
     * @return  string
     */
    public function getRootRule ( ) {

        foreach($this->_rules as $rule => $_)
            if(!is_int($rule))
                break;

        return $rule;
    }

    /**
     * Seeding function to find the paths from the root rule.
     *
     * @return array[] Array of paths, made out of rule indexes or false for a circular reference.
     */
    public function getRulePathsFromRoot() {
        // Find out where to start
        foreach ($this->_rules as $k => $v) {
            if (!is_int($k)) {
                $rootRuleKey = $k;
                $rootRuleValue = $v;
                break;
            }
        }

        return $this->getRulePaths($rootRuleValue, [$rootRuleKey]);
    }

    /**
     * Merges generic format from child nodes while applying concatenation rules.
     *
     * @param array[] $children Data structure: Root -> Child -> Choice -> Path
     * @return array[] Data structure: Root -> Choice -> Path
     */
    private function getRulePathsForConcatenation($children) {
        if (count($children) === 1) {
            $choices = reset($children);
        } else {
            $choices = array_shift($children);
            while (count($children) > 0) {
                $second = array_shift($children);
                $product = [];
                foreach ($choices as $choice_i) {
                    foreach ($second as $choice_j) {
                        $product[] = array_merge($choice_i, $choice_j);
                    }
                }
                $choices = $product;
            }
        }

        return $choices;
    }

    /**
     * Merges child generic format from child nodes while applying choice rules.
     *
     * @param array[] $paths Data structure: Root -> Child -> Choice -> Path
     * @return array[] Data structure: Root -> Choice -> Path
     */
    private function getRulePathsForChoice($paths) {
        $processedPaths = [];
        foreach ($paths as $path) {
            $processedPaths = array_merge($processedPaths, $path);
        }
        return $processedPaths;
    }

    /**
     * @param int $bytes
     * @see \Hoa\Compiler\Llk\Parser::$pathFindingMemoryLimit
     */
    public function setPathFindingMemoryLimit($bytes) {
        $this->pathFindingMemoryLimit = (int) $bytes;
    }

    /**
     * Injects repetition into the current path.
     *
     * Uses true to signal parentheses open and the repetition node to signal
     * parentheses close.
     *
     * @param mixed[] $path
     * @param mixed $repetition the node index of the Rule\Repetition
     * @return mixed[] the modified path
     */
    private function addRepetition($path, $repetition) {
        array_splice($path, count($path) - 1, 0, [$repetition]);
        array_unshift($path, true);
        return $path;
    }

    /**
     * Recursive function to get the paths from the grammar.
     *
     * # Usage #
     * When not being called by `getRulePathsFromRoot()` but instead manually
     * invoked. Seed it with a node (rule) of your choosing. As second parameter
     * you should pass an array with one element that holds an int or string of
     * where your node (rule) can be found in the rules array (which you can get
     * with `getRules()`.
     *
     * @param Hoa\Compiler\Llk\Rule\Rule $node
     * @param mixed[] $currentPath Array of path indexes from $_rules.
     * @return array[] Array of paths, made out of rule indexes or false for a circular reference.
     * @throws \RuntimeException When unsupported node type is used
     */
    public function getRulePaths(Rule\Rule $node, $currentPath) {
        // 1. Normalize node content
        $content = $node->getContent();
        if (!is_array($content)) {
            $content = [$content];
        }

        // 1. Convert child node into paths
        if ($node instanceof Rule\Token) {
            $paths[] = [end($currentPath)];
        } elseif ($node instanceof Rule\Repetition) {
            // Assumed: Repetition can only have one child
            $child = $this->_rules[$content[0]];

            // Returning a format where the Repetition precedes the Token
            switch (substr(get_class($child), strlen(__NAMESPACE__)+1)) {
                case 'Rule\\Token':
                    $paths[] = [$this->addRepetition($content, end($currentPath))];
                    break;
                case 'Rule\\Concatenation':
                case 'Rule\\Choice':
                    if ($this->pathFindingMemoryLimit !== null AND
                      memory_get_usage() > $this->pathFindingMemoryLimit) {
                        $tempPaths = [[]];
                    } else {
                        $tempPaths = $this->getRulePaths($child, $currentPath);
                    }

                    foreach ($tempPaths as $k => $v) {
                        $tempPaths[$k] = $this->addRepetition($tempPaths[$k], end($currentPath));
                    }

                    $paths[] = $tempPaths;
                    break;
                default:
                    throw new \RuntimeException('Unexpected node of type ' . get_class($child));
            }
        } else {
            $paths = [];
            foreach ($content as $v) {
                // Circular reference detection, using false as signal
                // Array keys can only be int or string so this never conflicts
                if (($pos = array_search($v, $currentPath)) !== false) {
                    $pathCopy = $currentPath;
                    array_splice($pathCopy, $pos + 1, 0, false);
                    $paths[] = [$pathCopy];
                } else {
                    // get child node
                    $child = $this->_rules[$v];

                    if ($this->pathFindingMemoryLimit !== null AND
                      memory_get_usage() > $this->pathFindingMemoryLimit) {
                        $paths[] = [];
                    } else {
                        $paths[] = $this->getRulePaths($child, array_merge($currentPath, [$v]));
                    }
                }
            }
        }

        // 2. process return value
        if ($node instanceof Rule\Concatenation) {
            $processedPaths = $this->getRulePathsForConcatenation($paths);
        } elseif ($node instanceof Rule\Choice) {
            $processedPaths = $this->getRulePathsForChoice($paths);
        } elseif ($node instanceof Rule\Token) {
            $processedPaths = $paths;
        } elseif ($node instanceof Rule\Repetition) {
            $processedPaths = reset($paths);
        } else {
            throw new \RuntimeException('Unexpected node of type '.get_class($node));
        }

        // 3. return or add to final result
        return $processedPaths;
    }

    /**
     * Generates strings for displaying of $paths.
     *
     * Will print `#CR:4#` when a Circular Reference has been detected for rule
     * with index 4. This is kept short to prevent cluttering the output.
     *
     * @param array[] $paths
     * @param boolean $asTokenNames set to true to print token names instead of token values.
     * @return string[]
     * @uses Hoa\Compiler\Llk\Parser::getQuantifierSymbol()
     * @uses Hoa\Compiler\Llk\Parser::resolveNameSpace()
     */
    public function getTokenPaths(array $paths, $displayType = self::TOKEN_NAME, $repetitionAsNode = false) {
        $foundTokenPaths = [];

        // Preprocess all tokens so they can be easily found
        foreach ($this->_tokens as $namespace => $tokens) {
            foreach ($tokens as $nameNamespace => $regex) {
                unset($token);
                $tokenInfo = preg_split('/:/', $nameNamespace);
                $token['regex'] = $regex;
                if (isset($tokenInfo[1])) {
                    $token['nextNamespace'] = $tokenInfo[1];
                }
                $processedTokens[$namespace][$tokenInfo[0]] = $token;
            }
        }

        foreach ($paths as $path) {
            $tempTokenPath = [];

            /*
             * Keeps track of previous tokens before a Rule\Token
             * To assist in finding a possible Repetition
             */
            $previousTokens = [];

            /*
             * Reset the namespace to default before each iteration
             * At the moment the algorithm has trouble with starting from a rule
             * that is not in the default namespace, because it doesn't have a
             * backtracking mechanism to find out which namespace applies.
             */
            $namespace = 'default';

            /*
             * Signal a parentheses needs to be opened before the next node.
             */
            $openParentheses = false;

            /*
             * Amount of parentheses opened at a time.
             */
            $ParenthesesOpen = 0;

            foreach ($path as $k => $index) {
                if ($index === false) {
                    $tempTokenPath[] = '#CR:' . $previous . '#';
                } elseif ($index === true) {
                    // Look ahead by one node, skip parenthesis when there is
                    // just a single node in it
                    if (! ($this->_rules[$path[$k+1]] instanceof Rule\Repetition)) {
                        if ($repetitionAsNode) {
                            $tempTokenPath[] = '(';
                            $ParenthesesOpen++;
                        } else {
                            $openParentheses = true;
                            $ParenthesesOpen++;
                        }
                    }
                } else {
                    $rule = $this->_rules[$index];

                    if ($rule instanceof Rule\Token) {
                        $tokenName = $rule->getTokenName();
                        $token = $processedTokens[$namespace][$tokenName];

                        if (isset($token['nextNamespace'])) {
                            $namespace = $token['nextNamespace'];
                        }

                        $quantifier = '';
                        foreach ($previousTokens as $prev) {
                            $rule = $this->_rules[$prev];
                            if ($rule instanceof Rule\Repetition) {
                                $quantifier = $this->getQuantifierSymbol($rule);
                            }
                        }

                        $tmpPath = '';
                        if (! $repetitionAsNode AND $openParentheses) {
                            $tmpPath = '( ';
                            $openParentheses = false;
                        }

                        if ($displayType === self::TOKEN_NAME) {
                            $tmpPath .= $tokenName;
                        } elseif ($displayType === self::TOKEN_REGEX) {
                            $tmpPath .= $token['regex'];
                        } elseif ($displayType === self::TOKEN_VALUE) {
                            throw new \Exception('not yet implemented');
                        } else {
                            throw new \RuntimeException('An unsupported option was passed for $displayType.');
                        }

                        if ($repetitionAsNode) {
                            $tempTokenPath[] = $tmpPath;
                            if ($quantifier !== '') {
                                if ($ParenthesesOpen > 0) {
                                    $tempTokenPath[] = ')';
                                    $ParenthesesOpen--;
                                }
                                $tempTokenPath[] = $quantifier;
                            }
                        } else {
                            if ($ParenthesesOpen > 0 AND $quantifier !== '') {
                                $tmpPath .= ' )';
                                $ParenthesesOpen--;
                            }
                            if ($quantifier !== '') {
                                $tmpPath .= ' ' . $quantifier;
                            }
                            $tempTokenPath[] = $tmpPath;
                        }

                        $previousTokens = [];
                    } else {
                        // We want to reverse traverse this later on, so not using array_push
                        array_unshift($previousTokens, $index);
                    }
                }

                $previous = $index;
            }

            $foundTokenPaths[] = $tempTokenPath;
        }

        return $foundTokenPaths;
    }

    /**
     * Helper function to format the quantifier symbol.
     *
     * @param \Hoa\Compiler\Llk\Rule\Repetition $rule
     * @return string
     */
    private function getQuantifierSymbol(Rule\Repetition $rule) {
        $min = $rule->getMin();
        $max = $rule->getMax();

        if ($min === 1 and $max === -1) {
            return '+';
        } elseif ($min === 0 and $max === -1) {
            return '*';
        } elseif ($min === 0 and $max === 1) {
            return '?';
        } else {
            return '{' . $min . ', ' . $max . '}';
        }
    }
}

}
