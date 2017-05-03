" Hoa
"
"
" New BSD License
"
" Copyright © 2007-2015, Hoa community. All rights reserved.
"
" Redistribution and use in source and binary forms, with or without
" modification, are permitted provided that the following conditions are met:
"     * Redistributions of source code must retain the above copyright
"       notice, this list of conditions and the following disclaimer.
"     * Redistributions in binary form must reproduce the above copyright
"       notice, this list of conditions and the following disclaimer in the
"       documentation and/or other materials provided with the distribution.
"     * Neither the name of the Hoa nor the names of its contributors may be
"       used to endorse or promote products derived from this software without
"       specific prior written permission.
"
" THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
" IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
" ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
" LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
" CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
" SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
" INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
" CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
" ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
" POSSIBILITY OF SUCH DAMAGE.

if !exists("b:current_syntax")

  syntax region  ppRuleName     start="."  end=":"
  syntax region  ppRule         start=" "  end="$"  contains=ppNamedToken,ppSkippedToken,ppKeptToken,ppNode,ppKeyword
  syntax match   ppNamedToken   "\w\(\)" contained
  syntax region  ppSkippedToken start="::" end="::" contained
  syntax region  ppKeptToken    start="<"  end=">"  contained
  syntax region  ppNode         start="#"  end=" "  contained
  syntax region  ppComment      start="//" end="$"
  syntax region  ppToken        start="%"  end="$"
  syntax match   ppKeyword      "[\|\*\?\+,{}]" contained

  highlight default link ppRuleName     StorageClass
  highlight default link ppNamedToken   Function
  highlight default link ppSkippedToken Constant
  highlight default link ppKeptToken    Constant
  highlight default link ppNode         Identifier
  highlight default link ppComment      Comment
  highlight default link ppToken        Define
  highlight default link ppKeyword      Keyword

  syntax sync clear
  syntax sync fromstart

  let b:current_syntax="pp"

endif

finish
