%skip space \s
%token hello Hello
%token world world!

#sentence:
	( ::hello:: ::world:: ){2,2}
