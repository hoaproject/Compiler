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
 * \Hoa\Compiler\Exception
 */
-> import('Compiler.Exception.~')

/**
 * \Hoa\Compiler\Llk\Parser
 */
-> import('Compiler.Llk.Parser')

/**
 * \Hoa\Compiler\Llk\Rule\Analyzer
 */
-> import('Compiler.Llk.Rule.Analyzer');

}

namespace Hoa\Compiler\Llk {

/**
 * Class \Hoa\Compiler\Llk.
 *
 * Provide a generic LL(k) compiler compiler using the PP language.
 * Support: skip (%skip), token (%token), token namespace (ns1:token name value
 * -> ns2), rule (rule:), disjunction (|), capturing (operators ( and )),
 * quantifiers (?, +, * and {n,m}), node (#node), skipped token (::token::),
 * kept token (<token>), token unification (token[i]) and rule unification
 * (rule()[j]).
 *
 * @author     Frédéric Dadeau <frederic.dadeau@femto-st.fr>
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2012 Frédéric Dadeau, Ivan Enderlin.
 * @license    New BSD License
 */

class Llk {

    /**
     * Load parser from a file that contains the grammar.
     * Example:
     *     %skip  space     \s
     *
     *     %token word      [a-zA-Z]+
     *     %token number    [0-9]+(\.[0-9]+)?
     *     %token open_par  \(
     *     %token close_par \)
     *     %token equal     =
     *     %token plus      \+
     *     %token minus     \-
     *     %token divide    \/
     *     %token times     \*
     *
     *     #equation:
     *         formula() ::equal:: <number>
     *
     *     formula:
     *         factor()
     *         (
     *             ::plus::  formula() #addition
     *           | ::minus:: formula() #substraction
     *         )?
     *
     *     factor:
     *         operand()
     *         (
     *             ::times::  factor() #product
     *           | ::divide:: factor() #division
     *         )?
     *
     *     operand:
     *           <word>
     *         | ::minus::? <number> #number
     *         | ::open_par:: formula() ::close_par::
     *
     * Use tabs or spaces, it does not matter.
     * Instructions follow the form: %<instruction>. Only %skip and %token are
     * supported.
     * Rules follow the form: <rule name>:<new line>[<space><rule><new line>]*.
     * Contexts are useful to set specific skips and tokens. We give a full
     * example with context + unification (for fun) to parse <a>b</a>:
     *     %skip   space         \s
     *     %token  lt             <        ->  in_tag
     *     %token  inner          [^<]*
     *
     *     %skip   in_tag:space   \s
     *     %token  in_tag:slash   /
     *     %token  in_tag:tagname [^>]+
     *     %token  in_tag:gt      >        ->  default
     *
     *     #foo:
     *         ::lt:: <tagname[0]> ::gt::
     *         <inner>
     *         ::lt:: ::slash:: ::tagname[0]:: ::gt::
     *
     * @access  public
     * @param   \Hoa\Stream\IStream\In  $stream    Stream that contains the
     *                                             grammar.
     * @return  \Hoa\Compiler\Llk
     * @throw   \Hoa\Compiler\Exception
     */
    public static function load ( \Hoa\Stream\IStream\In $stream ) {

        $pp     = $stream->readAll();
        $lines  = explode("\n", $pp);
        $tokens = array('default' => array());
        $rules  = array();

        for($i = 0, $m = count($lines); $i < $m; ++$i) {

            $line = rtrim($lines[$i]);

            if(0 === strlen($line) || '//' == substr($line, 0, 2))
                continue;

            if('%' == $line[0]) {

                if(0 !== preg_match(
                    '#^%skip\s+(?:([^:]+):)?([^\s]+)\s+(.*)$#',
                    $line,
                    $matches)) {

                    if(empty($matches[1]))
                        $matches[1] = 'default';

                    if(!isset($tokens[$matches[1]]))
                        $tokens[$matches[1]] = array();

                    if(!isset($tokens[$matches[1]]['skip']))
                        $tokens[$matches[1]]['skip'] = $matches[3];
                    else
                        $tokens[$matches[1]]['skip'] =
                            '(?:' . $matches[3] . ')|' .
                            $tokens[$matches[1]]['skip'];
                }

                elseif(0 !== preg_match(
                    '#^%token\s+(?:([^:]+):)?([^\s]+)\s+(.*?)(?:\s+->\s+(.*))?$#',
                    $line,
                    $matches)) {

                    if(empty($matches[1]))
                        $matches[1] = 'default';

                    if(isset($matches[4]) && !empty($matches[4]))
                        $matches[2] = $matches[2] . ':' . $matches[4];

                    if(!isset($tokens[$matches[1]]))
                        $tokens[$matches[1]] = array();

                    $tokens[$matches[1]][$matches[2]] = $matches[3];
                }

                else
                    throw new \Hoa\Compiler\Exception(
                        'Unrecognized instructions:' . "\n" .
                        '    %s' . "\n" . 'in file %s at line %d.',
                        0, array($line, $stream->getStreamName(), $i + 1));

                continue;
            }

            $ruleName = substr($line, 0, -1);
            $rule     = null;
            ++$i;

            while(   $i < $m
                  && isset($lines[$i][0])
                  && (' '  == $lines[$i][0]
                  ||  "\t" == $lines[$i][0]
                  ||  '//' == substr($lines[$i], 0, 2))) {

                if('//' == substr($lines[$i], 0, 2)) {

                    ++$i;

                    continue;
                }

                $rule .= ' ' . trim($lines[$i++]);
            }

            if(isset($lines[$i][0]))
                --$i;

            $rules[$ruleName] = $rule;
        }

        $ruleAnalyzer = new Rule\Analyzer($tokens);
        $rules        = $ruleAnalyzer->analyzeRules($rules);

        return new Parser($tokens, $rules);
    }
}

}
