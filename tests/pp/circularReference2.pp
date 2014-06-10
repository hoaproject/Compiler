%skip space \s
%token hello Hello
%token world world!
%token goodbye Goodbye

#sentence:
	greeting() ::world::

#greeting:
	greeting() | ::goodbye::
