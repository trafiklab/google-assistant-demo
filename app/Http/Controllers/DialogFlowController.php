<?php
/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

namespace App\Http\Controllers;

use App\Http\Models\DialogFlowPayload;
use App\Http\Requests\DialogflowRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Routing\Controller as BaseController;

/**
 * Class DialogFlowController.
 *
 * This is a common class used to fulfill DialogFlow intents. It can parse requests and build responses, and can call
 * the right method based on the used Intent. See https://dialogflow.com/docs/fulfillment for more.
 * information.
 *
 * @package App\Http\Controllers
 */
class DialogFlowController extends BaseController
{

    /**
     * Send a JSON response to DialogFlow in order to make google assistant answer the question.
     *
     * @param string $responseText
     *
     * @return JsonResponse Json reply for DialogFlow.
     */
    public static function createTextToSpeechResponse(string $responseText, bool $isEndOfConversation = true)
    {
        return response()->json(self::buildDialogFlowResponse($responseText));
    }

    /**
     * Build an array with the necessary
     *
     * @param string $responseText        The text which should be spoken by, for example, Google Assistant.
     * @param bool   $isEndOfConversation Determines if this is the end of a conversation. If true, the assistant will
     *                                    go to sleep after speaking. If false, the assistant will keep listening for
     *                                    more questions.
     *
     * @return array An array which can be serialized later on.
     */
    public static function buildDialogFlowResponse(string $responseText, bool $isEndOfConversation = true): array
    {
        return [
            'payload' => [
                'google' => [
                    'expectUserResponse' => $isEndOfConversation,
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
     * This method is used to accept multiple DialogFlow intents on a single endpoint.
     * All intents are sent to this endpoint, where they get filtered on their payload.
     * Depending on their payload, a controller class of the right type is created to answer the query.
     * We can't use redirects here, as the incoming DialogFlow request is a POST request, and we need to keep the body.
     *
     * @noinspection PhpUnused
     */
    public function redirectIntentToController(DialogflowRequest $request): JsonResponse
    {
        switch ($request->_dialogFlowPayload->getAction()) {
            case 'next-departure':
                // There are likely nicer options to do this, and definitely a more efficient one,
                // but it ain't stupid if it works
                $controller = new NextDepartureController();
                return $controller->getNextDeparture($request);
            case 'plan-route':
                // There are likely nicer options to do this, and definitely a more efficient one,
                // but it ain't stupid if it works
                $controller = new RoutePlanningController();
                return $controller->getRoutePlanning($request);
            default:
                return self::createTextToSpeechResponse(
                    "I can only tell you about the next departures or plan routes for you.");
        }
    }
}
