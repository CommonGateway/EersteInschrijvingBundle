<?php
/**
 * An example service for adding business logic to your class.
 *
 * @author  Conduction.nl <info@conduction.nl>
 * @license EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace CommonGateway\EersteInschrijvingBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use CommonGateway\CoreBundle\Service\GatewayResourceService;
use Psr\Log\LoggerInterface;

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
     * The plugin logger.
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;


    /**
     * @param EntityManagerInterface $entityManager   The Entity Manager.
     * @param GatewayResourceService $resourceService The Gateway Resource Service.
     * @param LoggerInterface        $pluginLogger    The plugin version of the logger interface.
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        GatewayResourceService $resourceService,
        LoggerInterface $pluginLogger
    ) {
        $this->entityManager   = $entityManager;
        $this->resourceService = $resourceService;
        $this->logger          = $pluginLogger;
        $this->configuration   = [];
        $this->data            = [];

    }//end __construct()


    // **
    // * An example handler that is triggered by an action.
    // *
    // * @param array $data          The data array
    // * @param array $configuration The configuration array
    // *
    // * @return array A handler must ALWAYS return an array
    // */
    // public function firstRegistrationHandler(array $data, array $configuration): array
    // {
    // $this->data          = $data;
    // $this->configuration = $configuration;
    //
    // $this->logger->debug("FirstRegistrationService -> firstRegistrationHandler()");
    //
    // return ['response' => 'Hello. Your EersteInschrijvingBundle works'];
    //
    // }//end petStoreHandler()


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

        $source                = $this->resourceService->getSource($this->configuration['source'], 'common-gateway/first-registration-bundle');
        $synchronizationEntity = $this->resourceService->getSchema($this->configuration['synchronizationEntity'], 'common-gateway/first-registration-bundle');

        if ($source === null
            || $synchronizationEntity === null
        ) {
            return [];
        }

        $dataId = $this->data['response']->_id;

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
            return ['response' => $data];
        }

        return $data;

    }//end firstRegistrationHandler()


}//end class
