<?php

namespace App\Http\Requests;

use App\Http\Models\DialogFlowPayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DialogflowRequest extends Request
{
    private DialogFlowPayload $_dialogFlowPayload;

    public function __construct(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);
        Log::info("DialogflowRequest constructing. POST Payload: " . $this->getContent());
        $this->_dialogFlowPayload = new DialogFlowPayload(json_decode($this->getContent(), true));
    }

    /**
     * @return DialogFlowPayload
     */
    public function getDialogFlowPayload(): DialogFlowPayload
    {
        return $this->_dialogFlowPayload;
    }


    /**
     * @return string
     */
    public function getLanguageCode(): string
    {
        return $this->_dialogFlowPayload->getLanguageCode();
    }
}
