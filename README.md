![Hoa](http://static.hoa-project.net/Image/Hoa_small.png)

Hoa is a **modular**, **extensible** and **structured** set of PHP libraries.
Moreover, Hoa aims at being a bridge between industrial and research worlds.

# Hoa\Compiler ![state](http://central.hoa-project.net/State/Compiler)

This library allows to manipulate LL(1) and LL(k) compiler compilers. A
dedicated grammar language is provided for the last one: the PP language.

## Installation

With [Composer](http://getcomposer.org/), to include this library into your
dependencies, you need to require
[`hoa/compiler`](https://packagist.org/packages/hoa/compiler):

```json
{
    "require": {
        "hoa/compiler": "~2.0"
    }
}
```

Please, read the website to [get more informations about how to
install](http://hoa-project.net/Source.html).

## Quick usage

As a quick overview, we will look at the PP language and the LL(k) compiler
compiler.

### The PP language

A grammar is constituted by tokens (the units of a word) and rules (please, see
the documentation for an introduction to the language theory). The PP language
declares tokens with the following construction:

```
%token [namespace:]name value [-> namespace]
```

The default namespace is `default`. The value of a token is represented by a
[PCRE](http://pcre.org/). We can skip tokens with the `%skip` construction.

As an example, we will take the *simplified* grammar of the [JSON
language](http://json.org/). The complete grammar is in the
`hoa://Library/Json/Grammar.pp` file. Thus:

```
%skip   space          \s
// Scalars.
%token  true           true
%token  false          false
%token  null           null
// Strings.
%token  quote_         "        -> string
%token  string:string  [^"]+
%token  string:_quote  "        -> default
// Objects.
%token  brace_         {
%token _brace          }
// Arrays.
%token  bracket_       \[
%token _bracket        \]
// Rest.
%token  colon          :
%token  comma          ,
%token  number         \d+

value:
    <true> | <false> | <null> | string() | object() | array() | number()

string:
    ::quote_:: <string> ::_quote::

number:
    <number>

#object:
    ::brace_:: pair() ( ::comma:: pair() )* ::_brace::

#pair:
    string() ::colon:: value()

#array:
    ::bracket_:: value() ( ::comma:: value() )* ::_bracket::
```

We can see the PP constructions:

  * `rule()` to call a rule;
  * `<token>` and `::token::` to declare a token;
  * `|` for a disjunction;
  * `(…)` to group multiple declarations;
  * `e?` to say that `e` is optional;
  * `e+` to say that `e` can appear at least 1 time;
  * `e*` to say that `e` can appear 0 or many times;
  * `e{x,y}` to say that `e` can appear between `x` and `y` times;
  * `#node` to create a node the AST (resulting tree);
  * `token[i]` to unify tokens value between them.

Unification is very useful. For example, if we have a token that expresses a
quote (simple or double), we could have:

```
%token  quote   "|'
%token  handle  \w+

string:
    ::quote:: <handle> ::quote::
```

So, the data `"foo"` and `'foo'` will be valid, but also `"foo'` and `'foo"`! To
avoid this, we can add a new constraint on token value by unifying them, thus:

```
string:
    ::quote[0]:: <handle> ::quote[0]::
```

All `quote[0]` for the rule instance must have the same value. Another example
is the unification of XML tags name.

### LL(k) compiler compiler

The `Hoa\Compiler\Llk\Llk` class will transform a grammar into a compiler. The
following code will use the previous grammar to create a compiler, and we will
parse a JSON string. If the parsing succeed, it will produce an AST (stands for
Abstract Syntax Tree) we can visit, for example to dump the AST:

```php
// 1. Load grammar.
$compiler = Hoa\Compiler\Llk\Llk::load(new Hoa\File\Read('Json.pp'));

// 2. Parse a data.
$ast      = $compiler->parse('{"foo": true, "bar": [null, 42]}');

// 3. Dump the AST.
$dump     = new Hoa\Compiler\Visitor\Dump();
echo $dump->visit($ast);

/**
 * Will output:
 *     >  #object
 *     >  >  #pair
 *     >  >  >  token(string, foo)
 *     >  >  >  token(true, true)
 *     >  >  #pair
 *     >  >  >  token(string, bar)
 *     >  >  >  #array
 *     >  >  >  >  token(null, null)
 *     >  >  >  >  token(number, 42)
 */
```

Pretty simple.

### Compiler in CLI

This library proposes a script to parse and apply a visitor on a data with a
specific grammar. Very useful. Moreover, we can use pipe (because
`Hoa\File\Read` —please, see the [`Hoa\File`
library](http://central.hoa-project.net/Resource/Library/File/)— supports `0` as
`stdin`), thus:

```sh
$ echo '[1, [1, [2, 3], 5], 8]' | hoa compiler:pp Json.pp 0 --visitor dump
>  #array
>  >  token(number, 1)
>  >  #array
>  >  >  token(number, 1)
>  >  >  #array
>  >  >  >  token(number, 2)
>  >  >  >  token(number, 3)
>  >  >  token(number, 5)
>  >  token(number, 8)
```

You can apply any visitor classes.

### Errors

Errors are well-presented:

```sh
$ echo '{"foo" true}' | hoa compiler:pp Json.pp 0 --visitor dump
Uncaught exception (Hoa\Compiler\Exception\UnexpectedToken):
Hoa\Compiler\Llk\Parser::parse(): (0) Unexpected token "true" (true) at line 1
and column 8:
{"foo" true}
       ↑
in hoa://Library/Compiler/Llk/Parser.php at line 1
```

### Samplers

Some algorithms are available to generate data based on a grammar. We will give
only one example with the coverage-based generation algorithm that will activate
all branches and tokens in the grammar:

```php
$sampler = new Hoa\Compiler\Llk\Sampler\Coverage(
    // Grammar.
    Hoa\Compiler\Llk\Llk::load(new Hoa\File\Read('Json.pp')),
    // Token sampler.
    new Hoa\Regex\Visitor\Isotropic(new Hoa\Math\Sampler\Random())
);

foreach ($sampler as $i => $data) {
    echo $i, ' => ', $data, "\n";
}

/**
 * Will output:
 *     0 => true
 *     1 => { " )o?bz " : null , " %3W) " : [ false , 130 , " 6 " ] }
 *     2 => [ { " ny  " : true } ]
 *     3 => { " Ne;[3 " : [ true , true ] , " th: " : true , " C[8} " : true }
 */
```

## Documentation

Different documentations can be found on the website:
[http://hoa-project.net/](http://hoa-project.net/).

## License

Hoa is under the New BSD License (BSD-3-Clause). Please, see
[`LICENSE`](http://hoa-project.net/LICENSE).
