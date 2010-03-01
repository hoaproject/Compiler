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
 * Copyright (c) 2007, 2009 Ivan ENDERLIN. All rights reserved.
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
 * Hoa_Framework
 */
require_once 'Framework.php';

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
 * Define the __ constant, so usefull in compiler :-).
 */
!defined('GO') and define('GO', 'GO');
!defined('__') and define('__', '__');

/**
 * Class Hoa_Compiler_Ll1.
 *
 * Provide an abstract LL(1) compiler.
 *
 * @author      Ivan ENDERLIN <ivan.enderlin@hoa-project.net>
 * @copyright   Copyright (c) 2007, 2009 Ivan ENDERLIN.
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
    protected $_initialLine = 0;

    /**
     * Tokens to skip (will be totally skip, no way to get it).
     * Tokens rules could be apply here (i.e. normal and special tokens are
     * understood).
     *
     * @var Hoa_Compiler_Ll1 array
     */
    protected $_skip        = array();

    /**
     * Tokens.
     * A token should be:
     *     * simple, it means just a single char, e.g. ':';
     *     * special, strings and/or a regular expressions, and must begin with
     *       a sharp (#), e.g. '#foobar', '#[a-zA-Z]' or '#fo?bar'.
     * Note: if we want the token #, we should write '##'.
     * PCRE expressions are fully-supported.
     *
     * @var Hoa_Compiler_Ll1 array
     */
    protected $_tokens      = array();

    /**
     * States.
     * Note: the constant GO or the string 'GO' must be used to represent the
     *       initial state.
     * Note: the constant __ or the string '__' must be used to represent the
     *       null/unrecognized/error state.
     *
     * @var Hoa_Compiler_Ll1 array
     */
    protected $_states      = array();

    /**
     * Terminal states (defined in the states set).
     *
     * @var Hoa_Compiler_Ll1 array
     */
    protected $_terminal    = array();

    /**
     * Transitions table.
     * It's actually a matrix, such as: TT(TOKENS × STATES), i.e.:
     * array(
     *                 a     b     c     d
     *     __  array( __ ,  __ ,  __ ,  __ ),
     *     GO  array('AA', 'BB', 'CC',  __ ),
     *     AA  array('AA',  __ ,  __ ,  __ ),
     *     BB  array( __ , 'BB',  __ ,  __ ),
     *     CC  array( __ ,  __ , 'CC', 'EN'),
     *     EN  array( __ ,  __ ,  __ ,  __ )
     * )
     * Note: tokens and states should be declared in the strict same order as
     *       defined previously.
     *
     * @var Hoa_Compiler_Ll1 array
     */
    protected $_transitions = array();

    /**
     * Actions table.
     * It's actually a matrix, such as: AT(TOKENS × STATES), i.e.:
     * array(
     *                a   b   c   d
     *     __  array( n,  …,  …,  m),
     *     GO  array( …,  …,  …,  …),
     *     AA  array( …,  …,  …,  …),
     *     BB  array( …,  …,  …,  …),
     *     CC  array( …,  …,  …,  …),
     *     EN  array( …,  …,  …,  p)
     * )
     * AT is filled with integer n.
     * If n > 0, it means a normal action.
     * If n = 0, it means no action.
     * If n < 0, it means a special action.
     *
     * When we write our consume() method, it's just a simple switch receiving
     * an action. It receives only positive/normal actions. It's like a “goto”
     * in our compiler, and allows us to execute code when skiming through the
     * graph.
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
     * @var Hoa_Compiler_Ll1 array
     */
    protected $_actions     = array();

    /**
     * Buffers.
     *
     * @var Hoa_Compiler_Ll1 array
     */
    protected $buffers      = array();

    /**
     * Recursive stack.
     *
     * @var Hoa_Compiler_Ll1 array
     */
    protected $_stack       = array();

    /**
     * Current transition table.
     *
     * @var Hoa_Compiler_Ll1 array
     */
    protected $_transition  = array();



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
     * @return  void
     */
    public function __construct ( Array $skip,
                                  Array $tokens,
                                  Array $states,
                                  Array $terminal,
                                  Array $transitions,
                                  Array $actions      ) {

        $this->setSkip($skip);
        $this->setTokens($tokens);
        $this->setStates($states);
        $this->setTerminal($terminal);
        $this->setTransitions($transitions);
        $this->setActions($actions);

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

        $d              = 0;
        $c              = 0; // current automata.
        $_skip          = array_flip($this->_skip);
        $_tokens        = array_flip($this->_tokens[$c]);
        $_states        = array_flip($this->_states[$c]);
        $_actions       = array($c => 0);

        $nextChar       = null;
        $nextToken      = null;
        $nextState      = $_states['GO'];
        $nextAction     = $_states['GO'];

        $iError         = $this->getInitialLine();
        $nError         = 0;
        $tError         = $iError;

        for($i = 0, $max = strlen($in); $i <= $max; $i++) {

            $fError = false;

            //echo "\n---\n\n";

            // End of parsing (not automata).
            if($i == $max) {

                if(   in_array($this->_states[$c][$nextState], $this->_terminal[$c])
                   && true === $this->end()) {

                    //echo '*********** END REACHED **********' . "\n";

                    return true;
                }

                throw new Hoa_Compiler_Exception_FinalStateHasNotBeenReached(
                    'End of code has beenreached but not correctly; ' .
                    'maybe your program is not complete?',
                    0
                );
            }

            $nextChar = $in[$i];

            if($nextChar == "\n") {

                $iError = 0;
                $nError++;
            }

            // Skip.
            if(isset($_skip[$nextChar])) {

                $iError++;

                if($nextChar == "\n")
                    $nError--;

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
                                $iError = $strlen - $offset - 1;
                            else
                                $iError += $strlen;

                            $nError   += substr_count($match[1], "\n") - 1;
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
            if(   array_key_exists($nextToken, $this->_actions[$c][$nextState])
               && ($foo = $this->_actions[$c][$nextState][$nextToken]) > 0) {

                if($_actions[$c] == 0) {

                    //echo '*** Change automata (up)' . "\n";

                    $_actions[$c] = 1;

                    $this->_stack[$d] = array($c, $nextState, $nextToken);
                    end($this->_stack);

                    $c            = $foo - 1;
                    $_tokens      = array_flip($this->_tokens[$c]);
                    $_states      = array_flip($this->_states[$c]);
                    $_actions     = $this->_actions[$c];

                    $nextState    = $_states['GO'];
                    $nextAction   = $_states['GO'];
                    $nextToken    = $_tokens[$token];

                    $_actions[$c] = 0;

                    $d++;
                }
                elseif($_actions[$c] == 2)
                    $_actions[$c] = 0;
            }

            // Token.
            if(isset($_tokens[$nextChar])) {

                $token      = $nextChar;
                $nextToken  = $_tokens[$token];
                $iError++;
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

                            $nextChar   = $match[1];
                            $nextToken  = $e;
                            $i         += $strlen - 1;
                            $iError    += $strlen;

                            break;
                        }
                    }
                }
            }

            /*
            echo '>>> Automata   ' . $c . "\n" .
                 '>>> Next state ' . $nextState . "\n" .
                 '>>> Token      ' . $token . "\n";
            */

            // Got it!
            if(false !== $nextToken) {

                $nextAction = $this->_actions[$c][$nextState][$nextToken];
                $nextState  = $_states[$this->_transitions[$c][$nextState][$nextToken]];
            }

            // Oh :-(.
            if(false === $nextToken || $nextState === $_states['__']) {

                $pop = array_pop($this->_stack);
                $d--;

                // Go back to an old automata.
                if(   (in_array($this->_states[$c][$nextState], $this->_terminal[$c])
                   &&  null !== $pop)
                   ||  $nextState === $_states['__']) {

                    //echo '!!! Change automata (down)' . "\n";

                    list($c, $nextState, $nextToken) = $pop;

                    $_actions[$c] = 2;

                    $i       -= strlen($token);
                    $_tokens  = array_flip($this->_tokens[$c]);
                    $_states  = array_flip($this->_states[$c]);

                    /*
                    echo '!!! Automata   ' . $c . "\n" .
                         '!!! Next state ' . $nextState . "\n";
                    */

                    continue;
                }

                $error = explode("\n", $in);
                $error = $error[$nError];

                throw new Hoa_Compiler_Exception_IllegalToken(
                    'Illegal token at line ' . ($nError + 1) . ' and column ' .
                    ($iError + 1) . "\n" . $error . "\n" . str_repeat(' ', $iError) . '↑',
                    0, array(), $nError + 1, $iError + 1
                );
            }

            //echo '<<< Next state ' . $nextState . "\n";

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
     *
     * add pre() and post() method.
     *
     */

    /**
     * Consume actions.
     * Please, see the actions table definition to learn more.
     *
     * @access  protected
     * @param   int        $action    Action.
     * @return  void
     */
    abstract protected function consume ( $action );

    /**
     * Verify compiler state when ending the source code.
     *
     * @access  protected
     * @return  bool
     */
    abstract protected function end ( );

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

        $old            = $this->_actions;
        $this->_actions = $actions;

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
}
