Courseware - Plugin für Stud.IP
==============================

Mit Courseware können Sie interaktive multimediale Lernmodule erstellen und nutzen. Die Module sind in Kapitel, Unterkapitel und Abschnitte unterteilt und können aus Textblöcken, Videosequenzen, Aufgaben (benötigt das Vips-Plugin), Kommunkationselementen und einer Vielzahl weiterer Blöcke bestehen. Fertige Module können exportiert und in andere Kurse oder andere Installationen importiert werden.

Entwicklung
-----------

In der [developer documentation](docs/development.md) wird beschrieben wie man 
das Courseware Plugin erstellt nachdem Teile des Quellcode verändert wurden.

Weiter-Entwicklung
------------------
Seit der Veröffentlichung von Stud.IP 5 mit integrierter Courseware erhält dieses Plugin **keine weiteren Features**.
Bugs werden zeitnahe gefixt und es wird entsprechende Releases geben.

Version 5
---------
Diese Version ist nur für die Migration der Courseware-Inhalte in die Kern-Courseware. Administratoren können über ein Komondozeilen-Tool die Migration für alle Veranstaltungen auslösen.

Optional kann in der Stud.IP Konfiguration auch die manuelle Migration aktiviert werden. Hiermit haben Lehrende die Möglichkeit die Migration zu einem gewünschten Zeitpunkt selber auszulösen.

Um das Komondozeilen-Tool zu nutzen wechselt man in der Verzeichnis *public/plugins_packages/virtUOS/Courseware/cli*.
Die Migration für Courseware-Inhalte in allen Veranstaltung löst man mit "php application.php courseware:migrate" aus. Wird nach dem Befehl noch eine Veranstaltungsnummer angegeben, so wird die Migration nur für diese Veranstaltung ausgeführt.
Auf der Komondozeile erhalten Sie Informationen über den Fortschritt der Migration. Sollte die Migration unerwartet abbrechen, so kann diese wieder am Punkt des Abbruchs aufgenommen werden. Courseware speichert welche Veranstaltungen schon migriert worden sind.

Zum aktivieren der manuellen Migration über die Stud.IP Oberfläche wählen Sie unter Admin -> System -> Konfiguration -> Courseware das Feld COURSEWARE_MANUAL_MIGRATION aus und setzen es auf aktiviert.


Anforderungen
------------

Für die Version 3.x wird 
* StudIP Version 3.4.x-3.5.x
* PHP Version >= 5.4

benötigt.


Für die Version 4.x wird 
* StudIP Version >= 4.0
* PHP in Version 5.5-7.0
* MySQL in Version 5.5

benötigt.


Nutzung
-------

[Hier](docs/usage.md) finden Sie eine Anleitung für die Nutzung von Courseware.
