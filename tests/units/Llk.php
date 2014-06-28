<?php

namespace Hoa\Compiler\tests\units;

use Hoa\Compiler\Llk\Llk as LlkTest;
use Hoa\File\Read;
use mageekguy\atoum;

class Llk extends atoum\test {

    private function pathsFromFile($pp) {
        $compiler = LlkTest::load(new Read($pp));

        $paths = $compiler->getRulePathsFromRoot();
        $pathStrings = $compiler->getTokenPaths($paths);

        return $this->getOutput($pathStrings);
    }

    private function getOutput(array $pathStrings) {
        $i = 0;
        $output = [];
        foreach ($pathStrings as $strings) {
            $output[] = ++$i . ': ' .  implode(' > ', $strings) .  PHP_EOL;
        }
        return $output;
    }

    public function testOneItem() {
        $output = $this->pathsFromFile(__DIR__ . '/../pp/oneItem.pp');
        $this->string($output[0])->isEqualTo('1: Hello' . PHP_EOL);
    }

    public function testTwoItem() {
        $output = $this->pathsFromFile(__DIR__ . '/../pp/concatenation.pp');
        $this->string($output[0])->isEqualTo('1: Hello > world!' . PHP_EOL);
    }

    public function testSubRule() {
        $output = $this->pathsFromFile(__DIR__ . '/../pp/subRule.pp');
        $this->string($output[0])->isEqualTo('1: Hello' . PHP_EOL);
    }

    public function testChoice() {
        $output = $this->pathsFromFile(__DIR__ . '/../pp/choice.pp');
        $this->string($output[0])->isEqualTo('1: Hello' . PHP_EOL);
        $this->string($output[1])->isEqualTo('2: Goodbye' . PHP_EOL);
    }

    public function testRepetition() {
        $output = $this->pathsFromFile(__DIR__ . '/../pp/repetition.pp');
        $this->string($output[0])->isEqualTo('1: Hello *' . PHP_EOL);
    }

    public function testWorld() {
        $output = $this->pathsFromFile(__DIR__ . '/../pp/world.pp');
        $this->string($output[0])->isEqualTo('1: Hello > world!' . PHP_EOL);
        $this->string($output[1])->isEqualTo('2: Goodbye > world!' . PHP_EOL);
    }

    public function testCircularReference1() {
        $output = $this->pathsFromFile(__DIR__ . '/../pp/circularReference1.pp');
        $this->string($output[0])->isEqualTo('1: * Circular Reference for index "greeting" *' . PHP_EOL);
        $this->string($output[1])->isEqualTo('2: Hello' . PHP_EOL);
    }

    public function testCircularReference2() {
        $output = $this->pathsFromFile(__DIR__ . '/../pp/circularReference2.pp');
        $this->string($output[0])->isEqualTo('1: * Circular Reference for index "greeting" * > world!' . PHP_EOL);
        $this->string($output[1])->isEqualTo('2: Goodbye > world!' . PHP_EOL);
    }

    public function testRuleRepetition() {
        $output = $this->pathsFromFile(__DIR__ . '/../pp/ruleRepetition.pp');
        $this->string($output[0])->isEqualTo('1: Hello {2, 3}' . PHP_EOL);
    }

    public function testGroupRepetition() {
        $output = $this->pathsFromFile(__DIR__.'/../pp/functionRepetition.pp');
        $this->string($output[0])->isEqualTo('1: hello {2, 3}'.PHP_EOL);
    }

    public function testGroupRepetitionAsNode() {
        $compiler = LlkTest::load(new Read(__DIR__.'/../pp/groupRepetition.pp'));
        $paths = $compiler->getRulePathsFromRoot();
        $pathStrings = $compiler->getTokenPaths($paths, Parser::TOKEN_NAME, true);
        $output = $this->getOutput($pathStrings);
        $this->string($output[0])->isEqualTo('1: ( > hello > world > ) > {2, 2}'.PHP_EOL);
    }

}
