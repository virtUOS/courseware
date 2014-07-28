<?php

namespace Mooc\Export;

use Mooc\Export\Visitor\XmlVisitor;
use Mooc\UI\BlockFactory;
use Mooc\UI\Courseware\Courseware;

/**
 * Courseware XML export.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class XmlExport implements ExportInterface
{
    /**
     * @var \Mooc\UI\BlockFactory
     */
    private $blockFactory;

    public function __construct(BlockFactory $blockFactory)
    {
        $this->blockFactory = $blockFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function export(Courseware $courseware)
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;
        $visitor = new XmlVisitor($this->blockFactory, $document);
        $visitor->startVisitingCourseware($courseware);

        return $document->saveXML();
    }
}
