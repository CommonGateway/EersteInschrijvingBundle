<?php
/**
 * An example service for adding business logic to your class.
 *
 * @author  Conduction.nl <info@conduction.nl>
 * @license EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace CommonGateway\FirstRegistrationBundle\Service;

use CommonGateway\GeboorteVrijBRPBundle\Service\ZgwToVrijbrpService;
use Doctrine\ORM\EntityManagerInterface;
use CommonGateway\CoreBundle\Service\GatewayResourceService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class FirstRegistrationService
{

    /**
     * @var array
     */
    private array $configuration;

    /**
     * @var array
     */
    private array $data;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * The Resource service.
     *
     * @var GatewayResourceService $resourceService
     */
    private GatewayResourceService $resourceService;

    /**
     * The ZGW To VrijBRP service.
     *
     * @var ZgwToVrijbrpService $zgwToVrijbrpService
     */
    private ZgwToVrijbrpService $zgwToVrijbrpService;

    /**
     * The plugin logger.
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;


    /**
     * @param EntityManagerInterface $entityManager   The Entity Manager.
     * @param GatewayResourceService $resourceService The Gateway Resource Service.
     * @param ZgwToVrijbrpService    $zgwToVrijbrpService The ZGW To VrijBRP Service
     * @param LoggerInterface        $pluginLogger    The plugin version of the logger interface.
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        GatewayResourceService $resourceService,
        ZgwToVrijbrpService $zgwToVrijbrpService,
        LoggerInterface $pluginLogger
    ) {
        $this->entityManager   = $entityManager;
        $this->resourceService = $resourceService;
        $this->zgwToVrijbrpService = $zgwToVrijbrpService;
        $this->logger          = $pluginLogger;
        $this->configuration   = [];
        $this->data            = [];

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

        $source                = $this->resourceService->getSource('https://vrijbrp.nl/source/vrijbrp.dossiers.source.json', 'common-gateway/first-registration-bundle');
        $synchronizationEntity = $this->resourceService->getSchema('https://vrijbrp.nl/schemas/vrijbrp.dataImport.schema.json', 'common-gateway/first-registration-bundle');

        if ($source === null
            || $synchronizationEntity === null
        ) {
            return [];
        }

        $content = \Safe\json_decode($this->data['response']->getContent(), true);
        $dataId = $content['_self']['id'];

        $object = $this->entityManager->getRepository('App:ObjectEntity')->find($dataId);
        $this->logger->debug("EersteInschrijving Object with id $dataId was created");

        $objectArray = $object->toArray();
        $objectArray = $this->removeSelf($objectArray);

        // @TODO $objectArray unset _self etc..
        // Create synchronization.
        $synchronization = $this->zgwToVrijbrpService->getSynchronization($object, $source, $synchronizationEntity);

        $this->logger->debug("Synchronize (Zaak) Object to: {$source->getLocation()}".$this->configuration['location']);
        // Todo: change synchronize function so it can also push to a source and not only pull from a source:
        // $this->syncService->synchronize($synchronization, $objectArray);
        // Todo: temp way of doing this without updated synchronize() function...
        if ($data = $this->zgwToVrijbrpService->synchronizeTemp($synchronization, $objectArray, $this->configuration['location'])) {
            // Return empty array on error for when we got here through a command.

            return ['response' => new Response(\Safe\json_encode($data), 201, ['content-type' => 'application/json'])];
        }

        return $this->data;

    }//end firstRegistrationHandler()


}//end class
