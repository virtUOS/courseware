<?php

require __DIR__.'/../vendor/autoload.php';

/**
 * Adds a configuration that makes it possible to configure the registration
 * form.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class AddRegistrationFormConfig extends Migration
{
    private $terms = <<<EOT
<h1>Nutzungsbedingungen</h1>

<p>
  Super existe da per, qui tu europeo millennios registrate, lo libro immediatementevia.
  Sine traduction es non. Un lateres ascoltar initialmente uso, uso sitos etiam
  message ha. Tu del gode americas introduction, svedese historiettas ma non,
  il qui vide linguistic grammatica. Iste articulo questiones lo sia, ha usate
  europa demonstrate qui.
</p>

<p>
  Via es flexione computator professional. Tres campo computator que o. Vocabulario
  denomination principalmente qui de, e integre conferentias sed, introductori
  unidirectional nos ma. Svedese essentialmente sia il, su qui disuso movimento
  litteratura. Non veni vices durante le, debitas internet uno es. Pro capital
  internet da.
</p>

<p>
  In major moderne comprende nos. Da sed latente qualcunque linguistic, uso iala
  vostre historiettas le. Es sine libera via. Hodie millennios qui se, lo durante
  anglo-romanic immediatemente uno. Es lateres subjecto resultato qui. Asia technic
  sed o, de iste malo instituto uso.
</p>
EOT;

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return 'adds a configuration that makes it possible to configure the registration form';
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        Config::get()->create(\Mooc\REGISTRATION_FORM_CONFIG_ID, array(
            'value'       => "field:firstname*|Vorname\nfield:lastname*|Nachname\nfield:email*|E-Mail-Adresse\nfield:terms_of_service*",
            'is_default'  => 1,
            'type'        => 'string',
            'range'       => 'global',
            'section'     => 'global',
            'description' => 'Layout des Registrierungsformulars.'
        ));
        Config::get()->create(\Mooc\TERMS_OF_SERVICE_CONFIG_ID, array(
            'value'       => $this->terms,
            'is_default'  => 1,
            'type'        => 'string',
            'range'       => 'global',
            'section'     => 'global',
            'description' => 'Nutzungsbedingungen'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        Config::get()->delete(\Mooc\REGISTRATION_FORM_CONFIG_ID);
        Config::get()->delete(\Mooc\TERMS_OF_SERVICE_CONFIG_ID);
    }
}
