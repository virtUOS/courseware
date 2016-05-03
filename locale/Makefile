# makefile for courseware/locale

# list of message catalogs to create
CATALOGS = locale/en/LC_MESSAGES/courseware.mo

# list of translatable source files
SOURCES = $(wildcard *.php */*.php */*/*.php */*/*/*.php)

# default rules for gettext handling
%.pot: $(SOURCES)
	xgettext -k_cw -o $@ --from-code=ISO-8859-1 $(SOURCES)
	msgconv -t ISO-8859-1 -o $@ $@
	msgcat locale/js.pot $@ -o $@

%.po: %.pot
	msgmerge -qU $@ $<

%.mo: %.po
	msgfmt -o $@ $<

# default target: create all message catalogs
all: $(CATALOGS)
