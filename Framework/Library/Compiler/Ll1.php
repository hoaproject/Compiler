<?php

/**
 * Hoa Framework
 *
 *
 * @license
 *
 * GNU General Public License
 *
 * This file is part of HOA Open Accessibility.
 * Copyright (c) 2007, 2010 Ivan ENDERLIN. All rights reserved.
 *
 * HOA Open Accessibility is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * HOA Open Accessibility is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with HOA Open Accessibility; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 *
 * @category    Framework
 * @package     Hoa_Compiler_Ll1
 *
 */

/**
 * Hoa_Compiler_Ll1_Exception
 */
import('Compiler.Exception');

/**
 * Hoa_Compiler_Exception_FinalStateHasNotBeenReached
 */
import('Compiler.Exception.FinalStateHasNotBeenReached');

/**
 * Hoa_Compiler_Exception_IllegalToken
 */
import('Compiler.Exception.IllegalToken');

/**
 * Define the __ constant, so useful in compiler :-).
 */
define('GO', 'GO');
define('__', '__');

/**
 * Class Hoa_Compiler_Ll1.
 *
 * Provide an abstract LL(1) compiler, based on sub-automata and stacks.
 *
 * @author      Ivan ENDERLIN <ivan.enderlin@hoa-project.net>
 * @copyright   Copyright (c) 2007, 2010 Ivan ENDERLIN.
 * @license     http://gnu.org/licenses/gpl.txt GNU GPL
 * @since       PHP 5
 * @version     0.1
 * @package     Hoa_Compiler_Ll1
 */

abstract class Hoa_Compiler_Ll1 {

    /**
     * Initial line.
     * If we try to compile a code inside another code, the initial line would
     * not probably be 0.
     *
     * @var Hoa_Compiler_Ll1 int
     */
    protected $_initialLine         = 0;

    /**
     * Tokens to skip (will be totally skip, no way to get it).
     * Tokens' rules could be apply here (i.e. normal and special tokens are
     * understood).
     * Example:
     *     array(
     *         '#\s+',           // white spaces
     *         '#//.*',          // inline comment
     *         '#/\*(.|\n)*\*\/' // block comment
     *     )
     *
     * @var Hoa_Compiler_Ll1 array
     */
    protected $_skip                = array();

    /**
     * Tokens.
     * A token should be:
     *     * simple, it means just a single char, e.g. ':';
     *     * special, strings and/or a regular expressions, and must begin with
     *       a sharp (#), e.g. '#foobar', '#[a-zA-Z]' or '#foo?bar'.
     * Note: if we want the token #, we should write '##'.
     * PCRE expressions are fully-supported.
     * We got an array of arrays because of sub-automata, one sub-array per
     * sub-automaton.
     * Example:
     *     array(
     *         array(
     *             '{'  // open brack
     *         ),
     *         array(
     *             '"',            // quote
     *             ':',            // semi-colon
     *             ',',            // comma
     *             '{',            // open bracket
     *             '}'             // close bracket
     *         ),
     *         array(
     *             '#[a-z_\ \n]+", // id/string
     *             '"'             // quote
     *         )
     *     )
     *
     * @var Hoa_Compiler_Ll1 array
     */
    protected $_tokens              = array();

    /**
     * States.
     * We got an array of arrays because of sub-automata, one sub-array per
     * sub-automaton.
     * Example:
     *     array(
     *         array(
     *              __ , // error
     *             'GO', // start
     *             'OK'  // terminal
     *         ),
     *         array(
     *              __ , // error
     *             'GO', // start
     *             'KE', // key
     *             'CO', // colon
     *             'VA', // value
     *             'BL', // block
     *             'OK'  // terminal
     *         ),
     *         array(
     *              __ , // error
     *             'GO', // start
     *             'ST', // string
     *             'OK'  // terminal
     *         )
     *     )
     *
     * Note: the constant GO or the string 'GO' must be used to represent the
     *       initial state.
     * Note: the constant __ or the string '__' must be used to represent the
     *       null/unrecognized/error state.
     *
     * @var Hoa_Compiler_Ll1 array
     */
    protected $_states              = array();

    /**
     * Terminal states (defined in the states set).
     * We got an array of arrays because of sub-automata, one sub-array per
     * sub-automaton.
     * Example:
     *     array(
     *         array('OK'),
     *         array('OK'),
     *         array('OK')
     *     )
     *
     * @var Hoa_Compiler_Ll1 array
     */
    protected $_terminal            = array();

    /**
     * Transitions table.
     * It's actually a matrix, such as: TT(TOKENS × STATES).
     * We got an array of arrays because of sub-automata, one sub-array per
     * sub-automaton.
     * Example:
     *     array(
     *         array(
     *                        {
     *             __  array( __ ),
     *             GO  array('OK'),
     *             OK  array( __ ),
     *         ),
     *         array(
     *                        "     :     ,     {     }
     *             __  array( __ ,  __ ,  __ ,  __ ,  __ ),
     *             GO  array('KE',  __ ,  __ ,  __ , 'OK'),
     *             KE  array( __ , 'CO',  __ ,  __ ,  __ ),
     *             CO  array('VA',  __ ,  __ , 'BL',  __ ),
     *             VA  array( __ ,  __ , 'GO',  __ , 'OK'),
     *             BL  array( __ ,  __ , 'GO',  __ , 'OK'),
     *             OK  array( __ ,  __ ,  __ ,  __ ,  __ )
     *         ),
     *         array(
     *                        id    "
     *             __  array( __ ,  __ ),
     *             GO  array('ST', 'OK'),
     *             ST  array( __ , 'OK'),
     *             OK  array( __ ,  __ )
     *         )
     *     )
     *
     * Note: tokens and states should be declared in the strict same order as
     *       defined previously.
     *
     * @var Hoa_Compiler_Ll1 array
     */
    protected $_transitions         = array();

    /**
     * Actions table.
     * It's actually a matrix, such as: AT(TOKENS × STATES).
     * We got an array of arrays because of sub-automata, one sub-array per
     * sub-automaton.
     * Example:
     *     array(
     *         array(
     *                        {
     *             __  array( 0),
     *             GO  array( 0),
     *             OK  array( 2),
     *         ),
     *         array(
     *                        "   :    ,    {    }
     *             __  array( 0,  0 ,  0 ,  0 ,  0 ),
     *             GO  array( 0,  0 ,  0 ,  0 , 'd'),
     *             KE  array( 3, 'k',  0 ,  0 ,  0 ),
     *             CO  array( 0,  0 ,  0 , 'u',  0 ),
     *             VA  array( 3,  0 , 'v',  0 , 'x'),
     *             BL  array( 0,  0 ,  0 ,  2 , 'd'),
     *             OK  array( 0,  0 ,  0 ,  0 ,  0 )
     *         ),
     *         array(
     *                       id  "
     *             __  array( 0, 0),
     *             GO  array(-1, 0),
     *             ST  array( 0, 0),
     *             OK  array( 0, 0)
     *         )
     *     )
     *
     * AT is filled with integer or char n.
     * If n is a char, it means an action.
     * If n < 0, it means a special action.
     * If n = 0, it means not action.
     * If n > 0, it means a link to a sub-automata (sub-automata index + 1).
     *
     * When we write our consume() method, it's just a simple switch receiving
     * an action. It receives only character. It's like a “goto” in our
     * compiler, and allows us to execute code when skiming through the graph.
     *
     * Negative/special actions are used to auto-fill or empty buffers.
     * E.g: -1 will fill the buffer 0, -2 will empty the buffer 0,
     *      -3 will fill the buffer 1, -4 will empty the buffer 1,
     *      -5 will fill the buffer 2, -6 will empty the buffer 2 etc.
     * A formula appears:
     *      y = |x|
     *      fill  buffer (x - 2) / 2 if x & 1 = 1
     *      empty buffer (x - 1) / 2 if x & 1 = 0
     *
     * Positive/link actions are used to make an epsilon-transition (or a link)
     * between two sub-automata.
     * Sub-automata are indexed from 0, but our links must be index + 1. Example
     * given: the sub-automata 0 in our example has a link to the sub-automata 1
     * through OK[{] = 2. Take attention to this :-).
     * And another thing which must be carefully studying is the place of the
     * link. For example, with our sub-automata 1 (the big one), we have an
     * epsilon-transition to the sub-automata 2 through VA["] = 3. It means:
     * when we arrived in the state VA from the token ", we go in the
     * sub-automata 3 (the 2nd one actually). And when the linked sub-automata
     * has finished, we are back in our state and continue our parsing. Take
     * care of this :-).
     *
     * Finally, it is possible to combine positive and char action, separated by
     a comma. Thus: 7,f is equivalent to make an epsilon-transition to the
     * automata 7, then consume the action f.
     *
     * @var Hoa_Compiler_Ll1 array
     */
    protected $_actions             = array();

    /**
     * Names of automata.
     */
    protected $_names               = array();

    /**
     * Recursive stack.
     *
     * @var Hoa_Compiler_Ll1 array
     */
    private   $_stack               = array();

    /**
     * Buffers.
     *
     * @var Hoa_Compiler_Ll1 array
     */
    protected $buffers              = array();

    /**
     * Current token's line.
     *
     * @var Hoa_Compiler_Ll1 int
     */
    protected $line                 = 0;

    /**
     * Current token's column.
     *
     * @var Hoa_Compiler_Ll1 int
     */
    protected $column               = 0;

    /**
     * Cache compiling result.
     *
     * @var Hoa_Compiler_Ll1 array
     */
    protected static $_cache        = array();

    /**
     * Whether cache is enabled or not.
     *
     * @var Hoa_Compiler_Ll1 bool
     */
    protected static $_cacheEnabled = true;



    /**
     * Singleton, and set parameters.
     *
     * @access  public
     * @param   array   $skip           Skip.
     * @param   array   $tokens         Tokens.
     * @param   array   $states         States.
     * @param   array   $terminal       Terminal states.
     * @param   array   $transitions    Transitions table.
     * @param   array   $actions        Actions table.
     * @param   array   $names          Names of automata.
     * @return  void
     */
    public function __construct ( Array $skip,
                                  Array $tokens,
                                  Array $states,
                                  Array $terminal,
                                  Array $transitions,
                                  Array $actions,
                                  Array $names = array() ) {

        $this->setSkip($skip);
        $this->setTokens($tokens);
        $this->setStates($states);
        $this->setTerminal($terminal);
        $this->setTransitions($transitions);
        $this->setActions($actions);
        $this->setNames($names);

        return;
    }

    /**
     * Compile a source code.
     *
     * @access  public
     * @param   string  $in    Source code.
     * @return  void
     */
    public function compile ( $in ) {

        $cacheId = md5($in);

        if(   true === self::$_cacheEnabled
           && true === array_key_exists($cacheId, self::$_cache))
            return self::$_cache[$cacheId];

        $d             = 0;
        $c             = 0; // current automata.
        $_skip         = array_flip($this->_skip);
        $_tokens       = array_flip($this->_tokens[$c]);
        $_states       = array_flip($this->_states[$c]);
        $_actions      = array($c => 0);

        $nextChar      = null;
        $nextToken     = 0;
        $nextState     = $_states['GO'];
        $nextAction    = $_states['GO'];

        $this->line    = $this->getInitialLine();
        $this->column  = 0;

        $this->buffers = array();

        $line          = $this->line;
        $column        = $this->column;

        $this->pre($in);

        for($i = 0, $max = strlen($in); $i <= $max; $i++) {

            //echo "\n---\n\n";

            // End of parsing (not automata).
            if($i == $max) {

                while(   $c > 0
                      && in_array($this->_states[$c][$nextState], $this->_terminal[$c]))
                    list($c, $nextState, ) = array_pop($this->_stack);

                if(   in_array($this->_states[$c][$nextState], $this->_terminal[$c])
                   && 0    === $c
                   && true === $this->end()) {

                    //echo '*********** END REACHED **********' . "\n";

                    if(true === self::$_cacheEnabled)
                        self::$_cache[$cacheId] = $this->getResult();

                    return true;
                }

                throw new Hoa_Compiler_Exception_FinalStateHasNotBeenReached(
                    'End of code has been reached but not correctly; ' .
                    'maybe your program is not complete?',
                    0
                );
            }

            $nextChar = $in[$i];

            // Skip.
            if(isset($_skip[$nextChar])) {

                if($nextChar == "\n") {

                    $line++;
                    $column = 0;
                }
                else
                    $column++;

                continue;
            }
            else {

                $continue = false;
                $handle   = substr($in, $i);

                foreach($_skip as $sk => $e) {

                    if($sk[0] != '#')
                        continue;

                    $sk = str_replace('#', '\#', substr($sk, 1));

                    if(0 != preg_match('#^(' . $sk . ')#', $handle, $match)) {

                        $strlen = strlen($match[1]);

                        if($strlen > 0) {

                            if(false !== $offset = strrpos($match[1], "\n"))
                                $column  = $strlen - $offset - 1;
                            else
                                $column += $strlen;

                            $line     += substr_count($match[1], "\n");
                            $i        += $strlen - 1;
                            $continue  = true;

                            break;
                        }
                    }
                }

                if(true === $continue)
                    continue;
            }

            // Epsilon-transition.
            $epsilon = false;
            while(    array_key_exists($nextToken, $this->_actions[$c][$nextState])
                  && (
                      (
                          is_array($this->_actions[$c][$nextState][$nextToken])
                       && 0 < $foo = $this->_actions[$c][$nextState][$nextToken][0]
                      )
                   || (
                          is_int($this->_actions[$c][$nextState][$nextToken])
                       && 0 < $foo = $this->_actions[$c][$nextState][$nextToken]
                      )
                     )
                 ) {

                $epsilon = true;

                if($_actions[$c] == 0) {

                    //echo '*** Change automata (up to ' . ($foo - 1) . ')' . "\n";

                    $this->_stack[$d] = array($c, $nextState, $nextToken);
                    end($this->_stack);

                    $c            = $foo - 1;
                    $_tokens      = array_flip($this->_tokens[$c]);
                    $_states      = array_flip($this->_states[$c]);

                    $nextState    = $_states['GO'];
                    $nextAction   = $_states['GO'];
                    $nextToken    = 0;

                    $_actions[$c] = 0;

                    $d++;
                }
                elseif($_actions[$c] == 2) {

                    $_actions[$c] = 0;
                    break;
                }
            }

            if(true === $epsilon) {

                $epsilon   = false;
                $nextToken = false;
            }

            // Token.
            if(isset($_tokens[$nextChar])) {

                $token      = $nextChar;
                $nextToken  = $_tokens[$token];

                if($nextChar == "\n") {

                    $line++;
                    $column = 0;
                }
                else
                    $column++;
            }
            else {

                $nextToken = false;
                $handle    = substr($in, $i);

                foreach($_tokens as $token => $e) {

                    if($token[0] != '#')
                        continue;

                    $ntoken = str_replace('#', '\#', substr($token, 1));

                    if(0 != preg_match('#^(' . $ntoken . ')#', $handle, $match)) {

                        $strlen = strlen($match[1]);

                        if($strlen > 0) {

                            if(false !== $offset = strrpos($match[1], "\n"))
                                $column  = $strlen - $offset - 1;
                            else
                                $column += $strlen;

                            $nextChar   = $match[1];
                            $nextToken  = $e;
                            $i         += $strlen - 1;
                            $line      += substr_count($match[1], "\n");

                            break;
                        }
                    }
                }
            }

            /*
            echo '>>> Automata   ' . $c . "\n" .
                 '>>> Next state ' . $nextState . "\n" .
                 '>>> Token      ' . $token . "\n" .
                 '>>> Next char  ' . $nextChar . "\n";
            */

            // Got it!
            if(false !== $nextToken) {

                if(is_array($this->_actions[$c][$nextState][$nextToken]))
                    $nextAction = $this->_actions[$c][$nextState][$nextToken][1];
                else
                    $nextAction = $this->_actions[$c][$nextState][$nextToken];
                $nextState      = $_states[$this->_transitions[$c][$nextState][$nextToken]];
            }

            // Oh :-(.
            if(false === $nextToken || $nextState === $_states['__']) {

                $pop = array_pop($this->_stack);
                $d--;

                // Go back to a parent automata.
                if(   (in_array($this->_states[$c][$nextState], $this->_terminal[$c])
                   &&  null !== $pop)
                   || ($nextState === $_states['__']
                   &&  null !== $pop)) {

                    //echo '!!! Change automata (down)' . "\n";

                    list($c, $nextState, $nextToken) = $pop;

                    $_actions[$c]  = 2;

                    $i            -= strlen($nextChar);
                    $_tokens       = array_flip($this->_tokens[$c]);
                    $_states       = array_flip($this->_states[$c]);

                    /*
                    echo '!!! Automata   ' . $c . "\n" .
                         '!!! Next state ' . $nextState . "\n";
                    */

                    continue;
                }

                $error = explode("\n", $in);
                $error = $error[$this->line];

                throw new Hoa_Compiler_Exception_IllegalToken(
                    'Illegal token at line ' . ($this->line + 1) . ' and column ' .
                    ($this->column + 1) . "\n" . $error . "\n" .
                    str_repeat(' ', $this->column) . '↑',
                    0, array(), $this->line + 1, $this->column + 1
                );
            }

            $this->line   = $line;
            $this->column = $column;

            //echo '<<< Next state ' . $nextState . "\n";

            $this->buffers[-1] = $nextChar;

            // Special actions.
            if($nextAction < 0) {

                $buffer = abs($nextAction);

                if(($buffer & 1) == 0)
                    $this->buffers[($buffer - 2) / 2] = null;
                else {

                    $buffer = ($buffer - 1) / 2;

                    if(!(isset($this->buffers[$buffer])))
                        $this->buffers[$buffer] = null;

                    $this->buffers[$buffer] .= $nextChar;
                }

                continue;
            }

            if(0 !== $nextAction)
                $this->consume($nextAction);
        }

        return;
    }

    /**
     * Consume actions.
     * Please, see the actions table definition to learn more.
     *
     * @access  protected
     * @param   int  $action    Action.
     * @return  void
     */
    abstract protected function consume ( $action );

    /**
     * Compute source code before compiling it.
     *
     * @access  protected
     * @param   string  &$in    Source code.
     * @return  void
     */
    protected function pre ( &$in ) {

        return;
    }

    /**
     * Verify compiler state when ending the source code.
     *
     * @access  protected
     * @return  bool
     */
    protected function end ( ) {

        return true;
    }

    /**
     * Get the result of the compiling.
     *
     * @access  public
     * @return  mixed
     */
    abstract public function getResult ( );

    /**
     * Set initial line.
     *
     * @access  public
     * @param   int     $line    Initial line.
     * @return  int
     */
    public function setInitialLine ( $line ) {

        $old                = $this->_initialLine;
        $this->_initialLine = $line;

        return $old;
    }

    /**
     * Set tokens to skip.
     *
     * @access  public
     * @param   array   $skip    Skip.
     * @return  array
     */
    public function setSkip ( Array $skip ) {

        $old         = $this->_skip;
        $this->_skip = $skip;

        return $old;
    }


    /**
     * Set tokens.
     *
     * @access  public
     * @param   array   $tokens    Tokens.
     * @return  array
     */
    public function setTokens ( Array $tokens ) {

        $old           = $this->_tokens;
        $this->_tokens = $tokens;

        return $old;
    }

    /**
     * Set states.
     *
     * @access  public
     * @param   array   $states    States.
     * @return  array
     */
    public function setStates ( Array $states ) {

        $old           = $this->_states;
        $this->_states = $states;

        return $old;
    }

    /**
     * Set terminal states.
     *
     * @access  public
     * @param   array   $terminal    Terminal states.
     * @return  array
     */
    public function setTerminal ( Array $terminal ) {

        $old             = $this->_terminal;
        $this->_terminal = $terminal;

        return $old;
    }

    /**
     * Set transitions table.
     *
     * @access  public
     * @param   array   $transitions    Transitions table.
     * @return  array
     */
    public function setTransitions ( Array $transitions ) {

        $old                = $this->_transitions;
        $this->_transitions = $transitions;

        return $old;
    }

    /**
     * Set actions table.
     *
     * @access  public
     * @param   array   $actions    Actions table.
     * @return  array
     */
    public function setActions ( Array $actions ) {

        foreach($actions as $e => $automata)
            foreach($automata as $i => $state)
                foreach($state as $j => $token)
                    if(0 != preg_match('#^(\d+),(.*)$#', $token, $matches))
                        $actions[$e][$i][$j] = array((int) $matches[1], $matches[2]);

        $old            = $this->_actions;
        $this->_actions = $actions;

        return $old;
    }

    /**
     * Set names of automata.
     *
     * @access  public
     * @param   array   $names    Names of automata.
     * @return  array
     */
    public function setNames ( Array $names ) {

        $old          = $this->_names;
        $this->_names = $names;

        return $old;
    }

    /**
     * Get initial line.
     *
     * @access  public
     * @return  int
     */
    public function getInitialLine ( ) {

        return $this->_initialLine;
    }

    /**
     * Get skip tokens.
     *
     * @access  public
     * @return  array
     */
    public function getSkip ( ) {

        return $this->_skip;
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
     * Get states.
     *
     * @access  public
     * @return  array
     */
    public function getStates ( ) {

        return $this->_states;
    }

    /**
     * Get terminal states.
     *
     * @access  public
     * @return  array
     */
    public function getTerminal ( ) {

        return $this->_terminal;
    }

    /**
     * Get transitions table.
     *
     * @access  public
     * @return  array
     */
    public function getTransitions ( ) {

        return $this->_transitions;
    }

    /**
     * Get actions table.
     *
     * @access  public
     * @return  array
     */
    public function getActions ( ) {

        return $this->_actions;
    }

    /**
     * Get names of automata.
     *
     * @access  public
     * @return  array
     */
    public function getNames ( ) {

        return $this->_names;
    }

    /**
     * Enable cache
     *
     * @access  public
     * @return  bool
     */
    public static function enableCache ( ) {

        $old                 = self::$_cacheEnabled;
        self::$_cacheEnabled = true;

        return $old;
    }

    /**
     * Disable cache
     *
     * @access  public
     * @return  bool
     */
    public static function disableCache ( ) {

        $old                 = self::$_cacheEnabled;
        self::$_cacheEnabled = false;

        return $old;
    }

    /**
     * Transform automatas into DOT language.
     *
     * @access  public
     * @return  void
     */
    public function __toString ( ) {

        $out  = 'digraph ' . get_class($this) . ' {' . "\n" .
                '    rankdir=LR;' . "\n" .
                '    label="Automata of ' . get_class($this) . '";';

        $transitions = array_reverse($this->_transitions, true);

        foreach($transitions as $e => $automata) {

            $out .= "\n\n" . '    subgraph cluster_' . $e . ' {' . "\n" .
                    '        label="Automata #' . $e .
                    (isset($this->_names[$e])
                        ? ' (' . str_replace('"', '\\"', $this->_names[$e]) . ')'
                        : '') . '";' . "\n";

            if(!empty($this->_terminal[$e]))
                $out .= '        node[shape=doublecircle] "' . $e . '_' .
                        implode('" "' . $e . '_', $this->_terminal[$e]) . '";' . "\n";

            $out .= '        node[shape=circle];' . "\n";

            foreach($this->_states[$e] as $i => $state) {

                $_states = array_flip($this->_states[$e]);
                $name    = array();
                $label   = $state;

                if(__ != $state) {

                    foreach($this->_transitions[$e][$i] as $j => $foo) {

                        $ep = $this->_actions[$e][$i][$j];

                        if(is_array($ep))
                            $ep = $ep[0];

                        if(is_int($ep)) {

                            $ep--;

                            if(0 < $ep && !isset($name[$ep]))
                                $name[$ep] = $ep;
                        }
                    }

                    if(!empty($name))
                        $label .= ' (' . implode(', ', $name) . ')';

                    $out .= '        "' . $e . '_' . $state . '" ' .
                            '[label="' . $label . '"];' . "\n";
                }
            }

            foreach($automata as $i => $transition) {

                $transition = array_reverse($transition, true);

                foreach($transition as $j => $state)
                    if(   __ != $this->_states[$e][$i]
                       && __ != $state) {

                        $label = str_replace('\\', '\\\\', $this->_tokens[$e][$j]);
                        $label = str_replace('"', '\\"', $label);

                        if('#' == $label[0])
                            $label = substr($label, 1);

                        $out .= '        "' . $e . '_' . $this->_states[$e][$i] .
                                '" -> "' . $e . '_' . $state . '"' .
                                ' [label="' . $label . '"];' . "\n";
                    }
            }
            
            $out .= '        node[shape=point,label=""] "' . $e . '_";' . "\n" .
                    '        "' . $e . '_" -> "' . $e . '_GO";' . "\n" .
                    '    }';
        }

        $out .= "\n" . '}' . "\n";

        return $out;
    }
}
