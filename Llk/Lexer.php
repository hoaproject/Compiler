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
        $offset            = 0;
        $tokenized         = array();
        $this->_lexerState = 'default';

        while(0 < strlen($this->_text)) {

            $nextToken = $this->nextToken();

            if(null === $nextToken)
                throw new \Hoa\Compiler\Exception\UnrecognizedToken(
                    'Unrecognized token "%s" at line 1 and column %d:' .
                    "\n" . '%s' . "\n" . str_repeat(' ', $offset) . '↑',
                    0, array($this->_text[0], $offset + 1, $text),
                    1, $offset
                );

            if(true === $nextToken['keep']) {

                $nextToken['offset'] = $offset;
                $tokenized[]         = $nextToken;
            }

            $offset      += $nextToken['length'];
            $this->_text  = substr($this->_text, $nextToken['length']);
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
     * @return  array
     */
    protected function nextToken ( ) {

        $tokenArray = &$this->_tokens[$this->_lexerState];

        foreach($tokenArray as $fullLexeme => $regexp) {

            if(false !== strpos($fullLexeme, ':'))
                list($lexeme, $nextState) = explode(':', $fullLexeme, 2);
            else {

                $lexeme    = $fullLexeme;
                $nextState = $this->_lexerState;
            }

            $out = $this->matchLexeme($lexeme, $regexp);

            if(null !== $out) {

                $out['namespace']  = $this->_lexerState;
                $this->_lexerState = $nextState;

                if('skip' !== $lexeme)
                    $out['keep'] = true;
                else
                    $out['keep'] = false;

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
     * @param   string  $regexp    Regular expression describing the lexeme.
     * @return  array
     * @throw   \Hoa\Compiler\Exception\Lexer
     */
    protected function matchLexeme ( $lexeme, $regexp ) {

        $_regexp = str_replace('#', '\#', $regexp);

        if(0 !== preg_match('#^(?:' . $_regexp . ')#u', $this->_text, $matches)) {

            if('' === $matches[0])
                throw new \Hoa\Compiler\Exception\Lexer(
                    'A lexeme must not match an empty value, which is the ' .
                    'case of "%s" (%s).', 1, array($lexeme, $regexp));

            return array(
                'token'  => $lexeme,
                'value'  => $matches[0],
                'length' => strlen($matches[0])
            );
        }

        return null;
    }
}

}
