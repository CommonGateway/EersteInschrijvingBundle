<?php
/**
 * An example handler for the per store.
 *
 * @author  Conduction.nl <info@conduction.nl>
 * @license EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace CommonGateway\FirstRegistrationBundle\ActionHandler;

use CommonGateway\CoreBundle\ActionHandler\ActionHandlerInterface;
use CommonGateway\FirstRegistrationBundle\Service\FirstRegistrationService;


class FirstRegistrationHandler implements ActionHandlerInterface
{

    /**
     * The pet store service used by the handler
     *
     * @var FirstRegistrationService
     */
    private FirstRegistrationService $service;


    /**
     * The constructor
     *
     * @param FirstRegistrationService $service The first registration service.
     */
    public function __construct(FirstRegistrationService $service)
    {
        $this->service = $service;

    }//end __construct()


    /**
     * Returns the required configuration as a https://json-schema.org array.
     *
     * @return array The configuration that this  action should comply to
     */
    public function getConfiguration(): array
    {
        return [
            '$id'         => 'https://vrijbrp.nl/vrijbrp.zaak.handler.json',
            '$schema'     => 'https://docs.commongateway.nl/schemas/ActionHandler.schema.json',
            'title'       => 'VrijbrpEersteInschrijvingHandler',
            'description' => 'This handler syncs EersteInschrijving to VrijBrp',
            'required'    => [
                'source',
                'location',
                'zaakEntity',
            ],
            'properties'  => [
                'source'   => [
                    'type'        => 'string',
                    'description' => 'The location of the Source we will send a request to, location of an existing Source object',
                    'example'     => 'https://vrijbrp.nl/source/vrijbrp.dossiers.source.json',
                    'required'    => true,
                    '$ref'        => 'https://vrijbrp.nl/source/vrijbrp.dossiers.source.json',
                ],
                'location' => [
                    'type'        => 'string',
                    'description' => 'The endpoint we will use on the Source to send a request, just a string',
                    'example'     => '/api/births',
                    'required'    => true,
                ],
                'schema'   => [
                    'type'        => 'string',
                    'description' => 'The reference of the entity we use as trigger for this handler, we need this to find a synchronization object',
                    'example'     => 'https://vrijbrp.nl/schemas/vrijbrp.dataImport.schema.json',
                    'required'    => true,
                    '$ref'        => 'https://vrijbrp.nl/schemas/vrijbrp.dataImport.schema.json',
                ],
            ],
        ];

    }//end getConfiguration()


    /**
     * This function runs the service.
     *
     * @param array $data          The data from the call
     * @param array $configuration The configuration of the action
     *
     * @return array
     *
     * @SuppressWarnings("unused") Handlers ara strict implementations
     */
    public function run(array $data, array $configuration): array
    {
        return $this->service->firstRegistrationHandler($data, $configuration);

    }//end run()


}//end class
