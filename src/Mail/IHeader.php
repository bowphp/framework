<?php


namespace Snoop\Mail;

use Message;

interface IHeader
{

	public function send(Message $msg);
	
}