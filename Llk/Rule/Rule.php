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

namespace Hoa\Compiler\Llk\Rule {

/**
 * Class \Hoa\Compiler\Llk\Rule.
 *
 * Rule parent.
 *
 * @author     Frédéric Dadeau <frederic.dadeau@femto-st.fr>
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2014 Frédéric Dadeau, Ivan Enderlin.
 * @license    New BSD License
 */

abstract class Rule {

    /**
     * Rule name.
     *
     * @var \Hoa\Compiler\Llk\Rule string
     */
    protected $_name           = null;

    /**
     * Rule content.
     *
     * @var \Hoa\Compiler\Llk\Rule mixed
     */
    protected $_content        = null;

    /**
     * Node ID.
     *
     * @var \Hoa\Compiler\Llk\Rule string
     */
    protected $_nodeId         = null;

    /**
     * Node options.
     *
     * @var \Hoa\Compiler\Llk\Rule array
     */
    protected $_nodeOptions    = array();

    /**
     * Default ID.
     *
     * @var \Hoa\Compiler\Llk\Rule string
     */
    protected $_defaultId      = null;

    /**
     * Default options.
     *
     * @var \Hoa\Compiler\Llk\Rule array
     */
    protected $_defaultOptions = array();

    /**
     * For non-transitional rule: PP representation.
     *
     * @var \Hoa\Compiler\Llk\Rule string
     */
    protected $_pp             = null;
    /**
     * Whether the rule is transitional or not (i.e. not declared in the grammar
     * but created by the analyzer).
     *
     * @var \Hoa\Compiler\Llk\Rule bool
     */
    protected $_transitional   = true;



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

        $this->setName($name);
        $this->setContent($content);
        $this->setNodeId($nodeId);

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
     * Set rule content.
     *
     * @access  public
     * @return  mixed
     */
    protected function setContent ( $content ) {

        $old            = $this->_content;
        $this->_content = $content;

        return $old;
    }

    /**
     * Get rule content.
     *
     * @access  public
     * @return  mixed
     */
    public function getContent ( ) {

        return $this->_content;
    }

    /**
     * Set node ID.
     *
     * @access  public
     * @param   string  $nodeId    Node ID.
     * @return  string
     */
    public function setNodeId ( $nodeId ) {

        $old = $this->_nodeId;

        if(false !== $pos = strpos($nodeId, ':')) {

            $this->_nodeId      = substr($nodeId, 0, $pos);
            $this->_nodeOptions = str_split(substr($nodeId, $pos + 1));
        }
        else {

            $this->_nodeId      = $nodeId;
            $this->_nodeOptions = array();
        }

        return $old;
    }

    /**
     * Get node ID.
     *
     * @access  public
     * @return  string
     */
    public function getNodeId ( ) {

        return $this->_nodeId;
    }

    /**
     * Get node options.
     *
     * @access  public
     * @retrun  array
     */
    public function getNodeOptions ( ) {

        return $this->_nodeOptions;
    }

    /**
     * Set default ID.
     *
     * @access  public
     * @param   string  $defaultId    Default ID.
     * @return  string
     */
    public function setDefaultId ( $defaultId ) {

        $old = $this->_defaultId;

        if(false !== $pos = strpos($defaultId, ':')) {

            $this->_defaultId      = substr($defaultId, 0, $pos);
            $this->_defaultOptions = str_split(substr($defaultId, $pos + 1));
        }
        else {

            $this->_defaultId      = $defaultId;
            $this->_defaultOptions = array();
        }

        return $old;
    }

    /**
     * Get default ID.
     *
     * @access  public
     * @return  string
     */
    public function getDefaultId ( ) {

        return $this->_defaultId;
    }

    /**
     * Get default options.
     *
     * @access  public
     * @return  array
     */
    public function getDefaultOptions ( ) {

        return $this->_defaultOptions;
    }

    /**
     * Set PP representation of the rule.
     *
     * @access  public
     * @param   string  $pp    PP representation.
     * @return  string
     */
    public function setPPRepresentation ( $pp ) {

        $old                 = $this->_pp;
        $this->_pp           = $pp;
        $this->_transitional = false;

        return $old;
    }

    /**
     * Get PP representation of the rule.
     *
     * @access  public
     * @return  string
     */
    public function getPPRepresentation ( ) {

        return $this->_pp;
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

}

namespace {

/**
 * Flex entity.
 */
Hoa\Core\Consistency::flexEntity('Hoa\Compiler\Llk\Rule\Rule');

}
