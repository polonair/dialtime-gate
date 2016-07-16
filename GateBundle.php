<?php

namespace Polonairs\Dialtime\GateBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Polonairs\Dialtime\GateBundle\DependencyInjection\GateExtension;

class GateBundle extends Bundle
{
	public function getContainerExtension()
	{
		return new GateExtension();
	}
}
