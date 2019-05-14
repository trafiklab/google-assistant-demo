<?php

namespace App\Http\Controllers;

use App\Http\Models\DialogFlowPayload;
use Illuminate\Http\JsonResponse;
use Laravel\Lumen\Routing\Controller as BaseController;

class DialogFlowController extends BaseController
{

    private $_dialogFlowPayload;

    public function __construct(array $jsonPayload)
    {
        $this->_dialogFlowPayload = new DialogFlowPayload($jsonPayload);
    }

    /**
     * Send a JSON response to Dialogflow in order to make google assistant answer the question.
     *
     * @param string $responseText
     *
     * @return JsonResponse Json reply for dialogflow.
     */
    public function createTextToSpeechResponse(string $responseText)
    {
        return response()->json($this->buildDialogFlowResponse($responseText));
    }

    /**
     * @param string $responseText
     *
     * @return array
     */
    public function buildDialogFlowResponse(string $responseText): array
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

    public function redirectIntentToController()
    {
        switch ($this->_dialogFlowPayload->getIntentDisplayName()) {
            case 'next-departure':
                return redirect('getNextDeparture');
            case 'plan-route':
                return redirect('getRoutePlanning');
            default:
                return $this->buildDialogFlowResponse("I can only tell you about the next departures or plan routes for you");
        }
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
