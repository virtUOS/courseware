<?php

namespace Mooc\Export\Validator;

use Mooc\UI\Block;
use Mooc\UI\BlockFactory;

/**
 * Validate an XML import file.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class XmlValidator implements ValidatorInterface
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
     * {@inheritdoc}
     */
    public function validate($data)
    {
        $document = new \DOMDocument();
        $document->loadXML($data);
        $schemaFile = tempnam(sys_get_temp_dir(), 'schema-');
        $this->buildXmlSchema($document)->save($schemaFile);
        libxml_use_internal_errors(true);
        @$document->schemaValidate($schemaFile);
        unlink($schemaFile);
        $errors = array();

        foreach (libxml_get_errors() as $error) {
            $errors[] = $error->message;
        }

        return $errors;
    }

    private function buildXmlSchema(\DOMDocument $document)
    {
        $blockClassname = $this->blockFactory->getBlockByName('Courseware');
        $schema = $this->initialiseSchemaDocument();
        $this->addImportNode(
            $schema,
            $document->documentElement->namespaceURI,
            $this->getSchemaLocationForBlock($blockClassname)
        );
        $this->addImportNodesForBlocks($schema, $this->getDocumentNamespaces($document));

        return $schema;
    }

    /**
     * Initialises the XML schema definition document.
     *
     * @return \DOMDocument The XSD document
     */
    private function initialiseSchemaDocument()
    {
        $document = new \DOMDocument();
        $schemaNode = $document->createElementNS(
            'http://www.w3.org/2001/XMLSchema',
            'xsd:schema'
        );
        $document->appendChild($schemaNode);

        return $document;
    }

    /**
     * Returns the namespaces used in an XML document.
     *
     * @param \DOMDocument The document to analyse
     *
     * @return array The namespaces
     */
    private function getDocumentNamespaces(\DOMDocument $document)
    {
        $namespaces = array();
        $xPath = new \DOMXPath($document);

        foreach ($xPath->query('namespace::*', $document->documentElement) as $node) {
            /** @var \DOMNode $node */
            if (!$node instanceof \DOMNameSpaceNode) {
                continue;
            }

            if (preg_match('/^xmlns:(\w+)$/', $node->nodeName, $matches)) {
                $namespaces[$matches[1]] = $node->nodeValue;
            }
        }

        return $namespaces;
    }

    /**
     * Appends an "<xsd:import>" node to the schema definition document.
     *
     * @param \DOMDocument $document       The schema definition document
     * @param string       $namespace      The namespace to create the import
     *                                     node for
     * @param string       $schemaLocation The local path to the schema file
     */
    private function addImportNode(\DOMDocument $document, $namespace, $schemaLocation)
    {
        $namespaceAttribute = $document->createAttribute('namespace');
        $namespaceAttribute->value = $namespace;
        $schemaLocationAttribute = $document->createAttribute('schemaLocation');
        $schemaLocationAttribute->value = $schemaLocation;
        $importNode = $document->createElementNS(
            'http://www.w3.org/2001/XMLSchema',
            'xsd:import'
        );
        $importNode->appendChild($namespaceAttribute);
        $importNode->appendChild($schemaLocationAttribute);
        $document->documentElement->appendChild($importNode);
    }

    /**
     * Adds import nodes for all blocks to the XML schema definition.
     *
     * @param \DOMDocument $document The schema definition
     * @param array        $namespaces     THe document's namespaces
     */
    private function addImportNodesForBlocks(\DOMDocument $document, array $namespaces)
    {
        foreach ($namespaces as $alias => $namespace) {
            $blockClassname = $this->blockFactory->getBlockByName($alias);
            if ($blockClassname === null) {
                continue;
            }

            $schemaLocation = $this->getSchemaLocationForBlock($blockClassname);
            $this->addImportNode($document, $namespace, $schemaLocation);
        }
    }

    /**
     * Returns the path to an XML schema definition file on the local file system.
     *
     * @param string $block The block's fully qualified classname
     *
     * @return string The path to the XML schema definition file
     */
    private function getSchemaLocationForBlock($blockClassname)
    {
        $reflectionClass = new \ReflectionClass($blockClassname);
        $localSchemaPath = str_replace($blockClassname::getXmlNamespace(), '', $blockClassname::getXmlSchemaLocation());

        return dirname($reflectionClass->getFileName()).'/schema/'.$localSchemaPath;
    }
}
