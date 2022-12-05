<?php
/**
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

namespace App\Http\Models;

/**
 * When Google DialogFlow makes a request to a webservice, it sends a payload in a POST request.
 * This payload contains information about the question asked to Google Assistant, so the server can answer
 * appropriately. Read more at https://dialogflow.com/docs/fulfillment/how-it-works .
 *
 * @package App\Http\Models
 */
class DialogFlowPayload
{
    private string $_conversationId;
    private string $_intentDisplayName;
    private string $_intentName;
    private bool $_containsAllRequiredParameters;
    private array $_parameters;
    private string $_action;
    private string $_queryText;
    private string $_languageCode;

    /**
     * Create an instance of DialogFlowPayload from POST data received from Google DialogFlow.
     *
     * @param array $payload
     */
    public function __construct(array $payload)
    {
        $this->_queryText = $payload['queryResult']['queryText'];
        $this->_action = $payload['queryResult']['action'];
        $this->_languageCode = $payload['queryResult']['languageCode'];
        $this->_parameters = $payload['queryResult']['parameters'];
        $this->_containsAllRequiredParameters = $payload['queryResult']['allRequiredParamsPresent'];
        $this->_intentName = $payload['queryResult']['intent']['name'];
        $this->_intentDisplayName = $payload['queryResult']['intent']['displayName'];
        //$this->_conversationId = $payload['originalDetectIntentRequest']['conversation']['conversationId'];
    }

    /**
     * This id identifies the conversation, and allows you to answer follow-up questions.
     *
     * @return string
     */
    public function getConversationId(): string
    {
        return $this->_conversationId;
    }

    /**
     * This name shows the human readable name of the DialogFlow intent that made the request to the server.
     *
     * @return string
     */
    public function getIntentDisplayName(): string
    {
        return $this->_intentDisplayName;
    }

    /**
     * This string uniquely identifies the DialogFlow intent that made the request to the server.
     *
     * @return string
     */
    public function getIntentName(): string
    {
        return $this->_intentName;
    }

    /**
     * This boolean indicates whether or not all required parameters for the intent are present.
     *
     * @return bool
     */
    public function containsAllRequiredParameters(): bool
    {
        return $this->_containsAllRequiredParameters;
    }

    /**
     * Get an associative array of the intents parameters in the request.
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->_parameters;
    }

    /**
     * Get a specific intent parameter.
     *
     * @param string $parameter
     *
     * @return null|string
     */
    public function getParameter(string $parameter): ?string
    {
        return key_exists($parameter, $this->_parameters) ? $this->_parameters[$parameter] : null;
    }

    /**
     * Get the name of the action which was executed on DialogFlow.
     *
     * @return string
     */
    public function getAction(): string
    {
        return $this->_action;
    }

    /**
     * Get the original query text by the user.
     *
     * @return string
     */
    public function getQueryText(): string
    {
        return $this->_queryText;
    }

    /**
     * @return string
     */
    public function getLanguageCode(): string
    {
        return $this->_languageCode;
    }
}
