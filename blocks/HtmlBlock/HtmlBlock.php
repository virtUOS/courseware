<?
namespace Mooc\UI;

// TODO: lots!
class HtmlBlock extends Block {

    function initialize()
    {
        $this->defineField('content', \Mooc\SCOPE_BLOCK, "Hello World!");
        // $this->content = new StringField(BLOCK_SCOPE, "Hello World!");
    }

    function student_view()
    {
        // JS, CSS und JS-ifizierte Templates werden automatisch
        // ausgeliefert

        // Was muss ich jetzt machen?
        // - wir brauchen daten
        // - (wir wollen die möglichkeit haben, das template zu
        //   bestimmen, aber standardmäßig ein bestimmtes nehmen?!)
        return array('content' => $this->content);




        // irgendwie JS und CSS ausliefern
        /*
        $fragment = new Fragment();
        $fragment->body = "Number of votes: '{$this->votes}'";
        $fragment->addJS(...);
        return $fragment;
        */

        ob_start();
        ?>

        <h2>Student view of block <?= $this->id ?>

        <div class=content>
            <?= htmlReady($this->content) ?>
        </div>

        <?
        return ob_get_clean();
    }

    function foo_handler($data)
    {
        $this->content = (string) $data['content'];
        return array("content" => $this->content);
    }
}
