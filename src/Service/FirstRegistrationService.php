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

class FirstRegistrationService
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
     * @param GatewayResourceService $resourceService     The Gateway Resource Service.
     * @param MappingService         $mappingService      The Mapping Service
     * @param CacheService           $cacheService        The Cache Service.
     * @param ZgwToVrijbrpService    $zgwToVrijbrpService The ZGW To VrijBRP Service
     * @param LoggerInterface        $pluginLogger        The plugin version of the logger interface.
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        GatewayResourceService $resourceService,
        MappingService $mappingService,
        CacheService $cacheService,
        ZgwToVrijbrpService $zgwToVrijbrpService,
        LoggerInterface $pluginLogger
    ) {
        $this->entityManager       = $entityManager;
        $this->resourceService     = $resourceService;
        $this->mappingService      = $mappingService;
        $this->cacheService        = $cacheService;
        $this->zgwToVrijbrpService = $zgwToVrijbrpService;
        $this->logger              = $pluginLogger;
        $this->configuration       = [];
        $this->data                = [];

    }//end __construct()


    /**
     * Recursively removes self parameters from object.
     *
     * @param array $object The object to remove self parameters from.
     *
     * @return array The cleaned object.
     */
    public function removeSelf(array $object): array
    {
        if (isset($object['_self']) === true) {
            unset($object['_self']);
        }

        foreach ($object as $key => $value) {
            if (is_array($value)) {
                $object[$key] = $this->removeSelf($value);
            }
        }

        return $object;

    }//end removeSelf()


    /**
     * Gets the zaaktype object from the zaak.
     *
     * @return ObjectEntity|null The zaaktype from the zaak.
     */
    public function getZaaktype(): ?ObjectEntity
    {
        // Get the zaaktype from the response.
        $zaaktype = $this->data['response']['zaaktype'];

        if (Uuid::isValid($zaaktype) === true) {
            $zaaktypeId = $zaaktype;
        }

        if (Uuid::isValid($zaaktype) === false) {
            // Get the path of the zaaktype url.
            $zaaktypePath = \Safe\parse_url($zaaktype)['path'];

            // Get the id of the zaaktype path.
            $zaaktypeId = explode('/api/ztc/v1/zaaktypen/', $zaaktypePath)[1];
        }

        // Get the zaaktype object.
        $zaaktypeObject = $this->entityManager->getRepository('App:ObjectEntity')->find($zaaktypeId);
        if ($zaaktypeObject === null) {
            return null;
        }

        return $zaaktypeObject;

    }//end getZaaktype()


    /**
     * Gets the values from the zaakEigenschappen of the zaak.
     *
     * @param ObjectEntity $zaaktypeObject The zaaktype object of the zaak.
     * @param ObjectEntity $zaakObject     The zaak object.
     *
     * @return array The values of the zaakEigenschappen.
     */
    public function getZaakEigenschappenValues(ObjectEntity $zaaktypeObject, ObjectEntity $zaakObject): array
    {
        $eigenschapNames = [];
        foreach ($zaaktypeObject->getValue('eigenschappen') as $eigenschap) {
            // Set the naam of the eigenshap to the array.
            $eigenschapNames[] = $eigenschap->getValue('naam');
        }

        $zaakEigenschapValues = [];
        $countLandcode        = 0;
        // Loop through the zaakEigenschappen. This is needed to fill the values of the importRecord.schema.json.
        foreach ($zaakObject->getValue('eigenschappen') as $zaakEigenschap) {
            // If the zaakEigenschap is oneOf the eigenschappen from the zaaktype then add it to the array.
            // Don't do anything if the zaakeigenschap is a landcode.
            if (in_array($zaakEigenschap->getValue('naam'), $eigenschapNames) === true && $zaakEigenschap->getValue('naam') !== 'landcode') {
                $zaakEigenschapValues[$zaakEigenschap->getValue('naam')] = $zaakEigenschap->getValue('waarde');

                continue;
            }

            // If the zaakEigenschap is oneOf the eigenschappen from the zaaktype and the name is landcode.
            // Set the countLandcode with +1 and set the right key.
            if (in_array($zaakEigenschap->getValue('naam'), $eigenschapNames) === true && $zaakEigenschap->getValue('naam') === 'landcode') {
                if ($countLandcode === 0) {
                    $zaakEigenschapValues['geboorteland'] = $zaakEigenschap->getValue('waarde');
                }

                if ($countLandcode !== 0) {
                    $zaakEigenschapValues['land_van_herkomst'] = $zaakEigenschap->getValue('waarde');
                }

                $countLandcode++;
            }
        }//end foreach

        return $zaakEigenschapValues;

    }//end getZaakEigenschappenValues()


    /**
     * Gets the values from the zaakEigenschappen of the zaak.
     *
     * @param ObjectEntity $zaaktypeObject The zaaktype object of the zaak.
     * @param ObjectEntity $zaakObject     The zaak object.
     *
     * @return array The values of the zaakEigenschappen.
     */
    public function getRolValues(ObjectEntity $zaaktypeObject, ObjectEntity $zaakObject): array
    {
        // TODO: is it always a natuurlijk persoon? Set roltype to test zaaktype.
        $roltypeUrl = null;
        foreach ($zaaktypeObject->getValue('roltypen') as $roltype) {
            // If the omschrijvingGeneriek is initiator then set the roltype.
            if ($roltype->getValue('omschrijvingGeneriek') === 'initiator') {
                $roltypeUrl = $roltype->getValue('url');
            }
        }

        // Loop through the rollen. This is needed to fill the values of the importRecord.schema.json.
        foreach ($zaakObject->getValue('rollen') as $rol) {
            // If the rol.roltype is the same as the roltype from the zaaktype then return the rol.
            if ($rol->getValue('roltype')->getValue('url') === $roltypeUrl) {
                return $rol->toArray();
            }
        }

        return [];

    }//end getRolValues()


    /**
     * A first registration handler that is triggered by an action.
     *
     * @param array $data          The data array
     * @param array $configuration The configuration array
     *
     * @return array A handler must ALWAYS return an array
     */
    public function zgwToFirstRegistrationHandler(array $data, array $configuration): array
    {
        $this->logger->info('Map ZGW zaak to First Registration.');
        $this->configuration = $configuration;
        $this->data          = $data;

        $schema = $this->resourceService->getSchema('https://vng.opencatalogi.nl/schemas/zrc.zaak.schema.json', 'common-gateway/zds-to-zgw-bundle');
        if ($schema === null) {
            return $this->data;
        }

        // Get the zaak identification property.
        $identification = $this->data['body']['SOAP-ENV:Body']['ns2:edcLk01']['ns2:object']['ns2:isRelevantVoor']['ns2:gerelateerde']['ns2:identificatie'];

        // Search zaak objects with the identification.
        $zaken = $this->cacheService->searchObjects(null, ['identificatie' => $identification], [$schema->getId()->toString()])['results'];

        // Create error response if the document is not empty and if there is more then one result.
        if (empty($zaken) === false && count($zaken) > 1) {
            $this->logger->warning('More than one document exists with id '.$identification);

            return $this->data;
        }

        // Create error response if the document is empty.
        if (empty($zaken) === true) {
            $this->logger->warning('The document with id '.$identification.' does not exist');

            return $this->data;
        }

        // Get the document object.
        $zaakObject = $this->entityManager->getRepository('App:ObjectEntity')->find($zaken[0]['_self']['id']);
        if ($zaakObject instanceof ObjectEntity === false) {
            return $this->data;
        }

        $zaaktypeObject = $zaakObject->getValue('zaaktype');

        // Get the zaakEigenschap and rol values of the zaak.
        $values['zaakEigenschapValues'] = $this->getZaakEigenschappenValues($zaaktypeObject, $zaakObject);
        $values['rolValues']            = $this->getRolValues($zaaktypeObject, $zaakObject);
        $values['zaak']                 = $zaakObject->toArray();

        // Get the value mapping object.
        $valueMapping = $this->resourceService->getMapping($this->configuration['valuesMapping'], 'common-gateway/first-registration-bundle');
        if ($valueMapping === null) {
            return $this->data;
        }

        // Here we have the zaakEigenschappen and the rol we need to fill the importRecord.schema.json.
        $valuesArray['values'] = $this->mappingService->mapping($valueMapping, $values);

        $dataImportSchema = $this->resourceService->getSchema($this->configuration['schema'], 'common-gateway/first-registration-bundle');
        if ($dataImportSchema === null) {
            return $this->data;
        }

        // Loop through the
        $documents = [];
        $zaakInfoObjectSchema = $this->resourceService->getSchema('https://vng.opencatalogi.nl/schemas/zrc.zaak.schema.json', 'common-gateway/zds-to-zgw-bundle');
        $zaakInformatieObjecten = $this->cacheService->searchObjects(null, ['embedded.zaak.identificatie' => $identification], [$zaakInfoObjectSchema->getId()->toString()])['results'];

        foreach ($zaakInformatieObjecten as $zaakInfoArray) {
            $zaakInfoObject = $this->entityManager->getRepository(ObjectEntity::class)->find($zaakInfoArray['_id']);
            $informatieObject = $zaakInfoObject->getValue('informatieobject');

            if ($informatieObject->getValueObject('inhoud') !== false && $informatieObject->getValueObject('inhoud')->getFiles()->count() > 0) {
                // Get the file from the inhoud object.
                $file = $informatieObject->getValueObject('inhoud')->getFiles()->first();
            }

            if (isset($file) === false) {
                continue;
            }

            $documents['documents'][] = [
                'title'    => $file->getName(),
                'filename' => $file->getName(),
                'content'  => $file->getBase64(),
            ];
        }

        // Merge the values and documents array.
        $mappingArray = array_merge($valuesArray, $documents);

        if ($zaakObject->getValue('zaaktype')->getValue('identificatie') === 'B334') {
            $dataImportArray['name'] = 'Eerste Inschrijving Expat ZDS';
        } else {
            $dataImportArray['name'] = 'Eerste Inschrijving ZDS';
        }

        $dataImportArray['type'] = 'first_registrants_2022';

        // Create the dataImport array.
        $dataImportArray['records'][] = $mappingArray;

        // TODO: Get naam and type of the dataImport.
        // Create and hydrate the dataImport object.
        $dataImport = new ObjectEntity($dataImportSchema);
        $dataImport->hydrate($dataImportArray);

        $this->entityManager->persist($dataImport);
        $this->entityManager->flush();

        return $this->sendFirstRegistration($dataImport);

    }//end zgwToFirstRegistrationHandler()


    /**
     * A first registration handler that is triggered by an action.
     *
     * @param array $data          The data array
     * @param array $configuration The configuration array
     *
     * @return array A handler must ALWAYS return an array
     */
    public function sendFirstRegistration(ObjectEntity $object): array
    {
        $source = $this->resourceService->getSource($this->configuration['source'], 'common-gateway/first-registration-bundle');
        $schema = $this->resourceService->getSchema($this->configuration['schema'], 'common-gateway/first-registration-bundle');

        if ($source === null || $schema === null) {
            return [];
        }

        $this->logger->debug("EersteInschrijving Object with id {$object->getId()->toString()} was created");

        $objectArray = $object->toArray();
        $objectArray = $this->removeSelf($objectArray);

        // @TODO $objectArray unset _self etc..
        // Create synchronization.
        $synchronization = $this->zgwToVrijbrpService->getSynchronization($object, $source, $schema);

        $this->logger->debug("Synchronize (Zaak) Object to: {$source->getLocation()}".$this->configuration['location']);
        // Todo: change synchronize function so it can also push to a source and not only pull from a source:
        // $this->syncService->synchronize($synchronization, $objectArray);
        // Todo: temp way of doing this without updated synchronize() function...
        if ($data = $this->zgwToVrijbrpService->synchronizeTemp($synchronization, $objectArray, $this->configuration['location'])) {
            // Return empty array on error for when we got here through a command.
            $this->data['response'] = new Response(\Safe\json_encode($data), 201, ['content-type' => 'application/json']);

            return $this->data;
        }

        return $this->data;

    }//end sendFirstRegistration()


    /**
     * A first registration handler that is triggered by an action.
     *
     * @param array $data          The data array
     * @param array $configuration The configuration array
     *
     * @return array A handler must ALWAYS return an array
     */
    public function firstRegistrationHandler(array $data, array $configuration): array
    {
        $this->logger->info('Syncing EersteInschrijving object to VrijBRP');
        $this->configuration = $configuration;
        $this->data          = $data;

        $content = \Safe\json_decode($this->data['response']->getContent(), true);
        $dataId  = $content['_self']['id'];

        $object = $this->entityManager->getRepository('App:ObjectEntity')->find($dataId);
        if ($object instanceof ObjectEntity === false) {
            return $this->data;
        }

        return $this->sendFirstRegistration($object);

    }//end firstRegistrationHandler()


}//end class
