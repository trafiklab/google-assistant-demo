# Google Assistant Demo
[![License: MPL 2.0](https://img.shields.io/badge/License-MPL%202.0-brightgreen.svg)](https://opensource.org/licenses/MPL-2.0)

[![Deploy](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy)

This project is a small demo on how you can use data from Trafiklab.org, with the help of the Trafiklab PHP SDKs, 
and use it to create a Google Assistant bot with the help of DialogFlow.

DialogFlow is a framework which can handle input from and output to various assistants, including Google Assistant. It
can send requests to a so-called 'webhook', which can create a dynamic reply. The image below can help you understand
how this all works together.

![How dialogflow works](https://codelabs.developers.google.com/codelabs/actions-1/img/dd9b9b73a367c4a6.png)

The project is based on Laravel Lumen. It requires PHP 7.1 or higher on the host.

## Installation

### DialogFlow
The DialogFlow project which is used to link Google Assistant has been exported to 
[a zip file](https://raw.githubusercontent.com/trafiklab/google-assistant-demo/master/dialogflow-stockholm-public-transport.zip), 
and can be downloaded and imported to your own DialogFlow project. 
Read [the dialogflow docs](https://dialogflow.com/docs/agents/export-import-restore) for more information.
 
 
### Webhook
In order to deploy your webhook, you can use Heroku as a free and easy hosting service. This repository already contains the needed
configuration. [A good tutorial on how you can deploy to Heroku can be found here](https://github.com/dwyl/learn-heroku),
 or you can read read the official documentation(https://devcenter.heroku.com/articles/github-integration).
 
If you choose to host this project yourself, download the project to the location and run `composer install`. When configuring
 the web server, you need to use `/public` as the root directory of the project.

## Forking, reporting issues and creating pull requests

This project is meant as an example and base to create your own, better version of it.
We encourage you to fork it, improve on it, and to publish your own version on chatbot platforms like Google Assistant or Alexa. 

Should you find a bug in our code, feel free to report it, or to create a pull request for it. Pull requests will be accepted for
bug fixes, but may not always be accepted for adding new (large) features.   
