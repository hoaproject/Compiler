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
 * Copyright (c) 2007, 2010 Ivan ENDERLIN. All rights reserved.
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
 */

namespace {

from('Hoa')

/**
 * \Hoa\Compiler\Exception
 */
-> import('Compiler.Exception');

}

namespace Hoa\Compiler\Exception {

/**
 * Class \Hoa\Compiler\Exception\IllegalToken.
 *
 * Extending the \Hoa\Compiler\Exception class.
 *
 * @author     Ivan ENDERLIN <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright (c) 2007, 2010 Ivan ENDERLIN.
 * @license    http://gnu.org/licenses/gpl.txt GNU GPL
 */

class IllegalToken extends Exception {

    /**
     * Column.
     *
     * @var \Hoa\Compiler\Exception\IllegalToken int
     */
    protected $column = 0;



    /**
     * Override line and add column support.
     *
     * @access  public
     * @param   string  $message    Formatted message.
     * @param   int     $code       Code (the ID).
     * @param   array   $arg        RaiseError string arguments.
     * @param   int     $line       Line.
     * @param   int     $column     Column.
     * @return  void
     */
    public function __construct ( $message, $code, $arg, $line, $column ) {

        parent::__construct($message, $code, $arg);

        $this->line   = $line;
        $this->column = $column;

        return;
    }

    /**
     * Get column.
     *
     * @access  public
     * @return  int
     */
    public function getColumn ( ) {

        return $this->column;
    }
}

}
