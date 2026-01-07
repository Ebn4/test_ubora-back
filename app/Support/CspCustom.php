<?php
namespace App\Support;

use Spatie\Csp\Policies\Basic;
use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;

class CspCustom extends Policy
{
    public function configure()
    {
        // parent::configure();
        $this
            ->addDirective(Directive::SCRIPT, [Keyword::SELF, 'https://trusted.cdn.com'])
            ->addDirective(Directive::STYLE, [Keyword::SELF, 'https://fonts.googleapis.com']);
        $this->addDirective(Directive::FRAME_ANCESTORS, Keyword::NONE);
    }
}
