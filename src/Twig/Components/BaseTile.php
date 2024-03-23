<?php

// Path: src/Twig/Components/BaseTile.php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class BaseTile
{
    public string $title = '';
    public array $buttons = [];
    public string $content = '';
    
}