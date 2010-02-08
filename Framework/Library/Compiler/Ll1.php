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
 * Class Hoa_Compiler_Ll1.
 *
 * 
 *
 * @author      Ivan ENDERLIN <ivan.enderlin@hoa-project.net>
 * @copyright   Copyright (c) 2007, 2009 Ivan ENDERLIN.
 * @license     http://gnu.org/licenses/gpl.txt GNU GPL
 * @since       PHP 5
 * @version     0.1
 * @package     Hoa_Compiler_Ll1
 */

abstract class Hoa_Compiler_Ll1 {

    protected $_skip        = array();
    protected $_tokens      = array();
    protected $_states      = array();
    protected $_terminal    = array();
    protected $_transitions = array();
    protected $_actions     = array();
    protected $_buffers     = array();



    /**
     * Singleton, and set parameters.
     *
     * @access  private
     * @param   array    $parameters    Parameters.
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

    public function compile ( $in ) {

        $_skip      = array_flip($this->_skip);
        $_tokens    = array_flip($this->_tokens);
        $_states    = array_flip($this->_states);

        $nextChar   = null;
        $nextToken  = null;
        $nextState  = $_states['GO'];
        $nextAction = $_states['GO'];

        $iError     = 0;
        $nError     = 0;
        $tError     = 0;

        for($i = 0, $max = strlen($in); $i <= $max; $i++, $iError++) {

            if($i == $max) {

                if(in_array($this->_states[$nextState], $this->_terminal)) {

                    echo '*** Code was parsed successfully!' . "\n";

                    break;
                }

                echo '*** End-parse error' . "\n" .
                     '*** Error: end of code was reached but not correctly; ' . "\n" .
                     '           maybe your program is not complete?' . "\n";

                break;
            }

            $nextChar = $in[$i];

            if($nextChar == "\n") {

                $iError = 0;
                $nError++;
            }

            if(isset($_skip[$nextChar])) {

                $iError--;
                continue;
            }
            else {

                $continue = false;
                $handle   = substr($in, $i);

                foreach($this->_skip as $e => $sk) {

                    if($sk[0] != '#')
                        continue;

                    $sk = str_replace('#', '\#', substr($sk, 1));

                    if(0 != preg_match('#^(' . $sk . ')#', $handle, $match)) {

                        $strlen = strlen($match[1]);

                        if($strlen > 0) {

                            $iError--;
                            $nError   += substr_count($match[1], "\n");
                            $i        += $strlen - 1;
                            $continue  = true;

                            break;
                        }
                    }
                }

                if(true === $continue)
                    continue;
            }

            if(isset($_tokens[$nextChar])) {

                $nextToken = $_tokens[$nextChar];
                $tError    = $iError;
            }
            else {

                $nextToken = false;
                $handle    = substr($in, $i);

                foreach($this->_tokens as $e => $token) {

                    if($token[0] != '#')
                        continue;

                    $token = str_replace('#', '\#', substr($token, 1));

                    if(0 != preg_match('#^(' . $token . ')#', $handle, $match)) {

                        $strlen = strlen($match[1]);

                        if($strlen > 0) {

                            $nextChar   = $match[1];
                            $nextToken  = $e;
                            $i         += $strlen - 1;
                            $tError     = $iError + $strlen - 1;

                            break;
                        }
                    }
                }
            }

            if(false !== $nextToken) {

                $nextAction = $this->_actions[$nextState][$nextToken];
                $nextState  = $_states[$this->_transitions[$nextState][$nextToken]];
            }

            if(false === $nextToken || $nextState === $_states['__']) {

                $error = explode("\n", $in);
                $error = $error[$nError];

                echo '*** Illegal token (' . ($nError + 1) . 'L/' . ($iError + 1) . 'C):' . "\n" .
                     '*** Error:  ' . $error . "\n" .
                     '            ' . str_repeat(' ', $iError) . '↑' . "\n" .
                     '*** Tokens: ' . $nextChar .   "\n";

                exit;
            }

            $iError = $tError;

            if($nextAction < 0) {

                $buffer = abs($nextAction);

                if(($buffer & 1) == 0)
                    $this->_buffers[($buffer - 2) / 2] = null;
                else {

                    $buffer = ($buffer - 1) / 2;

                    if(!(isset($this->_buffers[$buffer])))
                        $this->_buffers[$buffer] = null;

                    $this->_buffers[$buffer] .= $nextChar;
                }

                continue;
            }

            $this->consume($nextAction);
        }

        return;
    }

    abstract protected function consume ( $action );

    public function setSkip ( Array $skip ) {

        $old         = $this->_skip;
        $this->_skip = $skip;

        return $old;
    }

    public function setTokens ( Array $tokens ) {

        $old           = $this->_tokens;
        $this->_tokens = $tokens;

        return $old;
    }

    public function setStates ( Array $states ) {

        $old           = $this->_states;
        $this->_states = $states;

        return $old;
    }

    public function setTerminal ( Array $terminal ) {

        $old             = $this->_terminal;
        $this->_terminal = $terminal;

        return $old;
    }

    public function setTransitions ( Array $transitions ) {

        $old                = $this->_transitions;
        $this->_transitions = $transitions;

        return $old;
    }

    public function setActions ( Array $actions ) {

        $old            = $this->_actions;
        $this->_actions = $actions;

        return $old;
    }

    public function getSkip ( ) {

        return $this->_skip;
    }

    public function getTokens ( ) {

        return $this->_tokens;
    }

    public function getStates ( ) {

        return $this->_states;
    }

    public function getTerminal ( ) {

        return $this->_terminal;
    }

    public function getTransitions ( ) {

        return $this->_transitions;
    }

    public function getActions ( ) {

        return $this->_actions;
    }
}
