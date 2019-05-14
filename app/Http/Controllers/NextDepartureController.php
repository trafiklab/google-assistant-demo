<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Trafiklab\Common\Model\Contract\PublicTransportApiWrapper;
use Trafiklab\Common\Model\Exceptions\InvalidKeyException;
use Trafiklab\Common\Model\Exceptions\InvalidRequestException;
use Trafiklab\Common\Model\Exceptions\InvalidStoplocationException;
use Trafiklab\Common\Model\Exceptions\KeyRequiredException;
use Trafiklab\Common\Model\Exceptions\QuotaExceededException;
use Trafiklab\Common\Model\Exceptions\RequestTimedOutException;
use Trafiklab\Common\Model\Exceptions\ServiceUnavailableException;
use Trafiklab\Sl\Model\SlStopLocationLookupRequest;
use Trafiklab\Sl\Model\SlTimeTableRequest;
use Trafiklab\Sl\SlWrapper;

class NextDepartureController extends DialogFlowController
{
    /**
     * Create a new controller instance.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        Log::info("NextDeparture controller constructed.");
        parent::__construct($request);
    }

    public function getNextDeparture()
    {
        /**
         * @var $slWrapper PublicTransportApiWrapper A wrapper for SL, ResRobot, ...
         */
        $slWrapper = app(SlWrapper::class);
        $locationName = $this->getDialogFlowPayload()->getParameter('location');

        try {
            $locationLookupRequest = new SlStopLocationLookupRequest();
            $locationLookupRequest->setSearchQuery($locationName);
            $stopLocationLookupResponse = $slWrapper->lookupStopLocation($locationLookupRequest);
            $stopLocationId = $stopLocationLookupResponse->getFoundStopLocations()[0]->getId();

            $timeTableRequest = new SlTimeTableRequest();
            $timeTableRequest->setStopId($stopLocationId);
            $response = $slWrapper->getTimeTable($timeTableRequest);

            $transportType = strToUpper($this->getDialogFlowPayload()->getParameter("transportation-method"));

            foreach ($response->getTimetable() as $timeTableEntry) {
                if ($timeTableEntry->getTransportType() == $transportType) // Create a response
                {
                    return $this->createTextToSpeechResponse("The next " . strtolower($timeTableEntry->getTransportType()) . " "
                        . "from {$timeTableEntry->getStopName()} is "
                        . strtolower($timeTableEntry->getTransportType()) . " {$timeTableEntry->getLineNumber()}  to {$timeTableEntry->getDirection()} "
                        . "at {$timeTableEntry->getScheduledStopTime()->format("H:i")}");
                }
            }
            return $this->createTextToSpeechResponse("I could not find any " . strtolower($this->getDialogFlowPayload()->getParameter("transportation-method")) .
                " departing from " . $stopLocationLookupResponse->getFoundStopLocations()[0]->getName());
        } catch (InvalidKeyException $e) {
            Log::error($e->getMessage() . " at " . $e->getFile() . " : " . $e->getLine());
            return $this->createTextToSpeechResponse("I would like to answer you, but I don't have the right keys");
        } catch (InvalidStoplocationException $e) {
            Log::error($e->getMessage() . " at " . $e->getFile() . " : " . $e->getLine());
            return $this->createTextToSpeechResponse("I would like to answer you, but don't know that station");
        } catch (KeyRequiredException $e) {
            Log::error($e->getMessage() . " at " . $e->getFile() . " : " . $e->getLine());
            return $this->createTextToSpeechResponse("I would like to answer you, but I don't have the right keys");
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


}
