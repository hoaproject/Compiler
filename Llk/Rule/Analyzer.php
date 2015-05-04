<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2015, Hoa community. All rights reserved.
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

namespace Hoa\Compiler\Llk\Rule;

use Hoa\Compiler;

/**
 * Class \Hoa\Compiler\Llk\Rule\Analyzer.
 *
 * Analyze rules and transform them into atomic rules operations.
 *
 * @copyright  Copyright © 2007-2015 Hoa community
 * @license    New BSD License
 */
class Analyzer
{
    /**
     * Created rules.
     *
     * @var array
     */
    protected $_createdRules = null;

    /**
     * Tokens representing rules.
     *
     * @var array
     */
    protected $_tokens       = null;

    /**
     * Rules.
     *
     * @var array
     */
    protected $_rules        = null;

    /**
     * Current analyzed rule.
     *
     * @var string
     */
    protected $_rule         = null;

    /**
     * Current analyzer state.
     *
     * @var int
     */
    protected $_currentState = 0;



    /**
     * Constructor.
     *
     * @param   array  $tokens    Tokens.
     * @return  void
     */
    public function __construct(Array $tokens)
    {
        $this->_tokens = $tokens;

        return;
    }

    /**
     * Get created rules.
     *
     * @return  array
     */
    public function getCreatedRules()
    {
        return $this->_createdRules;
    }

   /**
     * Build the analyzer of the rules (does not analyze the rules).
     *
     * @param   array  $rules    Rule to be analyzed.
     * @return  void
     * @throws  \Hoa\Compiler\Exception
     */
    public function analyzeRules(Array $rules)
    {
        if (empty($rules)) {
            throw new Compiler\Exception\Rule('No rules specified!', 0);
        }

        $tokens = ['default' =>
            [
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
            ]
        ];

        $this->_createdRules = [];
        $this->_rules        = $rules;

        foreach ($rules as $key => $value) {
            $lexer                = new Compiler\Llk\Lexer();
            $this->_tokenSequence = $lexer->lexMe($value, $tokens);
            $this->_rule          = $value;
            $this->_currentState  = 0;
            $nodeId               = null;

            if ('#' === $key[0]) {
                $nodeId = $key;
                $key    = substr($key, 1);
            }

            $pNodeId = $nodeId;
            $rule    = $this->rule($pNodeId);

            if (null === $rule) {
                throw new Compiler\Exception(
                    'Error while parsing rule %s.',
                    1,
                    $key
                );
            }

            $zeRule = $this->_createdRules[$rule];
            $zeRule->setName($key);
            $zeRule->setPPRepresentation($value);

            if (null !== $nodeId) {
                $zeRule->setDefaultId($nodeId);
            }

            unset($this->_createdRules[$rule]);
            $this->_createdRules[$key] = $zeRule;
        }

        return $this->_createdRules;
    }

    /**
     * Implementation of “rule”.
     *
     * @return  mixed
     */
    protected function rule(&$pNodeId)
    {
        return $this->choice($pNodeId);
    }

    /**
     * Implementation of “choice”.
     *
     * @return  mixed
     */
    protected function choice(&$pNodeId)
    {
        $content = [];

        // concatenation() …
        $nNodeId = $pNodeId;
        $rule    = $this->concatenation($nNodeId);

        if (null === $rule) {
            return null;
        }

        if (null !== $nNodeId) {
            $this->_createdRules[$rule]->setNodeId($nNodeId);
        }

        $content[] = $rule;
        $others    = false;

        // … ( ::or:: concatenation() )*
        while ('or' === $this->getCurrentToken()) {
            $this->consumeToken();
            $others   = true;
            $nNodeId  = $pNodeId;
            $rule     = $this->concatenation($nNodeId);

            if (null === $rule) {
                return null;
            }

            if (null !== $nNodeId) {
                $this->_createdRules[$rule]->setNodeId($nNodeId);
            }

            $content[] = $rule;
        }

        $pNodeId = null;

        if (false === $others) {
            return $rule;
        }

        $name                       = count($this->_createdRules) + 1;
        $this->_createdRules[$name] = new Choice($name, $content, null);

        return $name;
    }

    /**
     * Implementation of “concatenation”.
     *
     * @return  mixed
     */
    protected function concatenation(&$pNodeId)
    {
        $content = [];

        // repetition() …
        $rule    = $this->repetition($pNodeId);

        if (null === $rule) {
            return null;
        }

        $content[] = $rule;
        $others    = false;

        // … repetition()*
        while (null !== $r1 = $this->repetition($pNodeId)) {
            $content[] = $r1;
            $others    = true;
        }

        if (false === $others && null === $pNodeId) {
            return $rule;
        }

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
     * @return  mixed
     * @throws  \Hoa\Compiler\Exception
     */
    protected function repetition(&$pNodeId)
    {

        // simple() …
        $content = $this->simple($pNodeId);

        if (null === $content) {
            return null;
        }

        // … quantifier()?
        switch ($this->getCurrentToken()) {

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
        if ('node' === $this->getCurrentToken()) {
            $pNodeId = $this->getCurrentToken('value');
            $this->consumeToken();
        }

        if (!isset($min)) {
            return $content;
        }

        if (-1 != $max && $max < $min) {
            throw new Compiler\Exception(
                'Upper bound of iteration must be greater of ' .
                'equal to lower bound',
                2
            );
        }

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
     * @return  mixed
     * @throws  \Hoa\Compiler\Exception
     * @throws  \Hoa\Compiler\Exception\Rule
     */
    protected function simple(&$pNodeId)
    {
        if ('capturing_' === $this->getCurrentToken()) {
            $this->consumeToken();
            $rule = $this->choice($pNodeId);

            if (null === $rule) {
                return null;
            }

            if ('_capturing' != $this->getCurrentToken()) {
                return null;
            }

            $this->consumeToken();

            return $rule;
        }

        if ('skipped' === $this->getCurrentToken()) {
            $tokenName = trim($this->getCurrentToken('value'), ':');

            if (']' === substr($tokenName, -1)) {
                $uId       = (int) substr($tokenName, strpos($tokenName, '[') + 1, -1);
                $tokenName = substr($tokenName, 0, strpos($tokenName, '['));
            } else {
                $uId = -1;
            }

            $exists = false;

            foreach ($this->_tokens as $namespace => $tokens) {
                foreach ($tokens as $token => $value) {
                    if ($token === $tokenName ||
                        substr($token, 0, strpos($token, ':')) === $tokenName) {
                        $exists = true;

                        break 2;
                    }
                }
            }

            if (false == $exists) {
                throw new Compiler\Exception(
                    'Token ::%s:: does not exist in%s.',
                    3,
                    [$tokenName, $this->_rule]
                );
            }

            $name                       = count($this->_createdRules) + 1;
            $this->_createdRules[$name] = new Token(
                $name,
                $tokenName,
                null,
                $uId
            );
            $this->consumeToken();

            return $name;
        }

        if ('kept' === $this->getCurrentToken()) {
            $tokenName = trim($this->getCurrentToken('value'), '<>');

            if (']' === substr($tokenName, -1)) {
                $uId       = (int) substr($tokenName, strpos($tokenName, '[') + 1, -1);
                $tokenName = substr($tokenName, 0, strpos($tokenName, '['));
            } else {
                $uId = -1;
            }

            $exists = false;

            foreach ($this->_tokens as $namespace => $tokens) {
                foreach ($tokens as $token => $value) {
                    if ($token === $tokenName
                       || substr($token, 0, strpos($token, ':')) === $tokenName) {
                        $exists = true;

                        break 2;
                    }
                }
            }

            if (false == $exists) {
                throw new Compiler\Exception(
                    'Token <%s> does not exist in%s.',
                    4,
                    [$tokenName, $this->_rule]
                );
            }

            $name  = count($this->_createdRules) + 1;
            $token = new Token(
                $name,
                $tokenName,
                null,
                $uId
            );
            $token->setKept(true);
            $this->_createdRules[$name] = $token;
            $this->consumeToken();

            return $name;
        }

        if ('named' === $this->getCurrentToken()) {
            $tokenName = rtrim($this->getCurrentToken('value'), '()');

            if (false === array_key_exists($tokenName, $this->_rules) &&
                false === array_key_exists('#' . $tokenName, $this->_rules)) {
                throw new Compiler\Exception\Rule(
                    'Rule %s() does not exist.',
                    5,
                    $tokenName
                );
            }

            if (0     ==  $this->_currentState &&
                'EOF' === $this->getNextToken()) {
                $name                       = count($this->_createdRules) + 1;
                $this->_createdRules[$name] = new Concatenation(
                    $name,
                    [$tokenName],
                    null
                );
            } else {
                $name = $tokenName;
            }

            $this->consumeToken();

            return $name;
        }

        return null;
    }

    /**
     * Get current token informations.
     *
     * @param   string  $kind    Token information.
     * @return  string
     */
    public function getCurrentToken($kind = 'token')
    {
        return $this->_tokenSequence[$this->_currentState][$kind];
    }

    /**
     * Get next token informations.
     *
     * @param   string  $kind    Token information.
     * @return  string
     */
    public function getNextToken($kind = 'token')
    {
        return $this->_tokenSequence[$this->_currentState + 1][$kind];
    }

    /**
     * Consume the current token and move to the next one.
     *
     * @return  int
     */
    public function consumeToken()
    {
        return ++$this->_currentState;
    }
}
