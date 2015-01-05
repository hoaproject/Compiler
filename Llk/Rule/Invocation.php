<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2015, Ivan Enderlin. All rights reserved.
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

/**
 * Class \Hoa\Compiler\Llk\Rule\Invocation.
 *
 * Parent of entry and ekzit rules.
 *
 * @author     Frédéric Dadeau <frederic.dadeau@femto-st.fr>
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2015 Frédéric Dadeau, Ivan Enderlin.
 * @license    New BSD License
 */

abstract class Invocation {

    /**
     * Rule.
     *
     * @var \Hoa\Compiler\Llk\Rule\Invocation string
     */
    protected $_rule         = null;

    /**
     * Data.
     *
     * @var \Hoa\Compiler\Llk\Rule\Invocation mixed
     */
    protected $_data         = null;

    /**
     * Piece of todo sequence.
     *
     * @var \Hoa\Compiler\Llk\Rule\Invocation array
     */
    protected $_todo         = null;

    /**
     * Depth in the trace.
     *
     * @var \Hoa\Compiler\Llk\Rule\Invocation int
     */
    protected $_depth        = -1;

    /**
     * Whether the rule is transitional or not (i.e. not declared in the grammar
     * but created by the analyzer).
     *
     * @var \Hoa\Compiler\Llk\Rule\Invocation bool
     */
    protected $_transitional = false;



    /**
     * Constructor.
     *
     * @access  public
     * @param   string  $rule     Rule name.
     * @param   mixed   $data     Data.
     * @param   array   $todo     Todo.
     * @param   int     $depth    Depth.
     * @return  void
     */
    public function __construct ( $rule, $data, Array $todo = null,
                                  $depth = -1 ) {

        $this->_rule         = $rule;
        $this->_data         = $data;
        $this->_todo         = $todo;
        $this->_depth        = $depth;
        $this->_transitional = is_numeric($rule);

        return;
    }

    /**
     * Get rule name.
     *
     * @access  public
     * @return  string
     */
    public function getRule ( ) {

        return $this->_rule;
    }

    /**
     * Get data.
     *
     * @access  public
     * @return  mixed
     */
    public function getData ( ) {

        return $this->_data;
    }

    /**
     * Get todo sequence.
     *
     * @access  public
     * @return  array
     */
    public function getTodo ( ) {

        return $this->_todo;
    }

    /**
     * Set depth in trace.
     *
     * @access  public
     * @parma   int  $depth    Depth.
     * @return  int
     */
    public function setDepth ( $depth) {

        $old          = $this->_depth;
        $this->_depth = $depth;

        return $old;
    }

    /**
     * Get depth in trace.
     *
     * @access  public
     * @return  int
     */
    public function getDepth ( ) {

        return $this->_depth;
    }

    /**
     * Check whether the rule is transitional or not.
     *
     * @access  public
     * @return  bool
     */
    public function isTransitional ( ) {

        return $this->_transitional;
    }
}
