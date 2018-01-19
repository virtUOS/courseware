Blöcke
======

Jeder [Abschnitt](structure.md) wird als eigene Seite dargestellt und kann beliebig viele _Blöcke_ enthalten.

Folgende Blocktypen sind in Courseware enthalten:


Freitext-Block
---------------
Freitext-Blöcke enthalten beliebig lange Texte die mittels eines WYSIWYG-Editors erstellt und formatiert werden können.
Der WYSIWYG-Editor ermöglicht außerdem das Einbinden von Bildern und externen Links.

![Autorenansicht des Freitext-Blocks](img/htmlBlock_edit.png)
_Autorenansicht des Freitext-Blocks_

![Studentenansicht des Freitext-Blocks](img/htmlBlock.png)
_Studentenansicht des Freitext-Blocks_


Video-Block
------------
Der Video-Block bettet ein Video aus verschiedensten Quellen in Courseware ein. Folgende Optionen stehen zur Auswahl:
- **Webvideo** ermöglicht das einbetten eines <video>-Tags. Es können verschiedene Qualitäten und Dateiformate gewählt werden.
- **YouTube**  bindet Videos mittels einer YouTube-ID ein. Es können Autoplay sowie Start und Ende des Videos gewählt werden.
- **openCast** bindet Vorlesungsaufzeichnungen ein die mit dem openCast-Player dargestellt werden.
- **eingebettetes Video (iFrame)** ermöglicht das einbetten mittels <iframe>-Tag. Dies ist notwendig wenn ein spezieller Player bereitgestellt wird.

![Autorenansicht des Video-Blocks](img/videoBlock_edit.png)
_Autorenansicht des Video-Blocks_

![Studentenansicht des Video-Blocks](img/videoBlock.png)
_Studentenansicht des Video-Blocks_


Quiz-Block
-----------
Der Quiz-Block stellt Aufgaben aus dem Vips-Plugin dar. Die Aufgaben können von den Lernenden bearbeitet und zur Auswertung abgeschickt werden.
Es gibt zwei Typen von Quiz-Blöcken, den Selbsttest und das Übungsblatt. Selbsttest können beliebig oft ausgeführt werden, die Auswertung erfolgt sofort.
In den Courseware-Einstellungen kann die Anzahl der Versuche, bis die Lösung angezeigt wird, eingestellt werden. Das Übungsblatt wird erst durch den Dozenten
im Vips-Plugin ausgewertet. Solange die Übung aktiv ist kann ein Lernender die Lösungen abändern. 

![Autorenansicht des Quiz-Blocks](img/testBlock_edit.png)
_Autorenansicht des Quiz-Blocks_

![Studentenansicht des Quiz-Blocks](img/testBlock.png)
_Studentenansicht des Quiz-Blocks_


Audio-Block
------------
In den Audio-Block können Audiodateien aus dem Dateibereich in Courseware eingebunden werden.
Der Courseware-Audio-Player ermöglicht die Funktionen Play, Pause und Stop. Vor- und zurückspulen ist 
über die Fortschrittsanzeige möglich.

Es werden die Dateitypen **mp3**, **ogg** und **wav** unterstützt. _Bitte beachten Sie das der Microsoft Internet Explorer das Dateiformat wav nicht unterstützt._

![Autorenansicht des Audio-Blocks](img/audioBlock_edit.png)
_Autorenansicht des Audio-Blocks_

![Studentenansicht des Audio-Blocks](img/audioBlock.png)
_Studentenansicht des Audio-Blocks_


Quellcode-Block (ab 2.2)
------------------------
Der Quellcode-Block verwendet das Syntax-Highlighting-Tool [highlight.js](https://highlightjs.org/).
Eingegebener Quelltext wird mittels der angegebenen Sprache dargestellt wie in einer IDE.

![Autorenansicht des Quellcode-Blocks](img/codeBlock_edit.png)
_Autorenansicht des Quellcode-Blocks_

![Studentenansicht des Quellcode-Blocks](img/codeBlock.png)
_Studentenansicht des Quellcode-Blocks_


Bestätigungs-Block
-------------------
In einer Courseware die sequentiell abgearbeitet werden soll müssen die in einer Sektion enthaltenen Blöcke bearbeitet werden damit das nächste Kapitel aufgerufen werden kann.
Da manche Blöcke wie z.B. Freitext-Block, IFrame-Block etc. nur das betrachten als Bestehenskriterium haben, kann mit dem Bestätigungs-Block eine zusätzliches Bestehenskriterium

eingefügt werden. Erst wenn der Nutzer bestätigt das er die Sektion bearbeitet, gelesen und oder betrachtet hat, wird die Sektion als erfolgreich bearbeitet vermerkt.

![Autorenansicht des Bestätigungs-Blocks](img/confirmBlock_edit.png)
_Autorenansicht des Bestätigungs-Blocks_

![Studentenansicht des Bestätigungs-Blocks](img/confirmBlock.png)
_Studentenansicht des Bestätigungs-Blocks_


Download-Block
---------------
Dieser Block ermöglicht den Download einer Datei aus dem Dateibereich.

![Autorenansicht des Download-Blocks](img/downloadBlock_edit.png)
_Autorenansicht des Download-Blocks_

![Studentenansicht des Download-Blocks](img/downloadBlock.png)
_Studentenansicht des Download-Blocks_


Evaluationen-Block
-------------------
Mit diesem Block können StudI-Evaluationen in Courseware eingebunden werden.


Forum-Block
------------
Mit diesem Block kann das StudIP-Forum in Courseware eingebunden werden.


Gruppierungs-Block
-------------------
Der Gruppierungs-Block fasst optisch mehrere Blöcke zusammen. Es ist eine Darstellung in einem sogenannten Accordion oder in Tabs möglich.

![Autorenansicht des Gruppierungs-Blocks](img/assortBlock_edit.png)
_Autorenansicht des Gruppierungs-Blocks_

![Studentenansicht des Gruppierungs-Blocks](img/assortBlock.png)
_Studentenansicht des Gruppierungs-Blocks_


IFrame-Block
-------------
Dieser Block bindet externe Inhalte von anderen Websites ein. Es kann die URL der einzubindenden Website angegeben werden, sowie die Höhe die dieser Block einnehmen soll.
Außerdem besteht die Möglichkeit eine einmalige User-ID an die eingebundene Seite zu übermitteln. Dies ID lässt keine Rückschlüsse auf den StudIP-Nutzer zu, identifiziert jedoch eindeutig.

![Autorenansicht des IFrame-Blocks](img/iframeBlock_edit_1.png)
_Autorenansicht des IFrame-Blocks_

![Autorenansicht des IFrame-Blocks](img/iframeBlock_edit_2.png)
_Autorenansicht des IFrame-Blocks mit Übergabeparameter_

![Studentenansicht des IFrame-Blocks](img/iframeBlock.png)
_Studentenansicht des IFrame-Blocks_


Galerie-Block (ab 2.2)
-----------------------
In den Galerie-Block werden alle Bilder aus einem Ordner des Dateibereichs in einer Karussellansicht angezeigt. 
Die Bilder können automatisch wechseln (autoplay) und die Navigationspfeile können ausgeblendet werden.

![Autorenansicht des Galerie-Blocks](img/galleryBlock_edit.png)
_Autorenansicht des Galerie-Blocks_

![Studentenansicht des Galerie-Blocks](img/galleryBlock.png)
_Studentenansicht des Galerie-Blocks mit Navigationspfeilen_

![Studentenansicht des Galerie-Blocks](img/galleryBlock_wo.png)
_Studentenansicht des Galerie-Blocks ohne Navigationspfeile_


Merksatz-Block (ab 2.2)
------------------------
Der Merksatz-Block ermöglicht das hervorheben von Informationen. Es können 5 verschiedene Farben und 15 verschiedene Icons ausgewählt werden.

![Autorenansicht des Merksatz-Blocks](img/keypointBlock_edit.png)
_Autorenansicht des Merksatz-Blocks_

![Studentenansicht des Merksatz-Blocks](img/keypointBlock.png)
_Studentenansicht des Merksatz-Blocks_


Such-Block (ab 2.2)
--------------------
Der Such-Block ermöglicht das Suchen innerhalb der Courseware einer Veranstaltung. 
Dieser Block kann sowohl in Sektionen als auch in der Sidebar verwendet werden.

![Autorenansicht des Such-Blocks](img/searchBlock_edit.png)
_Autorenansicht des Such-Blocks_

![Studentenansicht des Such-Blocks](img/searchBlock.png)
_Studentenansicht des Such-Blocks_


Link-Block (ab 2.2)
--------------------
Mit dem Link-Block lassen sich Links auf Strukturelemente innerhalb der Courseware setzen und externe Links erstellen.

![Autorenansicht des Link-Blocks](img/linkBlock_edit.png)
_Autorenansicht des Link-Blocks_

![Studentenansicht des Link-Blocks](img/linkBlock.png)
_Studentenansicht des Link-Blocks_


PDF-Block (ab 2.2)
--------------------
Der PDF-Block kann PDF Dateien aus dem Dateibereich darstellen. Die Datei lässt sich wie gewohnt durchblättern und runter laden.

![Autorenansicht des PDF-Blocks](img/pdfBlock_edit.png)
_Autorenansicht des PDF-Blocks_

![Studentenansicht des PDF-Blocks](img/pdfBlock.png)
_Studentenansicht des PDF-Blocks_


Kommentare & Diskussion Block (ab 2.2)
--------------------
Dieser Block ermöglicht eine Kommunikation zwischen den Lernenden untereinander und mit dem Lehrenden. 
Kommentare und Fragen können hiermit gezielt an einer gewünschten Stelle eingebunden werden. Der Lehrende hat die 
Möglichkeit über die Diskussionsübersicht alle Diskussionen einer Veranstaltung auf einen Blick zu sehen.

![Autorenansicht des Kommentare & Diskussion Blocks](img/postBlock_edit.png)
_Autorenansicht des Kommentare & Diskussion Blocks_

![Studentenansicht des Kommentare & Diskussion Blocks](img/postBlock.png)
_Studentenansicht des Kommentare & Diskussion Blocks_

![Diskussionsübersicht für Lehrende](img/cpo_post.png)
_Diskussionsübersicht für Lehrende_
