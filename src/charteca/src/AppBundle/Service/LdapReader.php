<?php

namespace AppBundle\Service;

use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;
use Symfony\Component\Ldap\Ldap;

//TODO : comment

class LdapReader
{
	private $ldapHost;
	private $ldapPort;
	private $ldapReaderDn;
	private $ldapReaderPw;
	private $ldapracine;

	// TODO : comment
	public function __construct($ldapHost, $ldapPort, $ldapReaderDn, $ldapReaderPw, $ldapRacine)
	{
		$this->ldapHost = $ldapHost;
		$this->ldapPort = $ldapPort;
		$this->ldapReaderDn = $ldapReaderDn;
		$this->ldapReaderPw = $ldapReaderPw;
		$this->ldapRacine = $ldapRacine;
	}
 
	// TODO comment
	public function getFreDuRne($uid)
	{
		$adapter = new Adapter(array(
		    'host' => $this->ldapHost,
		    'port' => $this->ldapPort,
		    'encryption' => 'none',
		    'options' => array(
			'protocol_version' => 2,
			'referrals' => false,
		    ),
		));
		$ldap = new Ldap($adapter);

		$ldap->bind($this->ldapReaderDn, $this->ldapReaderPw);

		$results = $ldap->query($this->ldapRacine,'(uid='.$uid.')')
				->execute()
				->toArray();

		if(!empty($results))
		{
		    return $results[0]->getAttribute('FrEduRne');
		}

		return null;
	}
		
}