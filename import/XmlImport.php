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
    public function import($path, Courseware $courseware)
    {
        $dataFile = $path.'/data.xml';
        $document = new \DOMDocument();
        $document->loadXML(file_get_contents($dataFile));
        $coursewareNode = $document->documentElement;

        $courseware->progression = $coursewareNode->getAttribute('progression');
        $courseware->save();

        $files = array();
        foreach ($coursewareNode->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                if  ($child->tagName === 'file') {
                    $this->processFile($child, $courseware, $path, $files);
                }
            }
        }

        foreach ($coursewareNode->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                if ($child->tagName === 'chapter') {
                    $this->processChapterNode($child, $courseware, $files);
                }
            }
        }

        foreach ($this->uuid_block_cache as $block) {
            $uiBlock = $this->blockFactory->makeBlock($block);
            if (is_callable(array($uiBlock, 'importUUIDBlocks'))) {
                $uiBlock->importUUIDBlocks($this->uuid_block_cache);
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
        $chapter = $this->createBlock(
            array(
                'type'       => 'Chapter',
                'parent'     => $courseware->getModel(),
                'title'      => utf8_decode($node->getAttribute('title')),
                'seminar_id' => $courseware->getModel()->seminar_id,
                'uuid'       => utf8_decode($node->getAttribute('uuid'))
            ));

        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof \DOMElement) {
                switch ($childNode->tagName) {

                case 'subchapter':
                    $this->processSubChapterNode($childNode, $chapter, $files);
                    break;

                case 'asidesection':
                    $this->processAsideSectionNode($childNode, $chapter, $files);
                    break;
                }
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
        $subChapter= $this->createBlock(
            array(
                'type'       => 'Subchapter',
                'parent'     => $chapter,
                'title'      => utf8_decode($node->getAttribute('title')),
                'seminar_id' => $chapter->seminar_id,
                'uuid'       => utf8_decode($node->getAttribute('uuid'))
            ));

        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof \DOMElement) {
                switch ($childNode->tagName) {

                case 'section':
                    $this->processSectionNode($childNode, $subChapter, $files);
                    break;

                case 'asidesection':
                    $this->processAsideSectionNode($childNode, $subChapter, $files);
                    break;
                }
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
        $section = $this->createBlock(
            array(
                'type'       => 'Section',
                'parent'     => $subChapter,
                'title'      => utf8_decode($node->getAttribute('title')),
                'seminar_id' => $subChapter->seminar_id,
                'uuid'       => utf8_decode($node->getAttribute('uuid'))
            ));

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
     * Processes an aside section.
     *
     * @param \DOMElement $node        The section node
     * @param Block       $sub_chapter The parent (sub) chapter
     * @param array       $files       Mapping of original file ids to new
     *                                 document instances
     */
    private function processAsideSectionNode(\DOMElement $node, Block $sub_chapter, $files)
    {
        $section = $this->createBlock(
            array(
                'type'       => 'Section',
                'title'      => utf8_decode($node->getAttribute('title')),
                'seminar_id' => $subChapter->seminar_id,
                'uuid'       => utf8_decode($node->getAttribute('uuid'))
            ));

        // store aside section's ID in sub/chapter's field
        $aside_field = new \Mooc\DB\Field(array($sub_chapter->id, '', 'aside_section'));
        $aside_field->content = $section->id;
        $aside_field->store();


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

        $block = $this->createBlock(
            array(
                'type'       => utf8_decode($node->getAttribute('type')),
                'sub_type'   => $node->hasAttribute('sub-type')
                                ? utf8_decode($node->getAttribute('sub-type'))
                                : null,
                'parent'     => $section->getModel(),
                'title'      => utf8_decode($node->getAttribute('title')),
                'seminar_id' => $section->getModel()->seminar_id,
                'uuid'       => utf8_decode($node->getAttribute('uuid'))
            ));

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

    private function createBlock($data)
    {
        extract($data);

        $block = new Block();
        $block->type = $type;
        $block->title = $title;
        $block->seminar_id = $seminar_id;

        if ($sub_type) {
            $block->sub_type = $sub_type;
        }

        if ($parent) {
            $block->parent = $parent;
        }

        if ($uuid) {
            $this->uuid_block_cache[$uuid] = $block;
        }

        $block->store();

        return $block;
    }
}
