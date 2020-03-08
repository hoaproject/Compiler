//
// Hoa
//
//
// @license
//
// New BSD License
//
// Copyright © 2007-2017, Hoa community. All rights reserved.
//
// Redistribution and use in source and binary forms, with or without
// modification, are permitted provided that the following conditions are met:
//     * Redistributions of source code must retain the above copyright
//       notice, this list of conditions and the following disclaimer.
//     * Redistributions in binary form must reproduce the above copyright
//       notice, this list of conditions and the following disclaimer in the
//       documentation and/or other materials provided with the distribution.
//     * Neither the name of the Hoa nor the names of its contributors may be
//       used to endorse or promote products derived from this software without
//       specific prior written permission.
//
// THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
// AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
// IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
// ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
// LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
// CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
// SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
// INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
// CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
// ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
// POSSIBILITY OF SUCH DAMAGE.
//
// Grammar \Hoa\Json\Grammar.
//
// Provide grammar for JSON. Please, see <http://json.org>, RFC4627 or RFC7159.
//
// @copyright  Copyright © 2007-2017 Hoa community.
// @license    New BSD License
//


%pragma lexer.unicode     false
%pragma parser.lookahead  0

%skip   space           [\x20\x09\x0a\x0d]+

%token  true            true
%token  false           false
%token  null            null
%token  string          "([\x20\x21\x23-\x5b\x5d-\x7f]|[\xc2-\xdf][\x80-\xbf]|(\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xec][\x80-\xbf]{2}|\xed[\x80-\x9f][\x80-\xbf]{2}|[\xee-\xef][\x80-\xbf]{2})|(\xf0[\x90-\xbf][\x80-\xbf]{2}|[\xf1-\xf3][\x80-\xbf]{3}|\xf4[\x80-\x8f][\x80-\xbf]{2})|\\(["\\/bfnrt]))*"
%token  brace_          {
%token _brace           }
%token  bracket_        \[
%token _bracket         \]
%token  colon           :
%token  comma           ,
%token  number          \-?(0|[1-9][0-9]*)(\.[0-9]+)?([eE][\+\-]?[0-9]+)?

value:
    <true> | <false> | <null> | string() | object() | array() | number()

string:
    <string>

number:
    <number>

#object:
    ::brace_:: ( pair() ( ::comma:: pair() )* )? ::_brace::

#pair:
    string() ::colon:: value()

#array:
    ::bracket_:: ( value() ( ::comma:: value() )* )? ::_bracket::
