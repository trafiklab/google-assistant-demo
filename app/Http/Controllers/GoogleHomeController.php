<?php

namespace App\Http\Controllers;

use App\Http\Models\DialogFlowPayload;
use Laravel\Lumen\Routing\Controller as BaseController;

class GoogleHomeController extends BaseController
{

    private $_dialogFlowPayload;

    public function __construct(array $jsonPayload)
    {
        $this->_dialogFlowPayload = new DialogFlowPayload($jsonPayload);
    }

    /**
     * Send a JSON response to Google Dialogflow in order to make google assistant answer the question.
     *
     * @param string $responseText
     */
    public function respondWithTextToSpeech(string $responseText)
    {
        response()->json($this->buildGoogleAssistantResponse($responseText));
    }

    /**
     * @param string $responseText
     *
     * @return array
     */
    public function buildGoogleAssistantResponse(string $responseText): array
    {
        return [
            'payload' => [
                'google' => [
                    'expectUserResponse' => false,
                    "richResponse" => [
                        "items" => [
                            [
                                "simpleResponse" => [
                                    "textToSpeech" => "$responseText",
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get the payload sent to the server by Dialogflow
     *
     * @return DialogFlowPayload
     */
    public function getDialogFlowPayload(): DialogFlowPayload
    {
        return $this->_dialogFlowPayload;
    }
}
