<?php

namespace Hoa\Compiler\Visitor\Trace {

abstract class RuleInvocation {

    protected $_rule = null;
    protected $_data = null;
    protected $_todo = null;



    public function __construct ( $rule, $data, $todo ) {

        $this->_rule = $rule;
        $this->_data = $data;
        $this->_todo = $todo;

        return;
    }

    public function getRule ( ) {

        return $this->_rule;
    }

    public function setData ( $data ) {

        $old         = $this->_data;
        $this->_data = $data;

        return $old;
    }

    public function getData ( ) {

        return $this->_data;
    }

    public function getTodo ( ) {

        return $this->_todo;
    }
}

}
