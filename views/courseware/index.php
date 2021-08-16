<? $body_id = 'courseware-index'; ?>
<div style="max-width: 1095px;">
    <? if($status !== null):?>
        <?= MessageBox::success('Lerninhalte wurden übertragen');?>
    <? else: ?>
        <?= MessageBox::warning('Lerninhalte wurden noch nicht übertragen');?>
    <? endif; ?>
    <p style="border: solid thin #d0d7e3; padding: 1em; text-align: justify; margin-top: 18px">
        Seit der Stud.IP Version 5.0 ist Courseware ein fester Systembestandteil. Aufgrund diverser Neuerungen wurden die
        Lerninhalte in die neue Struktur übertragen. Courseware 5 bietet eine Vielzahl von neuen Funktionen
        und verfügt über die neusten Bedienkonzepte in Stud.IP. Unter dem Menüpunkt "Mein Arbeitsplatz" haben nun
        alle Nutzenden die Möglichkeit eigene Lerninhalte zu erstellen und können diese dann mit anderen in Veranstaltungen
        und Studiengruppen teilen.<br>
        Ausführliche Informationen zur neuen Courseware finden Sie in der <a href="https://hilfe.studip.de/help/5.0/de/Basis.Courseware" target="_blank">
          Stud.IP Hilfe</a>.<br>
          <a href="<?= $CoursewareLink ?>" class="button">
            Zur Courseware dieser Veranstaltung
        </a>
    </p>
</div>
