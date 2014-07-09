<?php

namespace Mooc\Export\Visitor;

use Mooc\DB\Block;
use Mooc\UI\BlockFactory;
use Mooc\UI\BlubberBlock\BlubberBlock;
use Mooc\UI\Courseware\Courseware;
use Mooc\UI\HtmlBlock\HtmlBlock;
use Mooc\UI\IFrameBlock\IFrameBlock;
use Mooc\UI\Section\Section;
use Mooc\UI\TestBlock\TestBlock;
use Mooc\UI\VideoBlock\VideoBlock;

/**
 * XML courseware visitor.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class XmlVisitor extends AbstractVisitor
{
    /**
     * @var \Mooc\UI\BlockFactory
     */
    private $blockFactory;

    /**
     * @var \DOMDocument
     */
    private $document;

    /**
     * @var \DOMNode[]
     */
    private $nodeStack = array();

    /**
     * @var \DOMNode
     */
    private $currentNode;

    public function __construct(BlockFactory $blockFactory, \DOMDocument $document)
    {
        $this->blockFactory = $blockFactory;
        $this->document = $document;
        $this->enterNode($document);
    }

    /**
     * {@inheritdoc}
     */
    public function startVisitingCourseware(Courseware $courseware)
    {
        $this->enterNode($this->appendBlockNode('courseware', $courseware->title));

        foreach ($courseware->getModel()->children as $chapter) {
            $this->startVisitingChapter($chapter);
            $this->endVisitingChapter($chapter);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function endVisitingCourseware(Courseware $courseware)
    {
        $this->leaveNode();
    }

    /**
     * {@inheritdoc}
     */
    public function startVisitingChapter(Block $chapter)
    {
        $this->enterNode($this->appendBlockNode('chapter', $chapter->title));

        foreach ($chapter->children as $chapter) {
            $this->startVisitingSubChapter($chapter);
            $this->endVisitingSubChapter($chapter);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function endVisitingChapter(Block $chapter)
    {
        $this->leaveNode();
    }

    /**
     * {@inheritdoc}
     */
    public function startVisitingSubChapter(Block $subChapter)
    {
        $this->enterNode($this->appendBlockNode('subchapter', $subChapter->title));

        foreach ($subChapter->children as $block) {
            $section = $this->blockFactory->makeBlock($block);
            $this->startVisitingSection($section);
            $this->endVisitingSection($section);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function endVisitingSubChapter(Block $subChapter)
    {
        $this->leaveNode();
    }

    /**
     * {@inheritdoc}
     */
    public function startVisitingSection(Section $section)
    {
        $this->enterNode($this->appendBlockNode('section', $section->title, array(
            $this->createAttributeNode('icon', $section->icon),
        )));

        foreach ($section->getModel()->children as $block) {
            $uiBlock = $this->blockFactory->makeBlock($block);
            $this->startVisitingBlock($uiBlock);
            $this->endVisitingBlock($uiBlock);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function endVisitingSection(Section $section)
    {
        $this->leaveNode();
    }

    /**
     * {@inheritdoc}
     */
    public function startVisitingBlock(\Mooc\UI\Block $block)
    {
        $properties = $block->export();
        $attributes = array();

        foreach ($properties as $name => $value) {
            $attributes[] = $this->createAttributeNode($name, $value);
        }

        $this->appendBlockNode('block', $block->title, $attributes);
    }

    /**
     * Enters a new DOM node (i. e. making it the current node).
     *
     * @param \DOMNode $node The node to enter
     */
    private function enterNode(\DOMNode $node)
    {
        // put current node on the backtracking stack
        if ($this->currentNode !== null) {
            $this->nodeStack[] = $this->currentNode;
        }

        $this->currentNode = $node;
    }

    /**
     * Leaves the current node.
     *
     * @throws \RuntimeException if the current node is the XML document
     */
    private function leaveNode()
    {
        if (count($this->nodeStack) == 0) {
            throw new \RuntimeException('Cannot leave the root node');
        }

        $this->currentNode = array_pop($this->nodeStack);
    }

    /**
     * Appends a new block node to the current node.
     *
     * @param string $elementName The element name
     * @param string $title       The block title
     * @param array  $attributes  Element attributes
     *
     * @return \DOMElement The new element
     */
    private function appendBlockNode($elementName, $title = null, array $attributes = array())
    {
        $element = $this->document->createElement($elementName);

        if ($title !== null) {
            $element->appendChild($this->createAttributeNode('title', $title));
        }

        foreach ($attributes as $attribute) {
            $element->appendChild($attribute);
        }

        $this->currentNode->appendChild($element);

        return $element;
    }

    /**
     * Creates a new DOM XML attribute.
     *
     * @param string $name  Attribute name
     * @param string $value Attribute value
     *
     * @return \DOMAttr Attribute node
     */
    private function createAttributeNode($name, $value)
    {
        $attribute = $this->document->createAttribute($name);
        $attribute->value = utf8_encode($value);

        return $attribute;
    }
}
