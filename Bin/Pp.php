<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2014, Ivan Enderlin. All rights reserved.
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
 * \Hoa\Compiler\LLk
 */
-> import('Compiler.Llk.~')

/**
 * \Hoa\File\Read
 */
-> import('File.Read');

}

namespace Hoa\Compiler\Bin {

/**
 * Class Hoa\Compiler\Bin\Pp.
 *
 * Play with PP.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2014 Ivan Enderlin.
 * @license    New BSD License
 */

class Pp extends \Hoa\Console\Dispatcher\Kit {

    /**
     * Options description.
     *
     * @var \Hoa\Compiler\Bin\Pp array
     */
    protected $options = array(
        array('visitor',        \Hoa\Console\GetOption::REQUIRED_ARGUMENT, 'v'),
        array('visitor-class',  \Hoa\Console\GetOption::REQUIRED_ARGUMENT, 'c'),
        array('token-sequence', \Hoa\Console\GetOption::NO_ARGUMENT,       's'),
        array('trace',          \Hoa\Console\GetOption::NO_ARGUMENT,       't'),
        array('help',           \Hoa\Console\GetOption::NO_ARGUMENT,       'h'),
        array('help',           \Hoa\Console\GetOption::NO_ARGUMENT,       '?')
    );



    /**
     * The entry method.
     *
     * @access  public
     * @return  int
     */
    public function main ( ) {

        $visitor       = null;
        $tokenSequence = false;
        $trace         = false;

        while(false !== $c = $this->getOption($v)) switch($c) {

            case 'v':
                switch(strtolower($v)) {

                    case 'dump':
                        $visitor = 'Hoa\Compiler\Visitor\Dump';
                      break;

                    default:
                        return $this->usage();
                }
              break;

            case 'c':
                $visitor = str_replace('.', '\\', $v);
              break;

            case 's':
                $tokenSequence = true;
              break;

            case 't':
                $trace = true;
              break;

            case '__ambiguous':
                $this->resolveOptionAmbiguity($v);
              break;

            case 'h':
            case '?':
            default:
                return $this->usage();
              break;
        }

        $this->parser->listInputs($grammar, $language);

        if(empty($grammar) || (empty($language) && '0' !== $language))
            return $this->usage();

        $compiler = \Hoa\Compiler\Llk::load(
            new \Hoa\File\Read($grammar)
        );
        $data     = new \Hoa\File\Read($language);

        try {

            $ast = $compiler->parse($data->readAll());
        }
        catch ( \Hoa\Compiler\Exception $e ) {

            if(true === $tokenSequence) {

                $this->printTokenSequence($compiler);
                echo "\n\n";
            }

            throw $e;

            return 1;
        }

        if(true === $tokenSequence) {

            $this->printTokenSequence($compiler);
            echo "\n\n";
        }

        if(true === $trace) {

            $this->printTrace($compiler);
            echo "\n\n";
        }

        if(null !== $visitor) {

            $visitor = dnew($visitor);
            echo $visitor->visit($ast);
        }

        return;
    }

    /**
     * Print trace.
     *
     * @access  protected
     * @param   \Hoa\Compiler\Llk\Parser  $compiler    Compiler.
     * @return  void
     */
    protected function printTrace ( \Hoa\Compiler\Llk\Parser $compiler ) {

        $i = 0;

        foreach($compiler->getTrace() as $element)
            if($element instanceof \Hoa\Compiler\Llk\Rule\Entry) {

                $ruleName = $element->getRule();
                $rule     = $compiler->getRule($ruleName);

                echo str_repeat('>  ', ++$i), 'enter ', $ruleName;

                if(null !== $id = $rule->getNodeId())
                    echo ' (', $id, ')';

                echo "\n";
            }
            elseif($element instanceof \Hoa\Compiler\Llk\Rule\Token)
                echo str_repeat('   ', $i + 1), 'token ',$element->getTokenName(),
                    ', consumed ', $element->getValue(), "\n";
            else
                echo str_repeat('<  ', $i--), 'ekzit ', $element->getRule(), "\n";

        return;
    }

    /**
     * Print token sequence.
     *
     * @access  protected
     * @param   \Hoa\Compiler\Llk\Parser  $compiler    Compiler.
     * @return  void
     */
    protected function printTokenSequence ( \Hoa\Compiler\Llk\Parser $compiler ) {

        $sequence = $compiler->getTokenSequence();
        $format   = '%' . (strlen((string) count($sequence)) + 1) . 's  ' .
                    '%-13s %-20s  %s  %6s' . "\n";

        $header = sprintf(
            $format,
            '#',
            'namespace',
            'token name',
            'token value                   ',
            'offset'
        );

        echo $header, str_repeat('-', strlen($header)), "\n";

        foreach($sequence as $i => $token)
            printf(
                $format,
                $i,
                $token['namespace'],
                $token['token'],
                30 < $token['length']
                    ? mb_substr($token['value'], 0, 29) . '…'
                    : 'EOF' === $token['token']
                           ? str_repeat(' ', 30)
                           : $token['value'] .
                             str_repeat(' ', 30 - $token['length']),
                $token['offset']
            );

        return;
    }

    /**
     * The command usage.
     *
     * @access  public
     * @return  int
     */
    public function usage ( ) {

        echo 'Usage   : compiler:pp <options> [grammar.pp] [language]', "\n",
             'Options :', "\n",
             $this->makeUsageOptionsList(array(
                 'v'    => 'Visitor name (only "dump" is supported).',
                 'c'    => 'Visitor classname (using . instead of \ works).',
                 's'    => 'Print token sequence.',
                 't'    => 'Print trace.',
                 'help' => 'This help.'
             )), "\n";

        return;
    }
}

}

__halt_compiler();
Compile and visit languages with grammars.
