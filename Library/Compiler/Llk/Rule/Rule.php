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

namespace Hoa\Compiler\Llk\Rule {

/**
 * Class \Hoa\Compiler\Llk\Rule.
 *
 * Rule parent.
 *
 * @author     Frédéric Dadeau <frederic.dadeau@femto-st.fr>
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2012 Frédéric Dadeau, Ivan Enderlin.
 * @license    New BSD License
 */

abstract class Rule {

    /**
     * Rule name.
     *
     * @var \Hoa\Compiler\Llk\Rule string
     */
    protected $_name    = null;

    /**
     * Rule content.
     *
     * @var \Hoa\Compiler\Llk\Rule mixed
     */
    protected $_content = null;

    protected $_nodeId  = null;



    /**
     * Constructor.
     *
     * @access  public
     * @param   string  $name       Name.
     * @param   mixed   $content    Content.
     * @param   string  $nodeId     Node ID.
     * @return  void
     */
    public function __construct ( $name, $content, $nodeId = null ) {

        $this->_name    = $name;
        $this->_content = $content;
        $this->_nodeId  = $nodeId;

        return;
    }

    /**
     * Set rule name.
     *
     * @access  public
     * @param   string  $name    Rule name.
     * @return  string
     */
    public function setName ( $name ) {

        $old         = $this->_name;
        $this->_name = $name;

        return $old;
    }

    /**
     * Get rule name.
     *
     * @access  public
     * @return  string
     */
    public function getName ( ) {

        return $this->_name;
    }

    /**
     * Get rule content.
     *
     * @access  public
     * @return  string
     */
    public function getContent ( ) {

        return $this->_content;
    }

    public function setNodeId ( $nodeId ) {

        $old           = $this->_nodeId;
        $this->_nodeId = $nodeId;

        return $old;
    }

    public function getNodeId ( ) {

        return $this->_nodeId;
    }
}

}
