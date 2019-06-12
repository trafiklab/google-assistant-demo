<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Trafiklab\Common\Model\Contract\PublicTransportApiWrapper;
use Trafiklab\Common\Model\Contract\TimeTableEntry;
use Trafiklab\Common\Model\Enum\TimeTableType;
use Trafiklab\Common\Model\Enum\TransportType;
use Trafiklab\Common\Model\Exceptions\InvalidKeyException;
use Trafiklab\Common\Model\Exceptions\InvalidRequestException;
use Trafiklab\Common\Model\Exceptions\InvalidStoplocationException;
use Trafiklab\Common\Model\Exceptions\KeyRequiredException;
use Trafiklab\Common\Model\Exceptions\QuotaExceededException;
use Trafiklab\Common\Model\Exceptions\RequestTimedOutException;
use Trafiklab\Common\Model\Exceptions\ServiceUnavailableException;

/**
 * Class NextDepartureController.
 *
 * This controller can build a speech response for the question "When does the next ... leave from ...?".
 *
 * @package App\Http\Controllers
 */
class NextDepartureController extends DialogFlowController
{
    /**
     * Create a new controller instance.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        Log::info("NextDeparture controller constructing.");
        parent::__construct($request);
    }

    /**
     * Answer a question for the next departure of a bus/train/... from a certain stop.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNextDeparture()
    {
        /**
         * Retrieve an instance of PublicTransportApiWrapper. This instance is configured in AppServiceProvider.php.
         *
         * @var $apiWrapper PublicTransportApiWrapper A wrapper for SL, ResRobot, ...
         */
        $apiWrapper = app(PublicTransportApiWrapper::class);

        // Get the location name from the DialogFlow intent parameters
        $locationName = $this->getDialogFlowPayload()->getParameter('location');

        try {
            /**
             * First of all, we'll need to convert the station name to an id. We are using the StopLocation lookup API
             * to do this.
             */
            // Create a new StopLocation lookup request instance. This is better than using 'new ...Request()' as every
            // wrapper implementation will automaticly return an object of the right type.
            $stopLookupRequest = $apiWrapper->createStopLocationLookupRequestObject();
            // Set the station name we want to resolve.
            $stopLookupRequest->setSearchQuery($locationName);
            // Make the request.
            $stopLookupResponse = $apiWrapper->lookupStopLocation($stopLookupRequest);
            // Get the Id of the first result.
            // TODO: This can be smarter! We know the type of transport the user wants to take.
            // TODO: We can filter the stops to take the best match which serves the preferred type of transport.
            $stopLocationId = $stopLookupResponse->getFoundStopLocations()[0]->getId();

            // Create a new TimeTableRequest instance.
            $timeTableRequest = $apiWrapper->createTimeTableRequestObject();
            // Set the stop Id for which we want the departure information.
            $timeTableRequest->setStopId($stopLocationId);
            // Set the timetable type to Departures using the constant defined in TimeTableType.
            $timeTableRequest->setTimeTableType(TimeTableType::DEPARTURES);
            // Make the request.
            $response = $apiWrapper->getTimeTable($timeTableRequest);

            /**
             * At this point, we have all departures from the stop which was specified.
             * Now we have to find the first stop with the specified means of transport, and create a response.
             */
            // Get the transportation method from DialogFlow, and convert it to uppercase. By converting to uppercase,
            // we can compare it to the transportType field in every timeTableEntry object.
            $transportType = strToUpper($this->getDialogFlowPayload()->getParameter("transportation-method"));

            // Loop through all departures
            foreach ($response->getTimetable() as $timeTableEntry) {
                // If a matching type of transport is found, create an answer and send it back
                if ($timeTableEntry->getTransportType() == $transportType) // Create a response
                {
                    return $this->createTextToSpeechResponse($this->buildNextDepartureResponseText($timeTableEntry));
                }
            }
            // We only reach this point if no matching type of transport was found. We return a default reply
            // about not being able to find a response.
            // Another option would be to search further in time.
            return $this->createTextToSpeechResponse(
                "I could not find any "
                . strtolower($this->getDialogFlowPayload()->getParameter("transportation-method"))
                . " departing from " . $stopLookupResponse->getFoundStopLocations()[0]->getName());

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
        } catch (InvalidKeyException $e) {
            Log::error($e->getMessage() . " at " . $e->getFile() . " : " . $e->getLine());
            return $this->createTextToSpeechResponse("I would like to answer you, but I don't have the right keys");
        } catch (InvalidStoplocationException $e) {
            Log::error($e->getMessage() . " at " . $e->getFile() . " : " . $e->getLine());
            return $this->createTextToSpeechResponse("I would like to answer you, but don't know that station");
        } catch (KeyRequiredException $e) {
            Log::error($e->getMessage() . " at " . $e->getFile() . " : " . $e->getLine());
            return $this->createTextToSpeechResponse("I would like to answer you, but I don't have the right keys");
        } catch (QuotaExceededException $e) {
            Log::error($e->getMessage() . " at " . $e->getFile() . " : " . $e->getLine());
            return $this->createTextToSpeechResponse("I would like to answer you, but I already talked too much");
        } catch (RequestTimedOutException $e) {
            Log::error($e->getMessage() . " at " . $e->getFile() . " : " . $e->getLine());
            return $this->createTextToSpeechResponse("I could not obtain this data, it took too long");
        } catch (InvalidRequestException $e) {
            Log::error($e->getMessage() . " at " . $e->getFile() . " : " . $e->getLine());
            return $this->createTextToSpeechResponse("I would like to answer you, but I didn't get all  the details");
        } catch (ServiceUnavailableException $e) {
            Log::error($e->getMessage() . " at " . $e->getFile() . " : " . $e->getLine());
            return $this->createTextToSpeechResponse("I could not obtain this data, the service is not available");
        }
    }

    /**
     * Build a text string for a certain TimeTableEntry.
     *
     * @param $timeTableEntry
     *
     * @return string
     */
    public function buildNextDepartureResponseText(TimeTableEntry $timeTableEntry): string
    {
        return "The next " . strtolower($timeTableEntry->getTransportType()) . " "
            . "from {$timeTableEntry->getStopName()} is "
            . strtolower($timeTableEntry->getTransportType())
            . " {$timeTableEntry->getLineNumber()} to {$timeTableEntry->getDirection()} "
            . "at {$timeTableEntry->getScheduledStopTime()->format("H:i")}";
    }
}
