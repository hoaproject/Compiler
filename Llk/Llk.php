<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2016, Hoa community. All rights reserved.
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

namespace Hoa\Compiler\Llk;

use Hoa\Compiler;
use Hoa\Consistency;
use Hoa\Stream;

/**
 * Class \Hoa\Compiler\Llk.
 *
 * Provide a generic LL(k) compiler compiler using the PP language.
 * Support: skip (%skip), token (%token), token namespace (ns1:token name value
 * -> ns2), rule (rule:), disjunction (|), capturing (operators ( and )),
 * quantifiers (?, +, * and {n,m}), node (#node) with options (#node:options),
 * skipped token (::token::), kept token (<token>), token unification (token[i])
 * and rule unification (rule()[j]).
 *
 * @copyright  Copyright © 2007-2016 Hoa community
 * @license    New BSD License
 */
class Llk
{
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
     * In addition to `%skip` and `%token`, we have the `%pragma` keyword to
     * declare a pragma. Currently support pragmas are:
     *
     *   * `lexer.unicode`, used by the lexer to turn the Unicode mode on for
     *     the regular expressions,
     *   * `parser.lookahead`, used by the parser to define the `k` in LL(k),
     *     i.e. number of tokens to lookahead. By default, it is set to 1024.
     *     Memory can be reduced by using an appropriated value.
     *
     * @param   \Hoa\Stream\IStream\In  $stream    Stream that contains the
     *                                             grammar.
     * @return  \Hoa\Compiler\Llk\Parser
     * @throws  \Hoa\Compiler\Exception
     */
    public static function load(Stream\IStream\In $stream)
    {
        $pp = $stream->readAll();

        if (empty($pp)) {
            $message = 'The grammar is empty';

            if ($stream instanceof Stream\IStream\Pointable) {
                if (0 < $stream->tell()) {
                    $message .=
                        ': the stream ' . $stream->getStreamName() .
                        ' is pointable and not rewinded, maybe it ' .
                        'could be the reason';
                } else {
                    $message .=
                        ': nothing to read on the stream ' .
                        $stream->getStreamName();
                }
            }

            throw new Compiler\Exception($message . '.', 0);
        }

        static::parsePP($pp, $tokens, $rawRules, $pragmas, $stream->getStreamName());

        $ruleAnalyzer = new Rule\Analyzer($tokens);
        $rules        = $ruleAnalyzer->analyzeRules($rawRules);

        return new Parser($tokens, $rules, $pragmas);
    }

    public static function save(Parser $parser, $className)
    {
        $out        = null;
        $outTokens  = null;
        $outRules   = null;
        $outPragmas = null;
        $outExtra   = null;

        $escapeRuleName = function ($ruleName) use ($parser) {
            if (true == $parser->getRule($ruleName)->isTransitional()) {
                return $ruleName;
            }

            return '\'' . $ruleName . '\'';
        };

        foreach ($parser->getTokens() as $namespace => $tokens) {
            $outTokens .= '                \'' . $namespace . '\' => [' . "\n";

            foreach ($tokens as $tokenName => $tokenValue) {
                $outTokens .=
                    '                    \'' . $tokenName . '\' => \'' .
                    str_replace(
                        ['\'', '\\\\'],
                        ['\\\'', '\\\\\\'],
                        $tokenValue
                    ) . '\',' . "\n";
            }

            $outTokens .= '                ],' . "\n";
        }

        foreach ($parser->getRules() as $rule) {
            $arguments = [];

            // Name.
            $arguments['name'] = $escapeRuleName($rule->getName());

            if ($rule instanceof Rule\Token) {
                // Token name.
                $arguments['tokenName'] = '\'' . $rule->getTokenName() . '\'';
            } else {
                if ($rule instanceof Rule\Repetition) {
                    // Minimum.
                    $arguments['min'] = $rule->getMin();

                    // Maximum.
                    $arguments['max'] = $rule->getMax();
                }

                // Children.
                $ruleChildren = $rule->getChildren();

                if (null === $ruleChildren) {
                    $arguments['children'] = 'null';
                } elseif (false === is_array($ruleChildren)) {
                    $arguments['children'] = $escapeRuleName($ruleChildren);
                } else {
                    $arguments['children'] =
                        '[' .
                        implode(', ', array_map($escapeRuleName, $ruleChildren)) .
                        ']';
                }
            }

            // Node ID.
            $nodeId = $rule->getNodeId();

            if (null === $nodeId) {
                $arguments['nodeId'] = 'null';
            } else {
                $arguments['nodeId'] = '\'' . $nodeId . '\'';
            }

            if ($rule instanceof Rule\Token) {
                // Unification.
                $arguments['unification'] = $rule->getUnificationIndex();

                // Kept.
                $arguments['kept'] = $rule->isKept() ? 'true' : 'false';
            }

            // Default node ID.
            if (null !== $defaultNodeId = $rule->getDefaultId()) {
                $defaultNodeOptions = $rule->getDefaultOptions();

                if (!empty($defaultNodeOptions)) {
                    $defaultNodeId .= ':' . implode('', $defaultNodeOptions);
                }

                $outExtra .=
                    "\n" .
                    '        $this->getRule(' . $arguments['name'] . ')->setDefaultId(' .
                        '\'' . $defaultNodeId . '\'' .
                    ');';
            }

            // PP representation.
            if (null !== $ppRepresentation = $rule->getPPRepresentation()) {
                $outExtra .=
                    "\n" .
                    '        $this->getRule(' . $arguments['name'] . ')->setPPRepresentation(' .
                        '\'' . str_replace('\'', '\\\'', $ppRepresentation) . '\'' .
                    ');';
            }

            $outRules .=
                "\n" .
                '                ' . $arguments['name'] . ' => new \\' . get_class($rule) . '(' .
                implode(', ', $arguments) .
                '),';

        }

        foreach ($parser->getPragmas() as $pragmaName => $pragmaValue) {
            $outPragmas .=
                "\n" .
                '                \'' . $pragmaName . '\' => ' .
                (is_bool($pragmaValue)
                    ? (true === $pragmaValue ? 'true' : 'false')
                    : (is_int($pragmaValue)
                        ? $pragmaValue
                        : '\'' .$pragmaValue . '\'')) .
                ',';
        }

        $out .=
            'class ' . $className . ' extends \Hoa\Compiler\Llk\Parser' . "\n" .
            '{' . "\n" .
            '    public function __construct()' . "\n" .
            '    {' . "\n" .
            '        parent::__construct(' . "\n" .
            '            [' . "\n" .
            $outTokens .
            '            ],' . "\n" .
            '            [' .
            $outRules . "\n" .
            '            ],' . "\n" .
            '            [' .
            $outPragmas . "\n" .
            '            ]' . "\n" .
            '        );' . "\n" .
            $outExtra . "\n" .
            '    }' . "\n" .
            '}' . "\n";

        return $out;
    }

    /**
     * Parse PP.
     *
     * @param   string  $pp            PP.
     * @param   array   $tokens        Extracted tokens.
     * @param   array   $rules         Extracted raw rules.
     * @param   array   $pragmas       Extracted raw pragmas.
     * @param   string  $streamName    The name of the stream that contains the grammar.
     * @return  void
     * @throws  \Hoa\Compiler\Exception
     */
    public static function parsePP($pp, &$tokens, &$rules, &$pragmas, $streamName)
    {
        $lines   = explode("\n", $pp);
        $pragmas = [];
        $tokens  = ['default' => []];
        $rules   = [];

        for ($i = 0, $m = count($lines); $i < $m; ++$i) {
            $line = rtrim($lines[$i]);

            if (0 === strlen($line) || '//' == substr($line, 0, 2)) {
                continue;
            }

            if ('%' == $line[0]) {
                if (0 !== preg_match('#^%pragma\s+([^\s]+)\s+(.*)$#u', $line, $matches)) {
                    switch ($matches[2]) {
                        case 'true':
                            $pragmaValue = true;

                            break;

                        case 'false':
                            $pragmaValue = false;

                            break;

                        default:
                            if (true === ctype_digit($matches[2])) {
                                $pragmaValue = intval($matches[2]);
                            } else {
                                $pragmaValue = $matches[2];
                            }
                    }

                    $pragmas[$matches[1]] = $pragmaValue;
                } else if (0 !== preg_match('#^%skip\s+(?:([^:]+):)?([^\s]+)\s+(.*)$#u', $line, $matches)) {
                    if (empty($matches[1])) {
                        $matches[1] = 'default';
                    }

                    if (!isset($tokens[$matches[1]])) {
                        $tokens[$matches[1]] = [];
                    }

                    if (!isset($tokens[$matches[1]]['skip'])) {
                        $tokens[$matches[1]]['skip'] = $matches[3];
                    } else {
                        $tokens[$matches[1]]['skip'] =
                            '(?:' . $matches[3] . ')|' .
                            $tokens[$matches[1]]['skip'];
                    }
                } elseif (0 !== preg_match('#^%token\s+(?:([^:]+):)?([^\s]+)\s+(.*?)(?:\s+->\s+(.*))?$#u', $line, $matches)) {
                    if (empty($matches[1])) {
                        $matches[1] = 'default';
                    }

                    if (isset($matches[4]) && !empty($matches[4])) {
                        $matches[2] = $matches[2] . ':' . $matches[4];
                    }

                    if (!isset($tokens[$matches[1]])) {
                        $tokens[$matches[1]] = [];
                    }

                    $tokens[$matches[1]][$matches[2]] = $matches[3];
                } else {
                    throw new Compiler\Exception(
                        'Unrecognized instructions:' . "\n" .
                        '    %s' . "\n" . 'in file %s at line %d.',
                        1,
                        [
                            $line,
                            $streamName,
                            $i + 1
                        ]
                    );
                }

                continue;
            }

            $ruleName = substr($line, 0, -1);
            $rule     = null;
            ++$i;

            while ($i < $m &&
                   isset($lines[$i][0]) &&
                   (' '  === $lines[$i][0] ||
                    "\t" === $lines[$i][0] ||
                    '//' === substr($lines[$i], 0, 2))) {
                if ('//' === substr($lines[$i], 0, 2)) {
                    ++$i;

                    continue;
                }

                $rule .= ' ' . trim($lines[$i++]);
            }

            if (isset($lines[$i][0])) {
                --$i;
            }

            $rules[$ruleName] = $rule;
        }

        return;
    }
}

/**
 * Flex entity.
 */
Consistency::flexEntity('Hoa\Compiler\Llk\Llk');
