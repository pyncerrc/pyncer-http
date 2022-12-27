<?php
namespace Pyncer\Http\Message;

use JsonSerializable;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

interface HtmlResponseInterface extends PsrResponseInterface, JsonSerializable
{}
