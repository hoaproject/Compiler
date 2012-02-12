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
 * \Hoa\Compiler\Exception\UnrecognizedToken
 */
-> import('Compiler.Exception.UnrecognizedToken');

}

namespace Hoa\Compiler\Llk {

/**
 * Class \Hoa\Compiler\Llk\Lexer.
 *
 * PP lexer.
 *
 * @author     Frédéric Dadeau <frederic.dadeau@femto-st.fr>
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2012 Frédéric Dadeau, Ivan Enderlin.
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

        $_text             = $text;
        $offset            = 0;
        $tokenized         = array();
        $this->_lexerState = 'default';

        while(0 < strlen($text)) {

            $nextToken = $this->nextToken($text, $tokens);

            if(null === $nextToken)
                throw new \Hoa\Compiler\Exception\UnrecognizedToken(
                    'Unrecognized token "%s" at line 1 and column %d:' .
                    "\n" . '%s' . "\n" . str_repeat(' ', $offset) . '↑',
                    0, array($text[0], $offset + 1, $_text),
                    1, $offset
                );

            if($nextToken['keep']) {

                $nextToken['offset'] = $offset;
                $tokenized[]         = $nextToken;
            }

            $offset += $nextToken['length'];
            $text    = substr($text, $nextToken['length']);
        }

        $tokenized[] = array(
            'token'  => 'EOF',
            'value'  => 'EOF',
            'length' => 0,
            'offset' => $offset,
            'keep'   => true
        );

        return $tokenized;
    }

    /**
     * Compute the next token recognized at the beginning of the string.
     *
     * @access  protected
     * @param   string  $text      Text to tokenize.
     * @param   array   $skip      Tokens to be skipped.
     * @param   array   $tokens    Tokens to be returned.
     * @return  array
     */
    protected function nextToken ( $text, Array &$tokens ) {

        $tokenArray = $tokens[$this->_lexerState];

        foreach($tokenArray as $fulllexeme => $regexp) {

            if(false !== strpos($fulllexeme, ':'))
                list($lexeme, $nextState) = explode(':', $fulllexeme, 2);
            else {

                $lexeme    = $fulllexeme;
                $nextState = $this->_lexerState;
            }

            $out = $this->matchesLexem($text, $lexeme, $regexp);

            if(   $lexeme !== 'skip'
               && null    !== $out) {

                $out['keep']       = true;
                $this->_lexerState = $nextState;

                return $out;
            }
        }

        $out = $this->matchesLexem($text, $lexeme, $tokenArray['skip']);

        if(null !== $out) {

            $out['keep'] = false;

            return $out;
        }

        return null;
    }

    /**
     * Check if a given lexem is matched at the beginning of the text.
     *
     * @access  protected
     * @param   string  $text      Text in which the lexem has to be found.
     * @param   string  $lexem     Name of the lexem.
     * @param   string  $regexp    Regular expression describing the lexem.
     * @return  array
     */
    protected function matchesLexem ( $text, $lexem, $regexp ) {

        $regexp = str_replace('#', '\#', $regexp);

        if(   0 !== preg_match('#' . $regexp . '#', $text, $matches)
           && 0 <   count($matches)
           && 0 === strpos($text, $matches[0]))
            return array(
                'token'  => $lexem,
                'value'  => $matches[0],
                'length' => strlen($matches[0])
            );

        return null;
    }
}

}
