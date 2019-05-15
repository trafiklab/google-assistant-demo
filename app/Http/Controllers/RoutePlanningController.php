<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Trafiklab\Common\Model\Contract\PublicTransportApiWrapper;
use Trafiklab\Common\Model\Contract\StopLocationLookupEntry;
use Trafiklab\Common\Model\Exceptions\InvalidKeyException;
use Trafiklab\Common\Model\Exceptions\InvalidRequestException;
use Trafiklab\Common\Model\Exceptions\InvalidStoplocationException;
use Trafiklab\Common\Model\Exceptions\KeyRequiredException;
use Trafiklab\Common\Model\Exceptions\QuotaExceededException;
use Trafiklab\Common\Model\Exceptions\RequestTimedOutException;
use Trafiklab\Common\Model\Exceptions\ServiceUnavailableException;
use Trafiklab\Sl\Model\SlRoutePlanningRequest;
use Trafiklab\Sl\Model\SlStopLocationLookupRequest;
use Trafiklab\Sl\SlWrapper;

class RoutePlanningController extends DialogFlowController
{
    /**
     * Create a new controller instance.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        Log::info("RoutePlanning controller constructing.");
        parent::__construct($request);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRoutePlanning()
    {
        try {
            /**
             * @var $slWrapper PublicTransportApiWrapper A wrapper for SL, ResRobot, ...
             */
            $slWrapper = app(SlWrapper::class);
            $origin = $this->getStopLocation($slWrapper, $this->getDialogFlowPayload()->getParameter('origin'));
            $destination = $this->getStopLocation($slWrapper, $this->getDialogFlowPayload()->getParameter('destination'));
            $routePlanningRequest = new SlRoutePlanningRequest();
            $routePlanningRequest->setOriginStopId($origin->getId());
            $routePlanningRequest->setDestinationStopId($destination->getId());
            $response = $slWrapper->getRoutePlanning($routePlanningRequest);
            if (count($response->getTrips()) > 0) {
                $routePlan = $response->getTrips()[0];
                $responseText = "I found you the following route from {$origin->getName()} to {$origin->getName()}.\n";
                foreach ($routePlan->getLegs() as $i => $leg) {
                    if ($i == 0) {
                        if (count($routePlan->getLegs()) == 1) {
                            $responseText .= "Take ";
                        } else {
                            $responseText .= "First take ";
                        }
                    } else {
                        $responseText .= "Then take ";
                    }
                    $responseText .= strtolower($leg->getVehicle()->getType()) . " {$leg->getVehicle()->getNumber()} towards {$leg->getDirection()} from {$leg->getOrigin()->getStopName()} at {$leg->getOrigin()->getScheduledDepartureTime()->format("H:i")}.";
                    $responseText .= "Ride along for " . ($leg->getDestination()->getScheduledArrivalTime()->getTimestamp() - $leg->getOrigin()->getScheduledDepartureTime()->getTimestamp()) / 60 . " minutes.";
                    $responseText .= "Exit the vehicle in " . $leg->getDestination()->getStopName();
                }
                $responseText .= "You have then arrived at your destination.";
                return $this->createTextToSpeechResponse($responseText);
            } else {
                return $this->createTextToSpeechResponse("I could not find any route " . strtolower($this->getDialogFlowPayload()->getParameter("transportation-method")) .
                    " from " . $origin->getName() . " to " . $destination->getName());
            }
        } catch (InvalidKeyException $e) {
            Log::error($e->getMessage() . " at " . $e->getFile() . " : " . $e->getLine());
            return $this->createTextToSpeechResponse("I would like to answer you, but I don't have the right keys");
        } catch
        (InvalidStoplocationException $e) {
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

    /**
     * @param PublicTransportApiWrapper $apiWrapper
     *
     * @param string                    $id
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
        $locationLookupRequest = new SlStopLocationLookupRequest();
        $locationLookupRequest->setSearchQuery($id);
        $stopLocationLookupResponse = $apiWrapper->lookupStopLocation($locationLookupRequest);
        return $stopLocationLookupResponse->getFoundStopLocations()[0];
    }


}
