<?php

require __DIR__.'/../vendor/autoload.php';

/**
 * Adds a data field that allows to store the privacy policy.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class AddPrivacyPolicyDataField extends Migration
{
    private $privacyPolicy = <<<EOT
<h1>Datenschutzerklärung</h1>

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
        return 'Adds a data field that allows to store the privacy policy.';
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        Config::get()->create(\Mooc\PRIVACY_POLICY_ID, array(
            'value'       => $this->privacyPolicy,
            'is_default'  => 1,
            'type'        => 'string',
            'range'       => 'global',
            'section'     => 'global',
            'description' => 'Datenschutzerklärung'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        Config::get()->delete(\Mooc\PRIVACY_POLICY_ID);
    }
}
