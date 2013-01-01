<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2013, Ivan Enderlin. All rights reserved.
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
 * \Hoa\Compiler\Exception\Rule
 */
-> import('Compiler.Exception.Rule')

/**
 * \Hoa\Compiler\Llk\Rule\Choice
 */
-> import('Compiler.Llk.Rule.Choice')

/**
 * \Hoa\Compiler\Llk\Rule\Concatenation
 */
-> import('Compiler.Llk.Rule.Concatenation')

/**
 * \Hoa\Compiler\Llk\Rule\Repetition
 */
-> import('Compiler.Llk.Rule.Repetition')

/**
 * \Hoa\Compiler\Llk\Rule\Token
 */
-> import('Compiler.Llk.Rule.Token')

/**
 * \Hoa\Compiler\Llk\Lexer
 */
-> import('Compiler.Llk.Lexer');

}

namespace Hoa\Compiler\Llk\Rule {

/**
 * Class \Hoa\Compiler\Llk\Rule\Analyzer.
 *
 * Analyze rules and transform them into atomic rules operations.
 *
 * @author     Frédéric Dadeau <frederic.dadeau@femto-st.fr>
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2013 Frédéric Dadeau, Ivan Enderlin.
 * @license    New BSD License
 */

class Analyzer {

    /**
     * Created rules.
     *
     * @var \Hoa\Compiler\Llk\rule\Analyzer array
     */
    protected $_createdRules = null;

    /**
     * Current analyzer state.
     *
     * @var \Hoa\Compiler\Llk\rule\Analyzer array
     */
    protected $_currentState = 0;

    /**
     * Tokens representing rules.
     *
     * @var \Hoa\Compiler\Llk\rule\Analyzer array
     */
    protected $_tokens       = null;

    /**
     * Rules.
     *
     * @var \Hoa\Compiler\Llk\rule\Analyzer array
     */
    protected $_rules        = null;



    /**
     * Constructor.
     *
     * @access  public
     * @param   array  $tokens    Tokens.
     * @return  void
     */
    public function __construct ( Array $tokens ) {

        $this->_tokens = $tokens;

        return;
    }

    /**
     * Get created rules.
     *
     * @access  public
     * @return  array
     */
    public function getCreatedRules ( ) {

        return $this->_createdRules;
    }

   /**
     * Build the analyzer of the rules (does not analyze the rules).
     *
     * @access  protected
     * @param   array  $rules    Rule to be analyzed.
     * @return  void
     * @throw   \Hoa\Compiler\Exception
     */
    public function analyzeRules ( Array $rules ) {

        if(empty($rules))
            throw new \Hoa\Compiler\Exception\Rule('No rules specified!', 0);

        $tokens = array('default' =>
            array(
                'skip'          => '\s',
                'or'            => '\|',
                'zero_or_one'   => '\?',
                'one_or_more'   => '\+',
                'zero_or_more'  => '\*',
                'n_to_m'        => '\{[0-9]+,[0-9]+\}',
                'zero_to_m'     => '\{,[0-9]+\}',
                'n_or_more'     => '\{[0-9]+,\}',
                'exactly_n'     => '\{[0-9]+\}',
                'skipped'       => '::[a-zA-Z_][a-zA-Z0-9_]*(\[\d+\])?::',
                'kept'          => '<[a-zA-Z_][a-zA-Z0-9_]*(\[\d+\])?' . '>',
                'named'         => '[a-zA-Z_][a-zA-Z0-9_]*\(\)',
                'node'          => '#[a-zA-Z_][a-zA-Z0-9_]*(:[mM])?',
                'capturing_'    => '\(',
                '_capturing'    => '\)'
            )
        );

        $this->_createdRules = array();
        $this->_rules        = $rules;

        foreach($rules as $key => $value) {

            $lexer                = new \Hoa\Compiler\Llk\Lexer();
            $this->_tokenSequence = $lexer->lexMe($value, $tokens);
            $this->_currentState  = 0;
            $nodeId               = null;

            if('#' === $key[0]) {

                $nodeName  = $key;
                $buildNode = true;
                $nodeId    = $key;
                $key       = substr($key, 1);
            }
            else {

                $nodeName  = null;
                $buildNode = false;
            }

            $pNodeId = $nodeId;
            $rule    = $this->rule($pNodeId);

            if(null === $rule)
                throw new \Hoa\Compiler\Exception(
                    'Error while parsing rule %s.', 1, $key);

            $zeRule = $this->_createdRules[$rule];
            $zeRule->setName($key);
            $zeRule->setPPRepresentation($value);

            if(null !== $nodeId)
                $zeRule->setDefaultId($nodeId);

            unset($this->_createdRules[$rule]);
            $this->_createdRules[$key] = $zeRule;
        }

        return $this->_createdRules;
    }

    /**
     * Implementation of “rule”.
     *
     * @access  protected
     * @return  mixed
     */
    protected function rule ( &$pNodeId ) {

        return $this->choice($pNodeId);
    }

    /**
     * Implementation of “choice”.
     *
     * @access  protected
     * @return  mixed
     */
    protected function choice ( &$pNodeId ) {

        $content = array();

        // concatenation() …
        $nNodeId = $pNodeId;
        $rule    = $this->concatenation($nNodeId);

        if(null === $rule)
            return null;

        if(null !== $nNodeId)
            $this->_createdRules[$rule]->setNodeId($nNodeId);

        $content[] = $rule;
        $others    = false;

        // … ( ::or:: concatenation() )*
        while('or' == $this->getCurrentToken()) {

            $this->consumeToken();
            $others   = true;
            $nNodeId  = $pNodeId;
            $rule     = $this->concatenation($nNodeId);

            if(null === $rule)
                return null;

            if(null !== $nNodeId)
                $this->_createdRules[$rule]->setNodeId($nNodeId);

            $content[] = $rule;
        }

        $pNodeId = null;

        if(false === $others)
            return $rule;

        $name                       = count($this->_createdRules) + 1;
        $this->_createdRules[$name] = new Choice($name, $content, null);

        return $name;
    }

    /**
     * Implementation of “concatenation”.
     *
     * @access  protected
     * @return  mixed
     */
    protected function concatenation ( &$pNodeId ) {

        $content = array();

        // repetition() …
        $rule    = $this->repetition($pNodeId);

        if(null === $rule)
            return null;

        $content[] = $rule;
        $others    = false;

        // … repetition()*
        while(null !== $r1 = $this->repetition($pNodeId)) {

            $content[] = $r1;
            $others    = true;
        }

        if(false === $others && null === $pNodeId)
            return $rule;

        $name                       = count($this->_createdRules) + 1;
        $this->_createdRules[$name] = new Concatenation(
            $name,
            $content,
            null
        );

        return $name;
    }

    /**
     * Implementation of “repetition”.
     *
     * @access  protected
     * @return  mixed
     * @throw   \Hoa\Compiler\Exception
     */
    protected function repetition ( &$pNodeId ) {

        // simple() …
        $content = $this->simple($pNodeId);

        if(null === $content)
            return null;

        // … quantifier()?
        switch($this->getCurrentToken()) {

            case 'zero_or_one':
                $min = 0;
                $max = 1;
                $this->consumeToken();
              break;

            case 'one_or_more':
                $min =  1;
                $max = -1;
                $this->consumeToken();
              break;

            case 'zero_or_more':
                $min =  0;
                $max = -1;
                $this->consumeToken();
              break;

            case 'n_to_m':
                $handle = trim($this->getCurrentToken('value'), '{}');
                $nm     = explode(',', $handle);
                $min    = (int) trim($nm[0]);
                $max    = (int) trim($nm[1]);
                $this->consumeToken();
              break;

            case 'zero_to_m':
                $min = 0;
                $max = (int) trim($this->getCurrentToken('value'), '{,}');
                $this->consumeToken();
              break;

            case 'n_or_more':
                $min = (int) trim($this->getCurrentToken('value'), '{,}');
                $max = -1;
                $this->consumeToken();
              break;

            case 'exactly_n':
                $handle = trim($this->getCurrentToken('value'), '{}');
                $min    = (int) $handle;
                $max    = $min;
                $this->consumeToken();
              break;
        }

        // … <node>?
        if('node' == $this->getCurrentToken()) {

            $pNodeId = $this->getCurrentToken('value');
            $this->consumeToken();
        }

        if(!isset($min))
            return $content;

        if(-1 != $max && $max < $min)
            throw new \Hoa\Compiler\Exception(
                'Upper bound of iteration must be greater of ' .
                'equal to lower bound', 2);

        $name                       = count($this->_createdRules) + 1;
        $this->_createdRules[$name] = new Repetition(
            $name,
            $min,
            $max,
            $content,
            null
        );

        return $name;
    }

    /**
     * Implementation of “simple”.
     *
     * @access  protected
     * @return  mixed
     * @throw   \Hoa\Compiler\Exception\Rule
     */
    protected function simple ( &$pNodeId ) {

        if('capturing_' == $this->getCurrentToken()) {

            $this->consumeToken();
            $rule = $this->choice($pNodeId);

            if(null === $rule)
                return null;

            if('_capturing' != $this->getCurrentToken())
                return null;

            $this->consumeToken();

            return $rule;
        }

        if('skipped' == $this->getCurrentToken()) {

            $value = trim($this->getCurrentToken('value'), ':');

            if(']' == substr($value, -1)) {

                $uId   = (int) substr($value, strpos($value, '[') + 1, -1);
                $value = substr($value, 0, strpos($value, '['));
            }
            else
                $uId   = -1;

            $regex = $this->checkTokenExistence($value, $this->_tokens);

            if(false === $regex)
                throw new \Hoa\Compiler\Exception\Rule(
                    'Token ::%s:: does not exist.',
                    3, $value);

            $name                       = count($this->_createdRules) + 1;
            $this->_createdRules[$name] = new Token(
                $name,
                $value,
                $regex,
                null,
                $uId
            );
            $this->consumeToken();

            return $name;
        }

        if('kept' == $this->getCurrentToken()) {

            $value = trim($this->getCurrentToken('value'), '<>');

            if(']' == substr($value, -1)) {

                $uId   = (int) substr($value, strpos($value, '[') + 1, -1);
                $value = substr($value, 0, strpos($value, '['));
            }
            else
                $uId   = -1;

            $regex = $this->checkTokenExistence($value, $this->_tokens);

            if(false === $regex)
                throw new \Hoa\Compiler\Exception\Rule(
                    'Token <%s> does not exist.',
                    4, $value);

            $name  = count($this->_createdRules) + 1;
            $token = new Token(
                $name,
                $value,
                $regex,
                null,
                $uId
            );
            $token->setKept(true);
            $this->_createdRules[$name] = $token;
            $this->consumeToken();

            return $name;
        }

        if('named' == $this->getCurrentToken()) {

            $value = rtrim($this->getCurrentToken('value'), '()');

            if(   false === array_key_exists(      $value, $this->_rules)
               && false === array_key_exists('#' . $value, $this->_rules))
                throw new \Hoa\Compiler\Exception\Rule(
                    'Rule %s() does not exist.',
                    5, $value);

            if(   0     == $this->_currentState
               && 'EOF' == $this->getNextToken()) {

                $name                       = count($this->_createdRules) + 1;
                $this->_createdRules[$name] = new Concatenation(
                    $name,
                    array($value),
                    null
                );
            }
            else
                $name = $value;

            $this->consumeToken();

            return $name;
        }

        return null;
    }

    /**
     * Check token existence.
     *
     * @access  public
     * @param   string  $token         Token.
     * @param   array   $tokenArray    Tokens.
     * @return  bool
     */
    public function checkTokenExistence ( $token, &$tokenArray ) {

        foreach($tokenArray as $tokens)
            foreach($tokens as $tokName => $tokValue)
                if(false !== strpos($tokName, ':')) {

                    $tab = explode(':', $tokName);

                    if($token == $tab[0])
                        return $tokValue;
                }
                elseif($tokName == $token)
                    return $tokValue;

        return false;
    }

    /**
     * Get current token informations.
     *
     * @access  public
     * @param   string  $kind    Token information.
     * @return  string
     */
    public function getCurrentToken ( $kind = 'token' ) {

        return $this->_tokenSequence[$this->_currentState][$kind];
    }

    /**
     * Get next token informations.
     *
     * @access  public
     * @param   string  $kind    Token information.
     * @return  string
     */
    public function getNextToken ( $kind = 'token' ) {

        return $this->_tokenSequence[$this->_currentState + 1][$kind];
    }

    /**
     * Consume the current token and move to the next one.
     *
     * @access  public
     * @return  int
     */
    public function consumeToken ( ) {

        return ++$this->_currentState;
    }
}

}
