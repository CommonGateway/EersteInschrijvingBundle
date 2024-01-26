<?php
/**
 * A ZGW zaak to first registration handler.
 *
 * @author  Conduction.nl <info@conduction.nl>, Sarai Misidjan <sarai@conduction.nl>
 * @license EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

namespace CommonGateway\FirstRegistrationBundle\ActionHandler;

use CommonGateway\CoreBundle\ActionHandler\ActionHandlerInterface;
use CommonGateway\FirstRegistrationBundle\Service\ZGWDocumentToFileService;


class ZGWDocumentToFileHandler implements ActionHandlerInterface
{

    /**
     * The pet store service used by the handler
     *
     * @var ZGWDocumentToFileService
     */
    private ZGWDocumentToFileService $service;


    /**
     * The constructor
     *
     * @param ZGWDocumentToFileService $service The first registration service.
     */
    public function __construct(ZGWDocumentToFileService $service)
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
            '$id'         => 'https://vrijbrp.nl/vrijbrp.ZGWDocumentToFileHandler.handler.json',
            '$schema'     => 'https://docs.commongateway.nl/schemas/ActionHandler.schema.json',
            'title'       => 'ZGWDocumentToFileHandler',
            'description' => 'This handler syncs EersteInschrijving to VrijBrp',
            'required'    => ['endpoint'],
            'properties'  => [
                'endpoint' => [
                    'type'        => 'string',
                    'description' => 'The endpoint we will use for the download url.',
                    'example'     => 'https://vng.opencatalogi.nl/endpoints/drc.downloadEnkelvoudigInformatieObject.endpoint.json',
                    'required'    => true,
                    '$ref'        => 'https://vng.opencatalogi.nl/endpoints/drc.downloadEnkelvoudigInformatieObject.endpoint.json',
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
        return $this->service->zgwDocumentToFileHandler($data, $configuration);

    }//end run()


}//end class
