<?php
/**
 * This file is part of the Swiftype App Search PHP Client package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swiftype\AppSearch\Tests\Integration;

/**
 * Integration test for the Documents API.
 *
 * @package Swiftype\AppSearch\Test\Integration
 *
 * @author  Aurélien FOUCRET <aurelien.foucret@elastic.co>
 */
class DocumentApiTest extends AbstractEngineTestCase
{
    /**
     * Test indexing documents from sample data and check there is no errors.
     */
    public function testIndexing()
    {
        $documents = $this->getSampleDocuments();
        $engineName = $this->getDefaultEngineName();
        $client = $this->getDefaultClient();

        $indexingResponse = $client->indexDocuments($engineName, $documents);

        $this->assertCount(count($documents), $indexingResponse);
        foreach ($indexingResponse as $documentIndexingResponse) {
            $this->assertEmpty($documentIndexingResponse['errors']);
        }
    }

    /**
     * Test getting documents after they have been indexed.
     */
    public function testGetDocuments()
    {
        $documents = $this->getSampleDocuments();
        $engineName = $this->getDefaultEngineName();
        $client = $this->getDefaultClient();
        $documentIds = array_column($documents, 'id');

        $client->indexDocuments($engineName, $documents);
        $this->assertEquals($documents, $client->getDocuments($engineName, $documentIds));
    }

    /**
     * Test listing documents after they have been indexed.
     */
    public function testListDocuments()
    {
        $documents = $this->getSampleDocuments();
        $engineName = $this->getDefaultEngineName();
        $client = $this->getDefaultClient();
        $client->indexDocuments($engineName, $documents);

        $listParams = ['page' => ['current' => 1, 'size' => 25]];
        $documentListResponse = $client->listDocuments($engineName, $listParams);
        $this->assertEquals($listParams['page']['current'], $documentListResponse['meta']['page']['current']);
        $this->assertEquals($listParams['page']['size'], $documentListResponse['meta']['page']['size']);
        $this->assertCount(count($documents), $documentListResponse['results']);
    }

    /**
     * Test delete documents after they have been indexed.
     */
    public function testDeleteDocuments()
    {
        $documents = $this->getSampleDocuments();
        $engineName = $this->getDefaultEngineName();
        $client = $this->getDefaultClient();
        $documentIds = array_column($documents, 'id');
        $client->indexDocuments($engineName, $documents);

        $client->deleteDocuments($engineName, [current($documentIds)]);

        $documentListResponse = $client->listDocuments($engineName);
        $this->assertCount(count($documents) - 1, $documentListResponse['results']);
    }

    /**
     * Test delete documents after they have been indexed.
     */
    public function testUpdatingDocuments()
    {
        $documents = $this->getSampleDocuments();
        $engineName = $this->getDefaultEngineName();
        $client = $this->getDefaultClient();
        $client ->updateSchema($engineName, ['title' => 'text']);
        $client ->indexDocuments($engineName, $documents);

        $documentsUpdates = [['id' => $documents[0]['id'], 'title' => 'foo']];
        $updateResponse = $client->updateDocuments($engineName, $documentsUpdates);
        $this->assertEmpty(current($updateResponse)['errors']);
    }

    /**
     * Test getting a document that does not exists.
     */
    public function testGetNonExistingDocuments()
    {
        $this->assertEquals([null], $this->getDefaultClient()->getDocuments($this->getDefaultEngineName(), ['foo']));
    }

    /**
     * Test index in an engine that does not exists.
     *
     * @expectedException \Swiftype\AppSearch\Exception\NotFoundException
     */
    public function testIndexingInInvalidEngine()
    {
        $this->getDefaultClient()->getDocuments("not-an-engine", $this->getSampleDocuments());
    }
}
