<?php
return [
  'self'                       => [
    'service'                     => 'Facebook Push Webhook Connector',
    'version'                     => 'sample',
  ],
  'identity'                   => [
    'name'                        => '<YOUR PAGE NAME>',     // page name
    'id'                          => '<YOUR PAGE ID>'  // page id
  ],
  'pushServer'                 => [
    'protocol'                    =>   'https',
    'domain'                      =>   '<YOUR DOMAIN>',
    'port'                        =>   '443',
    'verifyToken'                 =>  '<YOUR VERIFY TOKEN>',
  ],
] ;
?>
