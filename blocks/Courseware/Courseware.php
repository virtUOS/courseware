<?
namespace Mooc\UI;

class Courseware extends Block {

    function initialize()
    {
    }

    // TODO: $context einführen!
    function student_view($context = array())
    {
        // TODO: chosen by user
        $section = current(
            \Mooc\DB\Block::findBySQL(
                'seminar_id = ? AND type = "Section" ORDER BY parent_id, position',
                array($this->container['cid'])));

        $active_subchapter = $section->parent;
        $active_chapter = $active_subchapter->parent;
        $courseware = $this->_model;

        // chapters
        $chapters = array();
        foreach ($courseware->children as $chapter) {
            $json = $chapter->toArray();
            $json['selected'] = $chapter->id == $active_chapter->id;
            $chapters[] = $json;
        }

        // subchapters
        $subchapters = array();
        foreach ($active_chapter->children as $sub) {
            $json = $sub->toArray();
            $json['selected'] = $sub->id == $active_subchapter->id;
            $subchapters[] = $json;
        }

        // sections
        $sections = array();
        foreach ($active_subchapter->children as $sec) {
            $json = $sec->toArray();
            $json['selected'] = $sec->id == $section->id;
            $sections[] = $json;
        }

        $courseware = $courseware->toArray();


        $active_section_block = $this->container['block_factory']->makeBlock($section);
        $active_section_html = $active_section_block->render('student', $context);

        $data = compact('courseware', 'chapters', 'subchapters', 'sections', 'active_section_html');
        return $data;
    }

}
