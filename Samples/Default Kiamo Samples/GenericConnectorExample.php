<?php

namespace UserFiles\Messaging\Connector;

use Kiamo\Bundle\AdminBundle\Utility\Messaging\ConnectorConfiguration;
use Kiamo\Bundle\AdminBundle\Utility\Messaging\GenericConnectorInterface;

/**
 * Class TestGenericConnector
 * @package Kiamo\Bundle\AdminBundle\Utility\Messaging
 */
class GenericConnectorExample implements GenericConnectorInterface
{
    /**
     * @var ConnectorConfiguration
     */
    private $_parameters;

    /**
     * @param ConnectorConfiguration $configuration
     */
    public function __construct(ConnectorConfiguration $configuration) {
        $this->_parameters = $configuration;
    }

    /**
     * Retourne le nom affiché dans l'interface Kiamo
     * @return string
     */
    public function getName() {
        return 'Nom affiché dans Kiamo';
    }

    /**
    * Retourne l'icone souhaitant être utilisée pour le connecteur, null pour icône par défaut
    * @return string
    */
    public function getIcon() {
      return null;
    }

    /**
     * Récupération des messages
     * @param \Kiamo\Bundle\AdminBundle\Utility\Messaging\ParameterBag $parameterBag
     * @return \Kiamo\Bundle\AdminBundle\Utility\Messaging\ParameterBag
     */
    public function fetch($parameterBag) {
        $parameterBag->addMessage([
            'id' => 'ID du message',
            'senderId' => 'ID du contact',
            'senderName' => 'Nom du contact',
            'content' => 'Contenu du message',
        ]);

        return $parameterBag;
    }

    /**
     * Send a message
     * @param array $messageTask
     * @return void
     */
    public function send(array $messageTask) {
        // Logique d'envoi d'un message
    }
}