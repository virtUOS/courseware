<?php

namespace Mooc\UI\WallNewspaperBlock;

use Mooc\UI\Block;
use Mooc\UI\WallNewspaperBlock\Model\AgeGroup;
use Mooc\UI\WallNewspaperBlock\Model\Topic;

use Mooc\UI\TestBlock\Model\Test;

/**
 */
class WallNewspaperBlock extends Block
{
    const NAME = 'Wandzeitung';


    private $tree;

    function initialize()
    {
        $this->defineField('complete_topics', \Mooc\SCOPE_USER, array());
        $this->defineField('test_blocks', \Mooc\SCOPE_BLOCK, array());
        $this->tree = $this->createStructure();
    }

    /**
     * {@inheritDoc}
     */
    public function isEditable()
    {
        return false;
    }

    function student_view()
    {
        $jsonTree = $this->getTreeAsJSON($this->tree);
        $jsonTests = json_encode($this->findSelfTests());
        return compact('jsonTree', 'jsonTests');
    }

    function author_view()
    {
        $this->authorizeUpdate();
        return array();
    }

    /**
     * Updates the block's contents.
     *
     * @param array $data  The request data
     *
     * @return array success or fail
     */
    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        $success = $this->storeContent($data['topic_id'], $data);
        return compact('success');
    }

    /**
     * Completes a topic
     *
     * @param array $data  array containing the `topic_id` to be marked as complete
     * @return array  an empty array
     */
    public function complete_handler($data)
    {
        $this->authorizeUpdate();

        if (!isset($data['topic_id'])) {
            throw new \Mooc\UI\Errors\BadRequest('Parameter "topic_id" is required.');
        }

        if (!in_array($data['topic_id'], $this->findAllTopicIDs())) {
            throw new \Mooc\UI\Errors\BadRequest('This topic_id does not exist.');
        }

        $this->setTopicComplete($data['topic_id']);

        return array();
    }

    /**
     * Updates the block's AgeGroup to SelfTests mapping
     *
     * @param array $data  The request data
     *
     * @return array success or fail
     */
    public function save_age_groups_tests_handler(array $data)
    {
        $this->authorizeUpdate();

        if (!isset($data['age_groups_tests'])) {
            throw new \Mooc\UI\Errors\BadRequest('Parameter "age_groups_tests" is required.');
        }

        // TODO validate incoming data


        // $data['age_groups_tests'] looks like this:
        //   { 0: "13160", 10: null, 20: "1771", ... }

        foreach ($data['age_groups_tests'] as $ageGroupId => $testId) {
            $this->updateTestBlock($ageGroupId, $testId);
        }

        $success = true;
        return compact('success');
    }

    /**
     * Mark this block as done for the current user
     *
     * @return array success or fail
     */
    public function pass_self_test_handler($data)
    {
        // TODO: usually we would validate this call

        $this->setGrade(1.0);

        return array('success' => true);
    }


    const AGE_GROUPS = 'Bambini F-Jugend E-Jugend D-Jugend C-Jugend B-/A-Jugend';

    const TOPICS = <<<JSONDATA
        {
          "Entwicklung": [
            "Entwicklungsstand/Persönlichkeit",
            "Lernziele/Kompetenz",
            "Trainingsprinzipien"
          ],

          "Sportpraxis": [
            "Trainingsumfang",
            "Technik",
            "Taktik",
            "Kondition"
          ]
        }
JSONDATA;

    private function getDefaultTopics()
    {
        return json_decode(studip_utf8encode(self::TOPICS));
    }

    private $nextID = 0;

    private function nextID()
    {
        return $this->nextID++;
    }

    const DEFAULT_VIDEO = '';
    const DEFAULT_TEXT  = '';

    private function retrieveContent($topic_id)
    {
        if ($cached = $this->retrieveContentFromDB($this->id, $topic_id)) {
            return $cached->toArray('video text');
        } else {
            return ['video' => self::DEFAULT_VIDEO, 'text' => self::DEFAULT_TEXT];
        }
    }

    private function storeContent($topic_id, $content)
    {
        // TODO: validate and/or use defaults for $content

        $status = $this->storeContentInDB($this->id, $topic_id, $content['video'], $content['text']);

        return !!$status;
    }

    private $cached_contents;

    private function invalidateContentCache()
    {
        unset($this->cached_contents);
    }

    private function retrieveContentFromDB($block_id, $topic_id)
    {
        if (!$this->cached_contents) {
            $this->cached_contents = \SimpleORMapCollection::createFromArray(
                Model\WallNewspaperContent::findByBlock_id($block_id));
        }
        return $this->cached_contents->findOneBy('topic_id', $topic_id);
    }

    private function storeContentInDB($block_id, $topic_id, $video, $text)
    {
        $this->invalidateContentCache();
        $content = Model\WallNewspaperContent::findOneBySQL('block_id = ? AND topic_id = ?',
                                                            array($block_id, $topic_id));
        if (!$content) {
            $content = new Model\WallNewspaperContent();
            $content->block_id = $block_id;
            $content->topic_id = $topic_id;
        }

        $content->setValue('video', $video);
        $content->setValue('text', $text);

        return $content->store();
    }

    private function getTreeAsJSON($tree)
    {
        return '[' . join(',', array_map('strval', $tree)) . ']';
    }

    private function createStructure()
    {
        $result = [];
        foreach (words(self::AGE_GROUPS) as $title) {
            $id = $this->nextID();
            $testBlock = $this->findOrCreateTestBlock($id);
            $result[] = $this->createAgeGroup($id, $title, $testBlock);
        }
        return $result;
    }

    private function createAgeGroup($id, $title, $associatedSelfTest = null)
    {
        $age_group = new AgeGroup($id, studip_utf8decode($title), $this->retrieveContent($id), $this->isTopicComplete($id), $associatedSelfTest);

        foreach($this->getDefaultTopics() as $topic_title => $subtopics) {
            $topic_id = sprintf('%d-%d', $id, $this->nextID());
            $age_group->addChildTopic($this->createTopic($topic_id, $topic_title, $subtopics));
        }

        return $age_group;
    }

    private function createTopic($id, $title, $subtopics = array())
    {
        $complete = count($subtopics) ? true : $this->isTopicComplete($id);
        $subtopic = new Topic($id, studip_utf8decode($title), $this->retrieveContent($id), $complete);

        foreach ($subtopics as $subtitle) {
            $topic_id = sprintf('%s-%d', $subtopic->id, $this->nextID());
            $subtopic->addChildTopic($this->createTopic($topic_id, $subtitle));
        }

        return $subtopic;
    }

    private function isTopicComplete($topic_id)
    {
        return in_array((string) $topic_id, $this->complete_topics);
    }

    private function setTopicComplete($topic_id)
    {
        $newAry = $this->complete_topics;
        array_push($newAry, (string) $topic_id);
        $this->complete_topics = array_unique($newAry);
    }

    private function findAllTopicIDs()
    {
        return array_reduce($this->tree, array(__CLASS__, 'findChildTopicIDs'), array());
    }

    private function findChildTopicIDs($memo, $node)
    {
        $memo[] = (string) $node->id;
        if (count($node->childTopics)) {
            return array_reduce($node->childTopics, __METHOD__, $memo);
        }
        return $memo;
    }

    private function findSelfTests()
    {
        $storedTests = Test::findAllByType($this->_model->seminar_id, 'selftest');
        $tests = array();

        foreach ($storedTests as $test) {
            $tests[] = array(
                'id' => $test->id,
                'title' => $test->title,
                'created' => isset($test->created) ? date('c', strtotime($test->created)) : null,
                'exercises_count' => count($test->exercises)
            );
        }
        return studip_utf8encode($tests);
    }

    private function findOrCreateTestBlock($ageGroupId)
    {
        $needs_saving = false;

        $key = 'age-group-' . $ageGroupId;

        // already created
        if (isset($this->test_blocks[$key])) {
            $testBlock = \Mooc\DB\Block::find((int)$this->test_blocks[$key]);
            if (!$testBlock) {
                throw new \RuntimeException('Inconsistent block link.');
            }
        }

        // create it first
        else {
            $needs_saving = true;

            $testBlock = new \Mooc\DB\Block();
            $testBlock->setData(
                array(
                    'seminar_id'       => $this->_model->seminar_id,
                    'parent_id'        => $this->id,
                    'type'             => 'TestBlock',
                    'sub_type'         => 'selftest',
                    'title'            => sprintf('Self test belonging to WallNewspaperBlock %d (age group: %s)', $this->id, $ageGroupId)
                ));
            if (!$testBlock->store()) {
                throw new \RuntimeException('Could not store linked TestBlock.');
            }
            $this->test_blocks = array_replace($this->test_blocks, array($key => $testBlock->id));
        }

        if ($needs_saving) {
            $this->save();
        }

        return $testBlock;
    }

    private function updateTestBlock($ageGroupId, $testId) {
        $ageGroups = array_filter($this->tree, function ($group) use ($ageGroupId) { return $group->id == $ageGroupId; });
        if (!count($ageGroups)) {
            throw new \RuntimeException('Missing age group.');
        }

        $ageGroup = current($ageGroups);

        $field = new \Mooc\DB\Field(array((int) $ageGroup->testBlock->id, '', 'test_id'));
        $field->content = (string) $testId;
        $field->store();
    }


    // IMPORT functions

    public function importContentsFromXml(\DOMNode $node, $alias)
    {
        foreach ($node->childNodes as $child) {
            if ($child->localName === 'agegroup') {
                $this->importTopic($child, $alias);
            }
        }
    }

    private function importTopic(\DOMNode $node, $alias, $parentTopic = null) {

        $title = studip_utf8decode($node->getAttribute("{$alias}:title"));
        $childTopic = $this->findChildTopicByTitle($title, $parentTopic);

        foreach ($node->childNodes as $childNode) {
            switch ($childNode->localName) {
            case 'content':
                $this->importContent($childNode, $alias, $childTopic);
                break;

            case 'topics':
                $this->importTopics($childNode, $alias, $childTopic);
                break;

            case 'selftestnode':
                $this->importSelfTest($childNode, $alias, $childTopic);
                break;
            }
        }

        $this->storeContent($childTopic->id, $childTopic->content);
    }

    private function findChildTopicByTitle($title, $parentTopic = null)
    {
        $children = $parentTopic ? $parentTopic->childTopics : $this->tree;

        foreach ($children as $childTopic) {
            if ($childTopic->title === $title) {
                return $childTopic;
            }
        }

        throw new \RuntimeException('Could not find child topic "' . $title);
    }

    private function importContent(\DOMNode $node, $alias, $topic)
    {
        $key = studip_utf8decode($node->getAttribute("{$alias}:key"));
        $value = studip_utf8decode($node->textContent);
        $topic->content[$key] = $value;
    }


    private function importTopics(\DOMNode $node, $alias, $topic)
    {
        foreach($node->childNodes as $childNode) {
            if ($childNode->localName === 'topic') {
                $this->importTopic($childNode, $alias, $topic);
            }
        }
    }

    private function importSelfTest(\DOMNode $node, $alias, $topic)
    {
        if (!$testBlock = $topic->testBlock) {
            throw new \RuntimeException('Could not find test block.');
        }

        $uiBlock = $this->container['block_factory']->makeBlock($testBlock);
        $properties = $this->getPropertiesFromNode($node);
        if (count($properties) > 0) {
            $uiBlock->importProperties($properties);
        }
        $uiBlock->importContentsFromXml($node, 'test');
    }

    private function getPropertiesFromNode(\DOMNode $node)
    {
        $properties = array();
        foreach ($node->attributes as $attribute) {
            if (!$attribute instanceof \DOMAttr) {
                continue;
            }

            if ($attribute->namespaceURI !== null) {
                $properties[$attribute->name] = studip_utf8decode($attribute->value);
            }
        }
        return $properties;
    }

    // EXPORT functions

    public function exportAdditionalNamespaces()
    {
        return array(
            array('http://moocip.de/schema/block/test/', 'http://moocip.de/schema/block/test/test-1.0.xsd', 'test')
        );
    }

    public function exportContentsForXml($document, $alias)
    {
        $resultNodes = array();

        foreach($this->tree as $ageGroup) {
            $resultNodes[] = $this->exportAgeGroupForXml($document, $alias, $ageGroup);
        }

        return $resultNodes;
    }

    private function exportAgeGroupForXml($document, $alias, $ageGroup)
    {
        $node = $this->fillTopicXml($document, $alias, $ageGroup, 'agegroup');

        $uiBlock = $this->container['block_factory']->makeBlock($ageGroup->testBlock);

        if ($uiBlock->test_id) {

            $selfTestNode = $document->createElement($alias.':selftestnode');
            foreach ($uiBlock->exportContentsForXml($document, 'test') as $testNode) {
                $selfTestNode->appendChild($testNode);
            }

            foreach($uiBlock->exportProperties() as $key => $value) {
                $propAttr = $document->createAttribute(htmlentities(studip_utf8encode($alias.':'.$key), \ENT_XML1));
                $propAttr->value = htmlentities(studip_utf8encode($value), \ENT_XML1);
                $selfTestNode->appendChild($propAttr);
            }

            $node->appendChild($selfTestNode);
        }

        return $node;
    }

    private function exportTopicForXml($document, $alias, $topic)
    {
        return $this->fillTopicXml($document, $alias, $topic, 'topic');
    }

    private function fillTopicXml($document, $alias, $topic, $tagName)
    {
        $node = $document->createElement($alias.':'.$tagName);

        // title
        $titleAttr = $document->createAttribute($alias.':title');
        $titleAttr->value = htmlentities(studip_utf8encode($topic->title), \ENT_XML1);
        $node->appendChild($titleAttr);

        // contents
        foreach ($topic->content as $key => $value) {
            $contentNode = $document->createElement($alias.':content', htmlEntities(studip_utf8encode($value), \ENT_XML1));

            $keyAttr = $document->createAttribute($alias.':key');
            $keyAttr->value = htmlentities(studip_utf8encode($key), \ENT_XML1);
            $contentNode->appendChild($keyAttr);

            $node->appendChild($contentNode);
        }

        // children
        $topicsNode = $document->createElement($alias.':topics');
        foreach ($topic->childTopics as $topic) {
            $topicsNode->appendChild($this->exportTopicForXml($document, $alias, $topic));
        }
        $node->appendChild($topicsNode);

        return $node;
    }

    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/wallnewspaper/';
    }

    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/wallnewspaper/wallnewspaper-1.0.xsd';
    }
}
