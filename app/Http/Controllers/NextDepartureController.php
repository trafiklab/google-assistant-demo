<?php

namespace App\Http\Controllers;

use App\Http\Repositories\SlDataRepository;
use Illuminate\Http\Request;
use Sl\SlWrapper;
use Trafiklab\Common\Model\Exceptions\InvalidKeyException;
use Trafiklab\Common\Model\Exceptions\InvalidRequestException;
use Trafiklab\Common\Model\Exceptions\InvalidStoplocationException;
use Trafiklab\Common\Model\Exceptions\KeyRequiredException;
use Trafiklab\Common\Model\Exceptions\QuotaExceededException;
use Trafiklab\Common\Model\Exceptions\RequestTimedOutException;
use Trafiklab\Common\Model\Exceptions\ServiceUnavailableException;
use Trafiklab\Sl\Model\SlTimeTableRequest;

class NextDepartureController extends GoogleHomeController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        parent::__construct(json_decode($request->getContent(), true));
    }

    public function getNextDeparture()
    {
        $locationName = $this->getDialogFlowPayload()->getParameter('location');
        $slData = new SlDataRepository();
        $slData->getStationId($locationName);

        $timeTableRequest = new SlTimeTableRequest();

        /**
         * @var $slWrapper SlWrapper
         */
        $slWrapper = app(SlWrapper::class);
        try {
            $response = $slWrapper->getTimeTable($timeTableRequest);
            // Create a response
            $this->respondWithTextToSpeech("The next {$response->getTimetable()[0]->getTransportType()} 
              from {$response->getTimetable()[0]->getStopName()} is
              {$response->getTimetable()[0]->getLineNumber()}  {$response->getTimetable()[0]->getLineName()} 
              at {$response->getTimetable()[0]->getScheduledStopTime()->format("H:i")}"
            );
        } catch (InvalidKeyException $e) {
            $this->respondWithTextToSpeech("I would like to answer you, but I don't have the right keys");
        } catch (InvalidStoplocationException $e) {
            $this->respondWithTextToSpeech("I would like to answer you, but don't know that station");
        } catch (KeyRequiredException $e) {
            $this->respondWithTextToSpeech("I would like to answer you, but I don't have the right keys");
        } catch (InvalidRequestException $e) {
            $this->respondWithTextToSpeech("I would like to answer you, but I didn't get all  the details");
        } catch (QuotaExceededException $e) {
            $this->respondWithTextToSpeech("I would like to answer you, but I already talked too much");
        } catch (RequestTimedOutException $e) {
            $this->respondWithTextToSpeech("I could not obtain this data, it took too long");
        } catch (ServiceUnavailableException $e) {
            $this->respondWithTextToSpeech("I could not obtain this data, the service is not available");
        }
    }


}
