<?php
require 'variables.php';
return (object) [
  'subjectPermissions' => [sprintf('manage-%s', $subject)],
  'actions' => [
    'list' => (object) [
      'permissions' => [sprintf('manage-%s', $subject)],
    ],
    'insert-form' => (object) [
      'permissions' => [sprintf('manage-%s', $subject)],
    ],
    'insert' => (object) [
      'permissions' => [sprintf('manage-%s', $subject)],
    ],
    'update-form' => (object) [
      'permissions' => [sprintf('manage-%s', $subject)],
    ],
    'update' => (object) [
      'permissions' => [sprintf('manage-%s', $subject)],
    ],
    'delete-form' => (object) [
      'permissions' => [sprintf('manage-%s', $subject)],
    ],
    'autocomplete' => (object) [
      'permissions' => [sprintf('manage-%s', $subject), sprintf('view-%s', $subject)],
    ],
    'set-side-bar-state' => (object) [
      'permissions' => [sprintf('manage-%s', $subject), sprintf('view-%s', $subject)],
    ],    
  ]
];
