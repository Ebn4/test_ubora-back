<?php
namespace App\Support;
 
use Spatie\Csp\Policies\Basic;
use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;

class CspCustom extends Basic
{
    public function configure()
    {
        parent::configure();
 
        $this->addDirective(Directive::FRAME_ANCESTORS, Keyword::NONE);
    }  
}