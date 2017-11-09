<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2017, Hoa community. All rights reserved.
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

namespace Hoa\Compiler\Exception;

/**
 * Class \Hoa\Compiler\Exception.
 *
 * Extending the \Hoa\Exception\Exception class.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */
trait ExceptionHelper
{
    /**
     * This is an auxiliary method that returns the line number, shift,
     * and line of code by shift relative to the beginning of the file.
     *
     * @param   string $text        The source code
     * @param   int    $bytesOffset Offset in bytes
     * @return  array
     */
    protected static function getErrorPositionByOffset($text, $bytesOffset)
    {
        $result = self::getErrorInfo($text, $bytesOffset);
        $code   = self::getAffectedCodeAsString($result['trace']);

        $column = self::getMbColumnPosition($code, $result['column']);

        return [
            'line'      => $result['line'],
            'code'      => $code,
            'column'    => $column,
            'highlight' => self::getStringHighligher($column),
        ];
    }

    /**
     * Returns the last line with an error. If the error occurred on
     * the line where there is no visible part, before complements
     * it with the previous ones.
     *
     * @param array|string[] $textLines List of code lines
     * @return string
     */
    private static function getAffectedCodeAsString(array $textLines)
    {
        $result = '';
        $i = 0;

        while (\count($textLines) && ++$i) {
            $textLine = \array_pop($textLines);
            $result   = $textLine . ($i > 1 ? "\n" . $result : '');

            if (\trim($textLine)) {
                break;
            }
        }

        return $result;
    }

    /**
     * The method draws the highlight of the error place.
     *
     * @param  int $charsOffset Error offset in symbols
     * @return string
     */
    private static function getStringHighligher($charsOffset)
    {
        $prefix = '';

        if ($charsOffset > 0) {
            $prefix = \str_repeat(' ', $charsOffset);
        }

        return $prefix . '↑';
    }

    /**
     * Returns the error location in UTF characters by the offset in bytes.
     *
     * @param  string $line        The code line from which we get a offset in the characters
     * @param  int    $bytesOffset Length of offset in bytes
     * @return int
     */
    private static function getMbColumnPosition($line, $bytesOffset)
    {
        $slice = \substr($line, 0, $bytesOffset);

        return \mb_strlen($slice, 'UTF-8');
    }

    /**
     * Returns information about the error location: line, column and affected text lines.
     *
     * @param string $text        The source code in which we search for a line and a column
     * @param int    $bytesOffset Offset in bytes relative to the beginning of the source code
     * @return array
     */
    private static function getErrorInfo($text, $bytesOffset)
    {
        $result = [
            'line'   => 1,
            'column' => 0,
            'trace'  => [],
        ];

        $current = 0;

        foreach (\explode("\n", $text) as $line => $code) {
            $previous = $current;
            $current += \strlen($code) + 1;
            $result['trace'][] = $code;

            if ($current > $bytesOffset) {
                return [
                    'line'   => $line + 1,
                    'column' => $bytesOffset - $previous,
                    'trace'  => $result['trace']
                ];
            }
        }

        return $result;
    }
}
