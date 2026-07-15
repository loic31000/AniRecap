<?php

namespace App\Security;

use App\Entity\User;

interface Ownable
{
    public function getOwner(): ?User;
}
