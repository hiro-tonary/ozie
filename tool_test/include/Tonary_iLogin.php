<?php
// Tonary_iLogin
// Copyright (C) 2013-2014 Tonary Management System, Inc. All Rights Reserved.

interface Tonary_iLogin{

	public function __construct($redirect_dir=null);

	public function login($id=null, $pass=null);

	public function logout();

}
