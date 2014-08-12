<?php

namespace Mooc\Import;

use Mooc\DB\Block;
use Mooc\UI\BlockFactory;
use Mooc\UI\Courseware\Courseware;

/**
 * Courseware XML import.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class XmlImport implements ImportInterface
{
    /**
     * @var BlockFactory
     */
    private $blockFactory;

    public function __construct(BlockFactory $blockFactory)
    {
        $this->blockFactory = $blockFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function import($data, Courseware $context)
    {
        $document = new \DOMDocument();
        $document->loadXML($data);

        $coursewareNode = $document->documentElement;

        foreach ($coursewareNode->childNodes as $chapterNode) {
            if ($chapterNode instanceof \DOMElement) {
                $this->processChapterNode($chapterNode, $context);
            }
        }
    }

    /**
     * Processes a chapter.
     *
     * @param \DOMElement $node       The chapter node
     * @param Courseware  $courseware The parent courseware
     */
    private function processChapterNode(\DOMElement $node, Courseware $courseware)
    {
        $chapter = new Block();
        $chapter->type = 'Chapter';
        $chapter->parent = $courseware->getModel();
        $chapter->title = $node->getAttribute('title');
        $chapter->store();

        foreach ($node->childNodes as $subChapterNode) {
            if ($subChapterNode instanceof \DOMElement) {
                $this->processSubChapterNode($subChapterNode, $chapter);
            }
        }
    }

    /**
     * Processes a sub chapter.
     *
     * @param \DOMElement $node    The sub chapter node
     * @param Block       $chapter The parent chapter
     */
    private function processSubChapterNode(\DOMElement $node, Block $chapter)
    {
        $subChapter = new Block();
        $subChapter->type = 'Subchapter';
        $subChapter->parent = $chapter;
        $subChapter->title = $node->getAttribute('title');
        $subChapter->store();

        foreach ($node->childNodes as $sectionNode) {
            if ($sectionNode instanceof \DOMElement) {
                $this->processSectionNode($sectionNode, $subChapter);
            }
        }
    }

    /**
     * Processes a section.
     *
     * @param \DOMElement $node       The section node
     * @param Block       $subChapter The parent sub chapter
     */
    private function processSectionNode(\DOMElement $node, Block $subChapter)
    {
        $section = new Block();
        $section->type = 'Section';
        $section->parent = $subChapter;
        $section->title = $node->getAttribute('title');
        $section->store();

        foreach ($node->childNodes as $blockNode) {
            if ($blockNode instanceof \DOMElement) {
                $this->processBlockNode($blockNode, $section);
            }
        }
    }

    /**
     * Processes a block and its fields.
     *
     * @param \DOMElement $node    The block node
     * @param Block       $section The parent section
     */
    private function processBlockNode(\DOMElement $node, Block $section)
    {
        $block = new Block();
        $block->type = $node->getAttribute('type');
        $block->parent = $section;
        $block->title = $node->getAttribute('title');
        $block->store();

        /** @var \Mooc\UI\Block $uiBlock */
        $uiBlock = $this->blockFactory->makeBlock($block);
        $properties = array();

        foreach ($node->attributes as $attribute) {
            if (!$attribute instanceof \DOMAttr) {
                continue;
            }

            if ($attribute->namespaceURI !== null) {
                $properties[$attribute->name] = $attribute->value;
            }
        }

        if (count($properties) > 0) {
            $uiBlock->importProperties($properties);
        }

        if (method_exists($uiBlock, 'importContentsFromXml')) {
            $alias = strtolower($block->type);
            if (substr($alias, -5) === 'block') {
                $alias = substr($alias, 0, -5);
            }
            $uiBlock->importContentsFromXml($node, $alias);
        } else {
            $uiBlock->importContents(trim($node->textContent));
        }
    }
}
