<?php

declare(strict_types=1);

namespace Pam\Native\Drawer;



enum DrawerType: int
{
    case Front = 1;
    case Back = 2;
    case Slide = 3;
    case Permanent = 4;
}
