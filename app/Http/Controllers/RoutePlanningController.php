<?php /**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

/** @noinspection PhpCSValidationInspection */

namespace App\Http\Controllers;

use App\Http\Requests\DialogflowRequest;
use Illuminate\Support\Facades\Log;
use Trafiklab\Common\Model\Contract\PublicTransportApiWrapper;
use Trafiklab\Common\Model\Contract\StopLocationLookupEntry;
use Trafiklab\Common\Model\Contract\Trip;
use Trafiklab\Common\Model\Enum\RoutePlanningLegType;
use Trafiklab\Common\Model\Exceptions\InvalidKeyException;
use Trafiklab\Common\Model\Exceptions\InvalidRequestException;
use Trafiklab\Common\Model\Exceptions\InvalidStoplocationException;
use Trafiklab\Common\Model\Exceptions\KeyRequiredException;
use Trafiklab\Common\Model\Exceptions\QuotaExceededException;
use Trafiklab\Common\Model\Exceptions\RequestTimedOutException;
use Trafiklab\Common\Model\Exceptions\ServiceUnavailableException;

class RoutePlanningController extends DialogFlowController
{

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRoutePlanning(DialogflowRequest $request)
    {
        try {
            /**
             * @var $apiWrapper PublicTransportApiWrapper A wrapper for SL, ResRobot, ...
             */
            $apiWrapper = app(PublicTransportApiWrapper::class);

            // Use a StopLocationLookup (Platsuppslag) API to get the ids of the origin and destination stop.
            // Since we need to lookup multiple stop locations, a method was created to do this.
            $origin = $this->getStopLocation($apiWrapper,
                $request->getDialogFlowPayload()->getParameter('origin'));
            $destination = $this->getStopLocation($apiWrapper,
                $request->getDialogFlowPayload()->getParameter('destination'));

            // Create a new RoutePlanningRequest object.
            $routePlanningRequest = $apiWrapper->createRoutePlanningRequestObject();

            // Set the origin and destination id, which we obtained before through the Stop lookup API
            $routePlanningRequest->setOriginStopId($origin->getId());
            $routePlanningRequest->setDestinationStopId($destination->getId());
            $response = $apiWrapper->getRoutePlanning($routePlanningRequest);

            // In case no result was found, we tell this to user.
            if (count($response->getTrips()) == 0) {
                return $this->createTextToSpeechResponse("I could not find any route from " . $origin->getName() . " to " . $destination->getName());
            }

            // We will tell the user about the first found result
            $routePlan = $response->getTrips()[0];

            // Start building the response.
            $responseText = "I found you the following route from {$origin->getName()} to {$destination->getName()}. " . PHP_EOL;

            // Tell the user about every leg in their journey.
            foreach ($routePlan->getLegs() as $i => $leg) {

                // There are two types of legs (at this moment): Vehicle journeys, where a vehicle is used, or walking parts
                // where a user walks between two stations. Not all fields are available for walking parts, so we need to handle them differently.

                if ($leg->getType() == RoutePlanningLegType::VEHICLE_JOURNEY) {
                    // The user needs to take a train/bus/...
                    $responseText .= $this->getStartOfSentence($i, $routePlan, "take");

                    // Explain when, from where, to where.
                    $responseText .= strtolower($leg->getVehicle()->getType())
                        . " {$leg->getVehicle()->getNumber()} towards {$leg->getDirection()} "
                        . "from {$leg->getDeparture()->getStopName()} "
                        . "at {$leg->getDeparture()->getScheduledDepartureTime()->format("H:i")}. " . PHP_EOL;
                    $responseText .= "Ride along for " . ($leg->getDuration()) / 60 . " minutes, then ";
                    $responseText .= "exit the vehicle in " . $leg->getArrival()->getStopName() . ". " . PHP_EOL;

                } else {
                    if ($leg->getType() == RoutePlanningLegType::WALKING) {
                        // The user needs to walk. We will simply say from where, to where.
                        $responseText .= $this->getStartOfSentence($i, $routePlan, "walk");
                        $responseText .= "from {$leg->getDeparture()->getStopName()} to {$leg->getArrival()->getStopName()}" . PHP_EOL;
                    }
                }
            }
            $responseText .= "You have then arrived at your destination.";
            return $this->createTextToSpeechResponse($responseText);


            /**
             *  Exception handling starts here
             *
             *  Below we handle all kinds of exceptions which can be thrown by our SDKs.
             *  This way we can handle API errors in a nice way.
             *
             *  The quality of the exceptions thrown depends on the used API.
             *  Some APIs might redirect everything to the general InvalidRequestException.
             *
             *  Handling exceptions and gracefully degrading is an important part of providing a good user experience.
             *
             * The order of catching Exceptions is important. Catching InvalidRequestExceptions first will also catch
             * InvalidKeyExceptions, InvalidStopLocationException, ... due to their hierarchy/inheritance.
             **/
        } catch (InvalidKeyException|KeyRequiredException $e) {
            Log::error($e->getMessage() . " at " . $e->getFile() . " : " . $e->getLine());
            return $this->createTextToSpeechResponse("I would like to answer you, but I don't have the right keys");
        } catch (InvalidStoplocationException $e) {
            Log::error($e->getMessage() . " at " . $e->getFile() . " : " . $e->getLine());
            return $this->createTextToSpeechResponse("I would like to answer you, but don't know that station");
        } catch (InvalidRequestException $e) {
            Log::error($e->getMessage() . " at " . $e->getFile() . " : " . $e->getLine());
            return $this->createTextToSpeechResponse("I would like to answer you, but I didn't get all  the details");
        } catch (QuotaExceededException $e) {
            Log::error($e->getMessage() . " at " . $e->getFile() . " : " . $e->getLine());
            return $this->createTextToSpeechResponse("I would like to answer you, but I already talked too much");
        } catch (RequestTimedOutException $e) {
            Log::error($e->getMessage() . " at " . $e->getFile() . " : " . $e->getLine());
            return $this->createTextToSpeechResponse("I could not obtain this data, it took too long");
        } catch (ServiceUnavailableException $e) {
            Log::error($e->getMessage() . " at " . $e->getFile() . " : " . $e->getLine());
            return $this->createTextToSpeechResponse("I could not obtain this data, the service is not available");
        }
    }

    /**
     * Get the StopLocation for a certain stop name. All exceptions are thrown to be handled on a higer level.
     *
     * @param PublicTransportApiWrapper $apiWrapper
     *
     * @param string $id
     *
     * @return StopLocationLookupEntry
     * @throws InvalidKeyException
     * @throws InvalidRequestException
     * @throws InvalidStopLocationException
     * @throws KeyRequiredException
     * @throws QuotaExceededException
     * @throws RequestTimedOutException
     * @throws ServiceUnavailableException
     */
    public function getStopLocation(PublicTransportApiWrapper $apiWrapper, string $id): StopLocationLookupEntry
    {
        // Create a request object.
        $locationLookupRequest = $apiWrapper->createStopLocationLookupRequestObject();
        // Set the query id.
        $locationLookupRequest->setSearchQuery($id);
        // Make the request.
        $stopLocationLookupResponse = $apiWrapper->lookupStopLocation($locationLookupRequest);
        // Return the first result.
        return $stopLocationLookupResponse->getFoundStopLocations()[0];
    }

    /**
     * @param        $legIndexInTrip int For which leg will the sentence be used.
     * @param        $trip           Trip The trip which is being described.
     * @param string $action         What the traveller will do. "Take" a vehicle, "Walk" between stations, "Cycle"
     *                               from ...
     *
     * @return string The start of the sentence for the $legIndexInTrip-th leg in the trip
     */
    public function getStartOfSentence(int $legIndexInTrip, Trip $trip, string $action): string
    {
        if ($legIndexInTrip == 0) {
            if (count($trip->getLegs()) == 1) {
                // If there is only one leg, there is no need for first...then..
                // ucfirst will uppercase the first character.
                $fancyContinuation = ucfirst($action) . " ";
            } else {
                // This is the first leg in the trip, so we start with First ...
                $fancyContinuation = "First $action ";
            }
        } else {
            // This is not the first leg in the trip, so we continue our response with Then ...
            $fancyContinuation = "Then $action ";
        }
        return $fancyContinuation;
    }


}
