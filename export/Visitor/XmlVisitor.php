<?php

namespace Mooc\Export\Visitor;

use Mooc\DB\Block;
use Mooc\UI\BlockFactory;
use Mooc\UI\Courseware\Courseware;
use Mooc\UI\Section\Section;

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
        $this->enterNode(
            $this->appendBlockNode('courseware',
                                   $courseware->title,
                                   array(
                                       $this->createAttributeNode('progression', $courseware->progression),
                                   ))
        );
        $this->addNamespace($courseware->getXmlNamespace(), $courseware->getXmlSchemaLocation());
        $this->addNamespace(
            'http://www.w3.org/2001/XMLSchema-instance',
            null,
            'xsi'
        );

        foreach ($courseware->getModel()->children as $chapter) {
            $this->startVisitingChapter($chapter);
            $this->endVisitingChapter($chapter);
        }

        foreach ($courseware->getFiles() as $file) {
            $attributes = array(
                $this->createAttributeNode('id', $file['id']),
                $this->createAttributeNode('name', $file['name']),
                $this->createAttributeNode('filename', $file['filename']),
                $this->createAttributeNode('filesize', $file['filesize']),
            );
            if ($file['url']) {
                $attributes[] = $this->createAttributeNode('url', $file['url']);
            }
            $fileNode = $this->appendBlockNode('file', null, $attributes);

            if (trim($file['description']) !== '') {
                $fileNode->appendChild($this->document->createCDATASection(utf8_encode($file['description'])));
            }
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

        // visit a potential aside section
        $aside_field = \Mooc\DB\Field::find(array($chapter->id, '', 'aside_section'));
        if ($aside_field) {
            if ($aside_block = \Mooc\DB\Block::find($aside_field->content)) {
                $section = $this->blockFactory->makeBlock($aside_block);
                $this->startVisitingAsideSection($section);
                $this->endVisitingAsideSection($section);
            }
        }
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
        // visit a potential aside section
        $aside_field = \Mooc\DB\Field::find(array($subChapter->id, '', 'aside_section'));
        if ($aside_field) {
            if ($aside_block = \Mooc\DB\Block::find($aside_field->content)) {
                $section = $this->blockFactory->makeBlock($aside_block);
                $this->startVisitingAsideSection($section);
                $this->endVisitingAsideSection($section);
            }
        }

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
    public function startVisitingAsideSection(Section $section)
    {
        $this->enterNode($this->appendBlockNode('asidesection', $section->title, array(
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
    public function endVisitingAsideSection(Section $section)
    {
        $this->leaveNode();
    }

    /**
     * {@inheritdoc}
     */
    public function startVisitingBlock(\Mooc\UI\Block $block)
    {
        $alias = null;
        $namespace = $block->getXmlNamespace();
        $schemaLocation = $block->getXmlSchemaLocation();

        if ($namespace !== null && $schemaLocation !== null) {
            $alias = strtolower(get_class($block));

            if (preg_match('/\\\\(\w+)$/', $alias, $matches)) {
                $alias = $matches[1];
            }

            if (substr($alias, -5) === 'block') {
                $alias = substr($alias, 0, strlen($alias) - 5);
            }

            $this->addNamespace($namespace, $schemaLocation, $alias);

            if (method_exists($block, 'exportAdditionalNamespaces')) {
                foreach ($block->exportAdditionalNamespaces() as $args) {
                    list($addNamespace, $addSchemaLocation, $addAlias) = $args;
                    $this->addNamespace($addNamespace, $addSchemaLocation, $addAlias);
                }
            }
        }

        $properties = $block->exportProperties();
        $attributes = array();
        $attributes[] = $this->createAttributeNode('type', $block->getModel()->type);
        $attributes[] = $this->createAttributeNode('sub-type', $block->getModel()->sub_type);
        $attributes[] = $this->createAttributeNode('uuid', $block->getModel()->getUUID());

        foreach ($properties as $name => $value) {
            if ($alias !== null) {
                $name = $alias.':'.$name;
            }

            $attributes[] = $this->createAttributeNode($name, $value);
        }

        $blockNode = $this->appendBlockNode('block', $block->title, $attributes);

        if (method_exists($block, 'exportContentsForXml')) {
            $contents = $block->exportContentsForXml($this->document, $alias);

            foreach ($contents as $node) {
                $blockNode->appendChild($node);
            }
        } else {
            $contents = $block->exportContents();

            if ($contents !== null) {
                $blockNode->appendChild($this->document->createCDATASection(utf8_encode($contents)));
            }
        }
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
     * Introduce a new XML namespace with an optional namespace alias.
     *
     * @param string $namespace      The full XML namespace
     * @param string $schemaLocation The url under which the XML schema definition
     *                               file is located
     * @param string $alias          An optional namespace alias (must be given
     *                               to be able to use multiple namespaces in
     *                               a single file)
     */
    private function addNamespace($namespace, $schemaLocation, $alias = null)
    {
        if ($alias === null) {
            $namespaceNode = $this->createAttributeNode('xmlns', $namespace);
        } else {
            $namespaceNode = $this->createAttributeNode('xmlns:'.$alias, $namespace);
        }

        $rootNode =  $this->document->documentElement;
        $rootNode->appendChild($namespaceNode);

        if ($schemaLocation === null) {
            return;
        }

        if ($rootNode->hasAttribute('xsi:schemaLocation')) {
            $attributeNode = $rootNode->getAttributeNode('xsi:schemaLocation');
        } else {
            $attributeNode = $this->createAttributeNode('xsi:schemaLocation', '');
        }

        $attributeNode->value = trim($attributeNode->value).' '.$namespace.' '.$schemaLocation;

        $this->document->documentElement->appendChild($attributeNode);
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
        $attribute->value = htmlspecialchars(utf8_encode($value));

        return $attribute;
    }
}
