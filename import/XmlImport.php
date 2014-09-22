<?php

namespace Mooc\Import;

use Mooc\DB\Block;
use Mooc\UI\BlockFactory;
use Mooc\UI\Courseware\Courseware;
use Mooc\UI\Section\Section;

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
    public function import($path, Courseware $context)
    {
        $dataFile = $path.'/data.xml';
        $document = new \DOMDocument();
        $document->loadXML(file_get_contents($dataFile));
        $coursewareNode = $document->documentElement;
        $files = array();

        foreach ($coursewareNode->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                if  ($child->tagName === 'file') {
                    $this->processFile($child, $context, $path, $files);
                }
            }
        }

        foreach ($coursewareNode->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                if ($child->tagName === 'chapter') {
                    $this->processChapterNode($child, $context, $files);
                }
            }
        }
    }

    /**
     * Processes a file.
     *
     * @param \DOMElement $node       The file node
     * @param Courseware  $courseware The parent courseware
     * @param string      $path       The path under which the ZIP archive
     *                                which contents are to be imported has
     *                                been extracted
     * @param array       $files      Mapping of original file ids to new
     *                                document instances
     */
    private function processFile(\DOMElement $node, Courseware $courseware, $path, &$files)
    {
        /** @var \Seminar_User $user */
        global $user;

        $folder = \TreeAbstract::getInstance('StudipDocumentTree', array('range_id' => $courseware->getModel()->seminar_id));
        $folders = $folder->getKids($courseware->getModel()->seminar_id);
        $originId = $node->getAttribute('id');
        $filename = utf8_decode($node->getAttribute('filename'));
        $sourceFile = $path.'/'.$originId.'/'.$filename;
        $data = array(
            'range_id' => $folders[0],
            'user_id' => $user->cfg->getUserId(),
            'seminar_id' => $courseware->getModel()->seminar_id,
            'name' => utf8_decode($node->getAttribute('name')),
            'description' => utf8_decode($node->textContent),
            'filename' => $filename,
            'filesize' => utf8_decode($node->getAttribute('filesize')),
            'url' => utf8_decode($node->getAttribute('url')),
            'author_name' => $user->getFullName(),
        );

        if (file_exists($sourceFile)) {
            // the file is part of the uploaded ZIP archive
            $document = \StudipDocument::createWithFile($sourceFile, $data);
        } else {
            // the file is referenced by URL
            $document = new \StudipDocument();
            $document->setData($data);
            $document->store();
        }

        $files[$originId] = $document;
    }

    /**
     * Processes a chapter.
     *
     * @param \DOMElement $node       The chapter node
     * @param Courseware  $courseware The parent courseware
     * @param array       $files      Mapping of original file ids to new
     *                                document instances
     */
    private function processChapterNode(\DOMElement $node, Courseware $courseware, array $files)
    {
        $chapter = new Block();
        $chapter->type = 'Chapter';
        $chapter->parent = $courseware->getModel();
        $chapter->title = utf8_decode($node->getAttribute('title'));
        $chapter->store();

        foreach ($node->childNodes as $subChapterNode) {
            if ($subChapterNode instanceof \DOMElement) {
                $this->processSubChapterNode($subChapterNode, $chapter, $files);
            }
        }
    }

    /**
     * Processes a sub chapter.
     *
     * @param \DOMElement $node    The sub chapter node
     * @param Block       $chapter The parent chapter
     * @param array       $files   Mapping of original file ids to new
     *                             document instances
     */
    private function processSubChapterNode(\DOMElement $node, Block $chapter, $files)
    {
        $subChapter = new Block();
        $subChapter->type = 'Subchapter';
        $subChapter->parent = $chapter;
        $subChapter->title = utf8_decode($node->getAttribute('title'));
        $subChapter->store();

        foreach ($node->childNodes as $sectionNode) {
            if ($sectionNode instanceof \DOMElement) {
                $this->processSectionNode($sectionNode, $subChapter, $files);
            }
        }
    }

    /**
     * Processes a section.
     *
     * @param \DOMElement $node       The section node
     * @param Block       $subChapter The parent sub chapter
     * @param array       $files      Mapping of original file ids to new
     *                                document instances
     */
    private function processSectionNode(\DOMElement $node, Block $subChapter, $files)
    {
        $section = new Block();
        $section->type = 'Section';
        $section->parent = $subChapter;
        $section->title = utf8_decode($node->getAttribute('title'));
        $section->store();

        /** @var \Mooc\UI\Section\Section $uiSection */
        $uiSection = $this->blockFactory->makeBlock($section);

        foreach ($node->childNodes as $blockNode) {
            if ($blockNode instanceof \DOMElement) {
                $this->processBlockNode($blockNode, $uiSection, $files);
            }
        }

        $uiSection->save();
    }

    /**
     * Processes a block and its fields.
     *
     * @param \DOMElement $node    The block node
     * @param Section     $section The parent section
     * @param array       $files   Mapping of original file ids to new
     *                             document instances
     */
    private function processBlockNode(\DOMElement $node, Section $section, $files)
    {
        $block = new Block();
        $block->type = utf8_decode($node->getAttribute('type'));
        if ($node->hasAttribute('sub-type')) {
            $block->sub_type = utf8_decode($node->getAttribute('sub-type'));
        }
        $block->parent = $section->getModel();
        $block->title = utf8_decode($node->getAttribute('title'));
        $block->store();

        $section->updateIconWithBlock($block);

        /** @var \Mooc\UI\Block $uiBlock */
        $uiBlock = $this->blockFactory->makeBlock($block);
        $properties = array();

        foreach ($node->attributes as $attribute) {
            if (!$attribute instanceof \DOMAttr) {
                continue;
            }

            if ($attribute->namespaceURI !== null) {
                $properties[$attribute->name] = utf8_decode($attribute->value);
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
            $uiBlock->importContentsFromXml($node, $alias, $files);
        } else {
            $uiBlock->importContents(trim($node->textContent), $files);
        }
    }
}
