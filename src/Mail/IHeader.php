<?php


namespace System\Mail;

use Message;

interface IHeader
{

	public function send(Message $msg);
	
}