%skip space \s
%token hello Hello
%token world world!
%token goodbye Goodbye

#sentence:
	greeting(){2,3}

#greeting:
	::hello::
