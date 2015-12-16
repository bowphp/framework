<?php


namespace System;


interface IHeader
{
	public function addCc($mail, $name = null);
	public function addBcc($mail, $name = null);
	public function addReplayTo($mail, $name = null);
	public function addReturnPath($mail, $name = null);
	public function to($mail, $name = null, $smtp = false);
	public function from($mail, $name);
	public function send($cb = null);
}