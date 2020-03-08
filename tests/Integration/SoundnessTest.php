<?php
/**
 * Hoa
 *
 *
 * @license
 *
 * BSD 3-Clause License
 *
 * Copyright © 2007-2017, Hoa community. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this
 *    list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its
 *    contributors may be used to endorse or promote products derived from
 *    this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace Tests\Hoa\Compiler\Integration;

use Hoa\Compiler as LUT;
use Hoa\Compiler\Llk\Sampler;
use Hoa\Iterator;
use Hoa\Math;
use Hoa\Regex;
use Tests\Hoa\Compiler\TestCase;

/**
 * Check soundness of the LL(k) compiler.
 *
 * @coversNothing
 */
class SoundnessTest extends TestCase
{
    private function generatorFromSampler(Sampler $sampler)
    {
        $compiler = $sampler->getCompiler();

        $count = 0;

        foreach ($sampler as $datum) {
            yield [$datum, $compiler];

            $count += 1;

            if ($count > 100) {
                break;
            } // TODO
        }
    }

    private function jsonParse(string $datum, \Hoa\Compiler\Llk\Parser $compiler)
    {
        $this
            ->given(json_decode($datum))
            ->when($error = json_last_error())
            ->then
                ->integer($error)
                    ->isEqualTo(JSON_ERROR_NONE)
            ->when($result = $compiler->parse($datum, null, false))
            ->then
                ->boolean($result)
                    ->isTrue();
    }

    public function boundedExhaustiveSampler()
    {
        return $this->generatorFromSampler(new LUT\Llk\Sampler\BoundedExhaustive(
            $this->getJSONCompiler(),
            $this->getRegexSampler(),
            12
        ));
    }

    /**
     * @dataProvider boundedExhaustiveSampler
     */
    public function test_exaustive_json(string $datum, \Hoa\Compiler\Llk\Parser $compiler)
    {
        $this->jsonParse($datum, $compiler);
    }

    public function coverageSampler()
    {
        return $this->generatorFromSampler(new LUT\Llk\Sampler\Coverage(
            $this->getJSONCompiler(),
            $this->getRegexSampler()
        ));
    }

    /**
     * @dataProvider coverageSampler
     */
    public function test_coverage_json(string $datum, \Hoa\Compiler\Llk\Parser $compiler)
    {
        $this->jsonParse($datum, $compiler);
    }


    /**
     * @test
     */
    public function case_uniform_random_json()
    {
        $this
        ->given(
            $sampler = new LUT\Llk\Sampler\Uniform(
                $this->getJSONCompiler(),
                $this->getRegexSampler(),
                5
            )
        )
            ->with_json(
                new Iterator\Limit(
                    new Iterator\CallbackGenerator(function () use ($sampler) {
                        return $sampler->uniform();
                    }),
                    0,
                    1000
                ),
                $sampler->getCompiler()
            );
    }

    public function with_json($sampler, $compiler = null)
    {
        if (null === $compiler) {
            $compiler = $sampler->getCompiler();
        }

        $this
        ->when(function () use ($compiler, $sampler) {
            foreach ($sampler as $datum) {
                $this
                ->given(json_decode($datum))
                ->executeOnFailure(function () use ($datum) {
                    if (true === function_exists('json_last_error_msg')) {
                        echo
                        'Data:  ' . $datum, "\n",
                        'Error: ' . json_last_error_msg(), "\n";
                    }
                })
                ->when($error = json_last_error())
                ->then
                ->integer($error)
                ->isEqualTo(JSON_ERROR_NONE)

                ->when($result = $compiler->parse($datum, null, false))
                ->then
                ->boolean($result)
                ->isTrue();
            }
        });
    }

    protected function getJSONCompiler()
    {
        return LUT\Llk::load(file_get_contents(dirname(__DIR__) . '/Fixtures/Grammar/Json.pp'));
    }

    protected function getRegexSampler()
    {
        if (!class_exists(Regex\Visitor\Isotropic::class)) {
            $this->markTestSkipped(sprintf("%s is not loaded", Regex\Visitor\Isotropic::class));
        }

        if (!class_exists(Math\Sampler\Random::class)) {
            $this->markTestSkipped(sprintf("%s is not loaded", Math\Sampler\Random::class));
        }

        return new Regex\Visitor\Isotropic(new Math\Sampler\Random());
    }
}
