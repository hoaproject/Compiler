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
 * \Hoa\Compiler\Exception\UnrecognizedToken
 */
-> import('Compiler.Exception.UnrecognizedToken')

/**
 * \Hoa\Compiler\Exception\Lexer
 */
-> import('Compiler.Exception.Lexer');

}

namespace Hoa\Compiler\Llk {

/**
 * Class \Hoa\Compiler\Llk\Lexer.
 *
 * PP lexer.
 *
 * @author     Frédéric Dadeau <frederic.dadeau@femto-st.fr>
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2013 Frédéric Dadeau, Ivan Enderlin.
 * @license    New BSD License
 */

class Lexer {

    /**
     * Lexer state.
     *
     * @var \Hoa\Compiler\Llk\Lexer array
     */
    protected $_lexerState = null;

    /**
     * Text.
     *
     * @var \Hoa\Compiler\Llk\Lexer string
     */
    protected $_text       = null;

    /**
     * Tokens.
     *
     * @var \Hoa\Compiler\Llk\Lexer array
     */
    protected $_tokens     = array();

    /**
     * Namespace stacks.
     *
     * @var \SplStack object
     */
    protected $_nsStack    = null;



    /**
     * Text tokenizer: splits the text in parameter in an ordered array of
     * tokens.
     *
     * @access  protected
     * @param   string  $text      Text to tokenize.
     * @param   array   $tokens    Tokens to be returned.
     * @return  array
     * @throw   \Hoa\Compiler\Exception\UnrecognizedToken
     */
    public function lexMe ( $text, Array $tokens ) {

        $this->_text       = $text;
        $this->_tokens     = $tokens;
        $this->_nsStack    = null;
        $offset            = 0;
        $tokenized         = array();
        $this->_lexerState = 'default';
        $stack             = false;

        foreach($this->_tokens as &$tokens) {

            $_tokens = array();

            foreach($tokens as $fullLexeme => $regex) {

                if(false === strpos($fullLexeme, ':')) {

                    $_tokens[$fullLexeme] = array($regex, null);
                    continue;
                }

                list($lexeme, $namespace) = explode(':', $fullLexeme, 2);

                $stack |= ('__shift__' === substr($namespace, 0, 9));

                unset($tokens[$fullLexeme]);
                $_tokens[$lexeme] = array($regex, $namespace);
            }

            $tokens = $_tokens;
        }

        if(true == $stack)
            $this->_nsStack = new \SplStack();

        while($offset < strlen($this->_text)) {

            $nextToken = $this->nextToken($offset);

            if(null === $nextToken)
                throw new \Hoa\Compiler\Exception\UnrecognizedToken(
                    'Unrecognized token "%s" at line 1 and column %d:' .
                    "\n" . '%s' . "\n" . str_repeat(' ', $offset) . '↑',
                    0, array(substr($this->_text, $this->_text[$offset], 1), $offset + 1, $text),
                    1, $offset
                );

            if(true === $nextToken['keep']) {

                $nextToken['offset'] = $offset;
                $tokenized[]         = $nextToken;
            }

            $offset += strlen($nextToken['value']);
        }

        $tokenized[] = array(
            'token'     => 'EOF',
            'value'     => 'EOF',
            'length'    => 0,
            'namespace' => 'default',
            'keep'      => true,
            'offset'    => $offset
        );

        return $tokenized;
    }

    /**
     * Compute the next token recognized at the beginning of the string.
     *
     * @access  protected
     * @param   int $offset     Where to start from.
     * @return  array
     * @throw   \Hoa\Compiler\Exception\UnrecognizedToken
     */
    protected function nextToken ( $offset ) {

        $tokenArray = &$this->_tokens[$this->_lexerState];

        foreach($tokenArray as $lexeme => $bucket) {

            list($regex, $nextState) = $bucket;

            if(null === $nextState)
                $nextState = $this->_lexerState;

            $out = $this->matchLexeme($lexeme, $regex, $offset);

            if(null !== $out) {

                $out['namespace'] = $this->_lexerState;
                $out['keep']      = 'skip' !== $lexeme;

                if($nextState !== $this->_lexerState) {

                    $shift = false;

                    if(   null !== $this->_nsStack
                       &&    0 !== preg_match('#^__shift__(?:\s*\*\s*(\d+))?$#', $nextState, $matches)) {

                        $i = isset($matches[1]) ? intval($matches[1]) : 1;

                        if($i > ($c = count($this->_nsStack)))
                            throw new \Hoa\Compiler\Exception\Lexer(
                                'Cannot shift namespace %d-times, from token ' .
                                '%s in namespace %s,  because the stack ' .
                                'contains only %d namespaces.',
                                1, array($i, $lexeme, $this->_lexerState, $c));

                        while(1 <=  $i--)
                            $previousNamespace = $this->_nsStack->pop();

                        $nextState = $previousNamespace;
                        $shift     = true;
                    }

                    if(!isset($this->_tokens[$nextState]))
                        throw new \Hoa\Compiler\Exception\Lexer(
                            'Namespace %s does not exist, called by token %s ' .
                            'in namespace %s.',
                            2, array($nextState, $lexeme, $this->_lexerState));

                    if(null !== $this->_nsStack && false === $shift)
                        $this->_nsStack[] = $this->_lexerState;

                    $this->_lexerState = $nextState;
                }

                return $out;
            }
        }

        return null;
    }

    /**
     * Check if a given lexeme is matched at the beginning of the text.
     *
     * @access  protected
     * @param   string  $lexeme    Name of the lexeme.
     * @param   string  $regex     Regular expression describing the lexeme.
     * @param   int     $offset    Where to start matching.
     * @return  array
     * @throw   \Hoa\Compiler\Exception\Lexer
     */
    protected function matchLexeme ( $lexeme, $regex, $offset ) {

        $_regex = str_replace('#', '\#', $regex);

        $status = preg_match('#(?|' . $_regex . ')#u', $this->_text, $matches, PREG_OFFSET_CAPTURE, $offset);

        if (false === $status)
            throw new \Hoa\Compiler\Exception\Lexer(
                'PCRE error %s occured during matching ' .
                'of "%s" (%s) at offset %d', 3, array(preg_last_error(), $lexeme, $regex, $offset));

        if (0 !== $status) {

            $match = & $matches[0];

            if ($offset !== $match[1])
                return null;

            if('' === $match[0])
                throw new \Hoa\Compiler\Exception\Lexer(
                    'A lexeme must not match an empty value, which is the ' .
                    'case of "%s" (%s).', 3, array($lexeme, $regex));

            return array(
                'token'  => $lexeme,
                'value'  => $match[0],
                'length' => mb_strlen($match[0])
            );
        }

        return null;
    }
}

}
