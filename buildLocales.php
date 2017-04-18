<?php

// preclude browser access
if (php_sapi_name() !== 'cli') {
  exit;
}

$output = array();

$fd = fopen('locale/js.pot', 'w');

fputs($fd, 'msgid ""' ."\n"
      . 'msgstr ""' . "\n"
      . '"Project-Id-Version: PACKAGE VERSION\n"' . "\n"
      . '"Report-Msgid-Bugs-To: \n"' . "\n"
      . '"POT-Creation-Date: ' . date('Y-m-md H:i') .'+0200\n"' . "\n"
      . '"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"' . "\n"
      . '"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"' . "\n"
      . '"Language-Team: LANGUAGE <LL@li.org>\n"' . "\n"
      . '"Language: \n"' . "\n"
      . '"MIME-Version: 1.0\n"' . "\n"
      . '"Content-Type: text/plain; charset=ISO-8859-1\n"' . "\n"
      . '"Content-Transfer-Encoding: 8bit\n"' ."\n\n");

// collect translatable texts
exec("for i in blocks/*/templates/*mustache; do iconv -c -f cp1252 \$i | awk '{if (match($0, /i18n}}([^{]*){{/)) {print substr($0, RSTART+6, RLENGTH-8)}}'; done | sort -u", $output);
exec("for i in blocks/*/*/*js; do iconv -c -f utf-8 \$i | awk '{if (match($0, /i18n.*'\'')/)) {print substr($0, RSTART+6, RLENGTH-8)}}'; done | sort -u", $output);

$output[] = 'Bestätigung';
$output[] = 'Diskussion';
$output[] = 'Evaluationen';
$output[] = 'Freitext';

foreach ($output as $entry) {
    if (strlen($entry)) {
        fputs($fd, 'msgid "'. str_replace('"', '\\"', utf8_decode($entry)) .'"' . "\n");
        fputs($fd, 'msgstr ""' ."\n\n");
    }
}

fclose($fd);

exec('make -f locale/Makefile');
