<?php
/**
 * An example service for adding business logic to your class.
 *
 * @author  Conduction.nl <info@conduction.nl>
 * @license EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace CommonGateway\FirstRegistrationBundle\Service;

use App\Entity\Endpoint;
use App\Entity\Entity as Schema;
use App\Entity\Gateway as Source;
use App\Entity\Mapping;
use App\Entity\ObjectEntity;
use App\Entity\File;
use CommonGateway\CoreBundle\Service\CacheService;
use CommonGateway\CoreBundle\Service\MappingService;
use CommonGateway\CoreBundle\Service\SynchronizationService;
use CommonGateway\GeboorteVrijBRPBundle\Service\ZgwToVrijbrpService;
use Doctrine\ORM\EntityManagerInterface;
use CommonGateway\CoreBundle\Service\GatewayResourceService;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Valid;

class ZGWDocumentToFileService
{

    /**
     * @var array $configuration
     */
    private array $configuration;

    /**
     * @var array $data
     */
    private array $data;

    /**
     * @var EntityManagerInterface $entityManager
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var ParameterBagInterface $parameterBag
     */
    private ParameterBagInterface $parameterBag;

    /**
     * The Resource service.
     *
     * @var GatewayResourceService $resourceService
     */
    private GatewayResourceService $resourceService;

    /**
     * The Mapping service.
     *
     * @var MappingService $mappingService
     */
    private MappingService $mappingService;

    /**
     * The Synchronization service.
     *
     * @var SynchronizationService $syncService
     */
    private SynchronizationService $syncService;

    /**
     * The Cache service.
     *
     * @var CacheService $cacheService
     */
    private CacheService $cacheService;

    /**
     * The ZGW To VrijBRP service.
     *
     * @var ZgwToVrijbrpService $zgwToVrijbrpService
     */
    private ZgwToVrijbrpService $zgwToVrijbrpService;

    /**
     * The plugin logger.
     *
     * @var LoggerInterface $logger
     */
    private LoggerInterface $logger;


    /**
     * @param EntityManagerInterface $entityManager       The Entity Manager.
     * @param ParameterBagInterface  $parameterBag        The Parameter Bag Interface
     * @param GatewayResourceService $resourceService     The Gateway Resource Service.
     * @param MappingService         $mappingService      The Mapping Service
     * @param SynchronizationService $syncService         The Synchronization Service.
     * @param CacheService $cacheService The Cache Service.
     * @param ZgwToVrijbrpService    $zgwToVrijbrpService The ZGW To VrijBRP Service
     * @param LoggerInterface        $pluginLogger        The plugin version of the logger interface.
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ParameterBagInterface $parameterBag,
        GatewayResourceService $resourceService,
        MappingService $mappingService,
        SynchronizationService $syncService,
        CacheService $cacheService,
        ZgwToVrijbrpService $zgwToVrijbrpService,
        LoggerInterface $pluginLogger
    ) {
        $this->entityManager       = $entityManager;
        $this->parameterBag        = $parameterBag;
        $this->resourceService     = $resourceService;
        $this->mappingService      = $mappingService;
        $this->syncService         = $syncService;
        $this->cacheService = $cacheService;
        $this->zgwToVrijbrpService = $zgwToVrijbrpService;
        $this->logger              = $pluginLogger;
        $this->configuration       = [];
        $this->data                = [];

    }//end __construct()


    /**
     * Generates the content for a new file part.
     *
     * @param ObjectEntity $object The object to create a filepart for.
     * @param int          $index  The index of the filepart
     * @param int          $size   The size (in Bytes) of the filepart
     * @param string       $lock   The loc belonging to the object.
     *
     * @return array The resulting filepart.
     */
    public function createFilePart(ObjectEntity $object, int $index, int $size, string $lock): array
    {
        return [
            'omvang'     => $size,
            'voltooid'   => false,
            'volgnummer' => ($index + 1),
            'lock'       => $lock,
        ];

    }//end createFilePart()


    /**
     * Generates a download endpoint from the id of an 'Enkelvoudig Informatie Object' and the endpoint for downloads.
     *
     * @param string   $id               The id of the Enkelvoudig Informatie Object.
     * @param Endpoint $downloadEndpoint The endpoint for downloads.
     *
     * @return string The endpoint to download the document from.
     */
    private function generateDownloadEndpoint(string $id, Endpoint $downloadEndpoint): string
    {
        // Unset the last / from the app_url.
        $baseUrl = rtrim($this->parameterBag->get('app_url'), '/');

        $pathArray = $downloadEndpoint->getPath();
        foreach ($pathArray as $key => $value) {
            if ($value == 'id' || $value == '[id]' || $value == '{id}') {
                $pathArray[$key] = $id;
            }
        }

        return $baseUrl.'/api/'.implode('/', $pathArray);

    }//end generateDownloadEndpoint()


    /**
     * Creates or updates a file associated with a given ObjectEntity instance.
     *
     * This method handles the logic for creating or updating a file based on
     * provided data. If an existing file is associated with the ObjectEntity,
     * it updates the file's properties; otherwise, it creates a new file.
     * It also sets the response data based on the method used (POST or other)
     * and if the `$setResponse` parameter is set to `true`.
     *
     * @param ObjectEntity $objectEntity The object entity associated with the file.
     * @param array        $data         Data associated with the file such as title, format, and content.
     *
     * @return void
     */
    public function createFile(ObjectEntity $objectEntity, array $data): File
    {
        if ($data['versie'] === null) {
            $objectEntity->hydrate(['versie' => 1]);
        }

        if ($data['versie'] !== null) {
            $objectEntity->hydrate(['versie' => ++$data['versie']]);
        }

        $file = new File();
        $file->setBase64('');
        $file->setMimeType(($data['formaat'] ?? 'application/pdf'));
        $file->setName($data['titel']);
        $file->setExtension('');
        $file->setSize(0);

        return $file;

    }//end createFile()


    /**
     * Creates or updates a file associated with a given ObjectEntity instance.
     *
     * This method handles the logic for creating or updating a file based on
     * provided data. If an existing file is associated with the ObjectEntity,
     * it updates the file's properties; otherwise, it creates a new file.
     * It also sets the response data based on the method used (POST or other)
     * and if the `$setResponse` parameter is set to `true`.
     *
     * @param ObjectEntity $objectEntity     The object entity associated with the file.
     * @param array        $data             Data associated with the file such as title, format, and content.
     * @param Endpoint     $downloadEndpoint Endpoint to use for downloading the file.
     * @param bool         $setResponse      Determines if a response should be set, default is `true`.
     *
     * @return void
     */
    public function getFilePart(ObjectEntity $objectEntity, array $data, Endpoint $downloadEndpoint, bool $setResponse=true): void
    {
        // Create the file with the data.
        $file = $this->createFile($objectEntity, $data);

        $parts = ceil(($data['bestandsomvang'] / 1000000));

        if (count($data['bestandsdelen']) >= $parts) {
            return;
        }

        $fileParts = [];

        if ($objectEntity->getLock() === null) {
            $lock = Uuid::uuid4()->toString();
            $objectEntity->setLock($lock);
        }

        for ($iterator = 0; $iterator < $parts; $iterator++) {
            $fileParts[] = $this->createFilePart($objectEntity, $iterator, ceil(($data['bestandsomvang'] / $parts)), $objectEntity->getLock());
        }

        $this->entityManager->persist($file);

        if ($this->data['method'] === 'POST') {
            $objectEntity->hydrate(['bestandsdelen' => $fileParts, 'lock' => $lock, 'locked' => true, 'inhoud' => $this->generateDownloadEndpoint($objectEntity->getId()->toString(), $downloadEndpoint)]);
        }

        if ($this->data['method'] !== 'POST') {
            $objectEntity->hydrate(['bestandsdelen' => $fileParts, 'inhoud' => $this->generateDownloadEndpoint($objectEntity->getId()->toString(), $downloadEndpoint)]);
        }

        $this->entityManager->persist($objectEntity);
        $this->entityManager->flush();

        if ($setResponse === true) {
            $this->data['response'] = new Response(
                \Safe\json_encode($objectEntity->toArray()),
                $this->data['method'] === 'POST' ? 201 : 200,
                ['content-type' => 'application/json']
            );
        }

    }//end getFilePart()


    /**
     * Creates or updates a file associated with a given ObjectEntity instance.
     *
     * This method handles the logic for creating or updating a file based on
     * provided data. If an existing file is associated with the ObjectEntity,
     * it updates the file's properties; otherwise, it creates a new file.
     * It also sets the response data based on the method used (POST or other)
     * and if the `$setResponse` parameter is set to `true`.
     *
     * @param ObjectEntity $objectEntity     The object entity associated with the file.
     * @param array        $data             Data associated with the file such as title, format, and content.
     * @param Endpoint     $downloadEndpoint Endpoint to use for downloading the file.
     * @param bool         $setResponse      Determines if a response should be set, default is `true`.
     *
     * @return void
     */
    public function createOrUpdateFile(ObjectEntity $objectEntity, array $data, Endpoint $downloadEndpoint, bool $setResponse=true): void
    {
        if ($objectEntity->getValueObject('inhoud') !== false && $objectEntity->getValueObject('inhoud')->getFiles()->count() > 0) {
            // Get the file from the inhoud object.
            $file = $objectEntity->getValueObject('inhoud')->getFiles()->first();
        }

        if ($objectEntity->getValueObject('inhoud') !== false && $objectEntity->getValueObject('inhoud')->getFiles()->count() === 0) {
            // Create the file with the data.
            $file = $this->createFile($objectEntity, $data);
        }

        if ($data['inhoud'] !== null && $data['inhoud'] !== '' && filter_var($data['inhoud'], FILTER_VALIDATE_URL) === false) {
            $file->setSize(mb_strlen(base64_decode($data['inhoud'])));
            $file->setBase64($data['inhoud']);
        }

        if ((($data['inhoud'] === null || filter_var($data['inhoud'], FILTER_VALIDATE_URL) === $data['inhoud'])
            && ($data['link'] === null || $data['link'] === ''))
            && isset($this->data['body']['bestandsomvang']) === true
        ) {
            // Creates a file part for the file.
            $this->getFilePart($objectEntity, $data, $downloadEndpoint, $setResponse);
        }//end if

        $file->setValue($objectEntity->getValueObject('inhoud'));
        $this->entityManager->persist($file);
        $objectEntity->hydrate(['inhoud' => $this->generateDownloadEndpoint($objectEntity->getId()->toString(), $downloadEndpoint)]);
        $this->entityManager->persist($objectEntity);
        $this->entityManager->flush();

        if ($setResponse === true) {
            $this->data['response'] = new Response(
                \Safe\json_encode($objectEntity->toArray(['embedded' => true])),
                $this->data['method'] === 'POST' ? 201 : 200,
                ['content-type' => 'application/json']
            );
        }

    }//end createOrUpdateFile()


    /**
     * A first registration handler that is triggered by an action.
     *
     * @param array $data          The data array
     * @param array $configuration The configuration array
     *
     * @return array A handler must ALWAYS return an array
     */
    public function zgwDocumentToFileHandler(array $data, array $configuration): array
    {
        $this->logger->info('Map ZGW zaak to First Registration.');
        $this->configuration = $configuration;
        $this->data          = $data;

        $schema = $this->resourceService->getSchema('https://vng.opencatalogi.nl/schemas/drc.enkelvoudigInformatieObject.schema.json', 'common-gateway/zds-to-zgw-bundle');
        if ($schema === null) {
            return $this->data;
        }

        // Get the informatieobject identification property.
        $identification = $this->data['body']['SOAP-ENV:Body']['ns2:edcLk01']['ns2:object']['ns2:identificatie'];

        // Search enkelvoudiginformatieobject objects with the identificatie in informatieobject identificatie.
        $documenten = $this->cacheService->searchObjects(null, ['identificatie' => $identification], [$schema->getId()->toString()])['results'];

        // Create error response if the document is not empty and if there is more then one result.
        if (empty($documenten) === false && count($documenten) > 1) {
            $this->logger->warning('More than one document exists with id '.$zaakDocumentArray['informatieobject']['identificatie']);

            return $this->data;
        }

        // Create error response if the document is empty.
        if (empty($documenten) === true) {
            $this->logger->warning('The document with id '.$zaakDocumentArray['informatieobject']['identificatie'].' does not exist');

            return $this->data;
        }

        // Get the document object.
        $informatieobject         = $this->entityManager->getRepository('App:ObjectEntity')->find($documenten[0]['_self']['id']);
        $downloadEndpoint = $this->entityManager->getRepository('App:Endpoint')->findOneBy(['reference' => $this->configuration['endpoint']]);
        if ($informatieobject instanceof ObjectEntity === false || $downloadEndpoint instanceof Endpoint === false) {
            return $this->data;
        }

        $this->createOrUpdateFile($informatieobject, $informatieobject->toArray(), $downloadEndpoint, false);

        return $this->data;

    }//end zgwDocumentToFileHandler()


}//end class
