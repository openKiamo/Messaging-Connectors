<?php
// Structure and list of existing file configurations (composite names)
// ---
// Minimal list (even if items are empty):
// - tools
// - services
//   -> int
//   -> ext
return [
  'tools' => [
    'key'   => 't',
    'items' => [
      'logger',
    ],
  ],
  'user'  => [
    'key'   => 'u',
    'items' => [
      'KConnectorPushFacebook',
    ],
  ],
] ;
?>
