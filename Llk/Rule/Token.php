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
 * \Hoa\Compiler\Llk\Rule
 */
-> import('Compiler.Llk.Rule.~')

/**
 * \Hoa\File\Read
 */
-> import('File.Read')

/**
 * \Hoa\Compiler\Llk
 */
-> import('Compiler.Llk.~');

}

namespace Hoa\Compiler\Llk\Rule {

/**
 * Class \Hoa\Compiler\Llk\Rule\Token.
 *
 * The token rule.
 *
 * @author     Frédéric Dadeau <frederic.dadeau@femto-st.fr>
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2013 Frédéric Dadeau, Ivan Enderlin.
 * @license    New BSD License
 */

class Token extends Rule {

    /**
     * LL(k) compiler of hoa://Library/Regex/Grammar.pp
     *
     * @var \Hoa\Compiler\Llk object
     */
    protected static $_regexCompiler = null;

    /**
     * Token name.
     *
     * @var \Hoa\Compiler\Llk\Rule\Token string
     */
    protected $_tokenName            = null;

    /**
     * Token representation.
     *
     * @var \Hoa\Compiler\Llk\Rule\Token string
     */
    protected $_regex                = null;

    /**
     * AST of the regex.
     *
     * @var \Hoa\Compiler\Llk\TreeNode object
     */
    protected $_ast                  = null;

    /**
     * Token value.
     *
     * @var \Hoa\Compiler\Llk\Rule\Token string
     */
    protected $_value                = null;

    /**
     * Whether the token is kept or not in the AST.
     *
     * @var \Hoa\Compiler\Llk\Rule\Token bool
     */
    protected $_kept                 = false;

    /**
     * Unification index.
     *
     * @var \Hoa\Compiler\Llk\Rule\Token int
     */
    protected $_unification          = -1;



    /**
     * Constructor.
     *
     * @access  public
     * @param   string  $name           Name.
     * @param   string  $tokenName      Token name.
     * @param   string  $regex          Representation.
     * @param   string  $nodeId         Node ID.
     * @param   int     $unification    Unification index.
     * @return  void
     */
    public function __construct ( $name, $tokenName, $regex, $nodeId,
                                  $unification ) {

        parent::__construct($name, null, $nodeId);
        $this->_tokenName   = $tokenName;
        $this->_regex       = $regex;
        $this->_unification = $unification;

        return;
    }

    /**
     * Get token name.
     *
     * @access  public
     * @return  string
     */
    public function getTokenName ( ) {

        return $this->_tokenName;
    }

   /**
     * Set token representation.
     *
     * @access  public
     * @param   string  $regex    Representation.
     * @return  string
     */
    public function setRepresentation ( $regex ) {

        $old          = $this->_regex;
        $this->_regex = $regex;
        unset($this->_ast);

        return $old;
    }

    /**
     * Get token representation.
     *
     * @access  public
     * @return  string
     */
    public function getRepresentation ( ) {

        return $this->_regex;
    }

    /**
     * Get AST of the token representation.
     *
     * @access  public
     * @return  \Hoa\Compiler\Llk\TreeNode
     */
    public function getAST ( ) {

        if(null === static::$_regexCompiler)
            static::$_regexCompiler = \Hoa\Compiler\Llk::load(
                new \Hoa\File\Read('hoa://Library/Regex/Grammar.pp')
            );

        if(null === $this->_ast)
            $this->_ast = static::$_regexCompiler->parse(
                $this->getRepresentation()
            );

        return $this->_ast;
    }

    /**
     * Set token value.
     *
     * @access  public
     * @param   string  $value    Value.
     * @return  string
     */
    public function setValue ( $value ) {

        $old          = $this->_value;
        $this->_value = $value;

        return $old;
    }

    /**
     * Get token value.
     *
     * @access  public
     * @return  string
     */
    public function getValue ( ) {

        return $this->_value;
    }

    /**
     * Set whether the token is kept or not in the AST.
     *
     * @access  public
     * @param   bool  $kept    Kept.
     * @return  bool
     */
    public function setKept ( $kept ) {

        $old         = $this->_kept;
        $this->_kept = $kept;

        return $old;
    }

    /**
     * Check whether the token is kept in the AST or not.
     *
     * @access  public
     * @return  bool
     */
    public function isKept ( ) {

        return $this->_kept;
    }

    /**
     * Set unification index.
     *
     * @access  public
     * @param   int  $unification    Unification index.
     * @return  int
     */
    public function setUnificationIndex ( $unification ) {

        $old                = $this->_unification;
        $this->_unification = $unification;

        return $old;
    }

    /**
     * Get unification index.
     *
     * @access  public
     * @return  int
     */
    public function getUnificationIndex ( ) {

        return $this->_unification;
    }
}

}
