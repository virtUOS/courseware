<?php
namespace Mooc\UI\EvaluationBlock;

use Mooc\UI\Block;
use Mooc\UI\Section\Section;


/**
 * Display the course evaluations in a (M)ooc.IP block.
 *
 * @author André Klaßen <klassen@elan-ev.de>
 */
class EvaluationBlock extends Block
{
    const NAME = 'Evaluationen';
    const DESCRIPTION = 'Bindet eine Evaluation ein';

    public function initialize()
    {
        $this->defineField('evaluations', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }
        if (!$active = self::evaluationActivated()) {
            return compact('active');
        }
        $this->setGrade(1.0);
        $eval_db = new \EvaluationDB();
        $evaluations = \StudipEvaluation::findMany($eval_db->getEvaluationIDs($this->container['cid'], EVAL_STATE_ACTIVE));
        $content = self::mustachify($evaluations);

        return array('active' => true, 'content' => $content);
    }

    public function preview_view()
    {
        return array();
    }

    public function pdfexport_view()
    {
        return array();
    }

    public function getHtmlExportData()
    {
        return array();
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
    public static function additionalInstanceAllowed($container, Section $section, $subType = null)
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
                               'link' => \URLHelper::getURL('show_evaluation.php', array('evalID' => $evaluation->id))
                         );
        }

        return $content;
    }
}
