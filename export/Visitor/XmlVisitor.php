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
     * @var \DOMElement
     */
    private $currentNode;

    public function __construct(BlockFactory $blockFactory, \DOMDocument $document)
    {
        $this->blockFactory = $blockFactory;
        $this->document = $document;
        $this->currentNode = $document;
    }

    /**
     * {@inheritdoc}
     */
    public function startVisitingCourseware(Courseware $courseware)
    {
        $element = $this->document->createElement('courseware');
        $title = $this->document->createAttribute('title');
        $title->value = $courseware->title;
        $element->appendChild($title);
        $this->currentNode->appendChild($element);
        $this->currentNode = $element;

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
        $this->currentNode = $this->currentNode->parentNode;
    }

    /**
     * {@inheritdoc}
     */
    public function startVisitingChapter(Block $chapter)
    {
        $element = $this->document->createElement('chapter');
        $title = $this->document->createAttribute('title');
        $title->value = $chapter->title;
        $element->appendChild($title);
        $this->currentNode->appendChild($element);
        $this->currentNode = $element;

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
        $this->currentNode = $this->currentNode->parentNode;
    }

    /**
     * {@inheritdoc}
     */
    public function startVisitingSubChapter(Block $subChapter)
    {
        $element = $this->document->createElement('subchapter');
        $title = $this->document->createAttribute('title');
        $title->value = $subChapter->title;
        $element->appendChild($title);
        $this->currentNode->appendChild($element);
        $this->currentNode = $element;

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
        $this->currentNode = $this->currentNode->parentNode;
    }

    /**
     * {@inheritdoc}
     */
    public function startVisitingSection(Section $section)
    {
        $element = $this->document->createElement('section');
        $title = $this->document->createAttribute('title');
        $title->value = $section->title;
        $element->appendChild($title);
        $icon = $this->document->createAttribute('icon');
        $icon->value = $section->icon;
        $element->appendChild($icon);
        $blocks = $this->document->createElement('blocks');
        $element->appendChild($blocks);
        $this->currentNode->appendChild($element);
        $this->currentNode = $blocks;

        foreach ($section->getModel()->children as $block) {
            $uiBlock = $this->blockFactory->makeBlock($block);

            if ($uiBlock instanceof BlubberBlock) {
                $this->startVisitingBlubberBlock($uiBlock);
                $this->endVisitingBlubberBlock($uiBlock);
            } elseif ($uiBlock instanceof HtmlBlock) {
                $this->startVisitingHtmlBlock($uiBlock);
                $this->endVisitingHtmlBlock($uiBlock);
            } elseif ($uiBlock instanceof IFrameBlock) {
                $this->startVisitingIFrameBlock($uiBlock);
                $this->endVisitingIFrameBlock($uiBlock);
            } elseif ($uiBlock instanceof TestBlock) {
                $this->startVisitingTestBlock($uiBlock);
                $this->endVisitingTestBlock($uiBlock);
            } elseif ($uiBlock instanceof VideoBlock) {
                $this->startVisitingVideoBlock($uiBlock);
                $this->endVisitingVideoBlock($uiBlock);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function endVisitingSection(Section $section)
    {
        $this->currentNode = $this->currentNode->parentNode->parentNode;
    }

    /**
     * {@inheritdoc}
     */
    public function startVisitingBlubberBlock(BlubberBlock $block)
    {
        $element = $this->document->createElement('discussion-block');
        $this->currentNode->appendChild($element);
    }

    /**
     * {@inheritdoc}
     */
    public function startVisitingIFrameBlock(IFrameBlock $block)
    {
        $element = $this->document->createElement('iframe-block');
        $url = $this->document->createAttribute('url');
        $url->value = $block->url;
        $element->appendChild($url);
        $height = $this->document->createAttribute('height');
        $height->value = $block->height;
        $element->appendChild($height);
        $this->currentNode->appendChild($element);
    }

    /**
     * {@inheritdoc}
     */
    public function startVisitingVideoBlock(VideoBlock $block)
    {
        $element = $this->document->createElement('video-block');
        $url = $this->document->createAttribute('url');
        $url->value = $block->url;
        $element->appendChild($url);
        $this->currentNode->appendChild($element);
    }
}
