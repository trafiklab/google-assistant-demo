<?php

namespace App\Http\Controllers;

use App\Http\Models\DialogFlowPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Routing\Controller as BaseController;

class DialogFlowController extends BaseController
{

    private $_dialogFlowPayload;
    private $_request;

    public function __construct(Request $request)
    {
        Log::info("DialogFlowController constructing. POST Payload: " . $request->getContent());
        $this->_request = $request;
        $this->_dialogFlowPayload = new DialogFlowPayload(json_decode($request->getContent(), true));
    }

    /**
     * Send a JSON response to Dialogflow in order to make google assistant answer the question.
     *
     * @param string $responseText
     *
     * @return JsonResponse Json reply for dialogflow.
     */
    public static function createTextToSpeechResponse(string $responseText)
    {
        return response()->json(self::buildDialogFlowResponse($responseText));
    }

    /**
     * @param string $responseText
     *
     * @return array
     */
    public static function buildDialogFlowResponse(string $responseText): array
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
                // There are likely nicer options to do this, and definitely a more efficient one, but it ain't stupid if it works
                $controller = new NextDepartureController($this->_request);
                return $controller->getNextDeparture();
            case 'plan-route':
                // There are likely nicer options to do this, and definitely a more efficient one, but it ain't stupid if it works
                $controller = new RoutePlanningController($this->_request);
                return $controller->getRoutePlanning();
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
