<?
namespace Mooc\UI\EvaluationBlock;

use Mooc\UI\Block;


/**
 * Display the course evaluations in a (M)ooc.IP block.
 *
 * @author Andr� Kla�en <klassen@elan-ev.de>
 */
class EvaluationBlock extends Block
{
    const NAME = 'Evaluationen';

    function initialize()
    {
        $this->defineField('evaluations', \Mooc\SCOPE_BLOCK, '');
    }

    public function author_view()
    {
        if (!$active = self::evaluationActivated()) {
            return compact('active');
        }

        return compact('active');
    }


    function student_view()
    {
        if (!$active = self::evaluationActivated()) {
            return compact('active');
        }

        $this->setGrade(1.0);
        $eval_db = new \EvaluationDB();
        $evaluations = \StudipEvaluation::findMany($eval_db->getEvaluationIDs($this->container['cid'], EVAL_STATE_ACTIVE));
        $content = self::mustachify($evaluations);

        return array('active' => true, 'content' => $content);
    }

    /**
     * {@inheritDoc}
     */
    public function isEditable()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalInstanceAllowed()
    {
        return self::evaluationActivated();
    }


    private static function evaluationActivated()
    {
        return get_config('VOTE_ENABLE');
    }

    private static function mustachify($evaluations) {
        $content = array();
        foreach($evaluations as $evaluation) {
            $content[] = array('id' =>  $evaluation->id ,
                               'title' => $evaluation->title,
                               'description' => $evaluation->text,
                               'link' => \URLHelper::getURL('show_evaluation.php',
                                            array('evalID' => $evaluation->id)));
        }
        return $content;
    }
}
