<?php
use Simplex\Local\Frontend;
$frontendController = $DIContainer->get(Frontend\Controller::class);
$frontendController->buildTemplateHelpers();
