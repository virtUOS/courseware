<div class="mooc-registrations">
    <h1>
        <?= htmlReady($course->name) ?>
    </h1>

    <h2>
        <?= _('Anmeldung') ?>
    </h2>

    <form class="register" method="post" action="<?= $controller->url_for('registrations/create') ?>">
        <input type="text" name="vorname" placeholder="<?= _('Vorname') ?>" required><br>
        <input type="text" name="nachname" placeholder="<?= _('Nachname') ?>" required><br>
        <input type="email" name="mail" placeholder="<?= _('E-Mail-Adresse') ?>" required><br>
        <br>

        <article class="tos">
            <b><?= _('Nutzungsbedingungen') ?></b>
            <p>
                Super existe da per, qui tu europeo millennios registrate, lo libro immediatemente via. 
                Sine traduction es non. Un lateres ascoltar initialmente uso, uso sitos etiam message ha.
                Tu del gode americas introduction, svedese historiettas ma non, il qui vide linguistic grammatica.
                Iste articulo questiones lo sia, ha usate europa demonstrate qui.<br>
                Via es flexione computator professional. Tres campo computator que o. 
                Vocabulario denomination principalmente qui de, e integre conferentias sed,
                introductori unidirectional nos ma. Svedese essentialmente sia il, su qui 
                disuso movimento litteratura. Non veni vices durante le, debitas internet uno es. 
                Pro capital internet da.<br>
                In major moderne comprende nos. Da sed latente qualcunque linguistic,
                uso iala vostre historiettas le. Es sine libera via. Hodie millennios qui se,
                lo durante anglo-romanic immediatemente uno. Es lateres subjecto resultato qui. 
                Asia technic sed o, de iste malo instituto uso.<br>
            </p>
        </article>

        <label>
            <input type="checkbox" name="accept_tos" value="yes" required>
            <?= _('Einverstanden') ?>
        </label>

        <br>

        <?= Studip\Button::createAccept(_('Jetzt anmelden')) ?>
    </form>

    <? $infobox = $this->render_partial('registrations/_infobox') ?>
</div>